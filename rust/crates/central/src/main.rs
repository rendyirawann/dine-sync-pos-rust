mod audit;
mod auth;
mod customer;
mod finance;
mod kasir;
mod kitchen;
mod logactivity;
mod master;
mod queue;
mod rbac;
mod realtime;
mod report;
mod ratelimit;
mod shift;
mod stock;
mod sync;
mod usermgmt;
mod view;

use std::sync::Arc;

use axum::{
    extract::State,
    middleware::from_fn,
    response::{Html, Redirect},
    routing::{get, post},
    Router,
};

use rbac::CurrentUser;
use sqlx::{postgres::PgPoolOptions, PgPool};
use tera::Tera;
use tower_http::services::ServeDir;
use tower_sessions::{MemoryStore, SessionManagerLayer};

#[derive(Clone)]
pub struct AppState {
    pub pool: PgPool,
    pub local: sqlx::SqlitePool,
    pub tera: Arc<Tera>,
    pub limiter: Arc<ratelimit::RateLimiter>,
    pub force_offline: Arc<std::sync::atomic::AtomicBool>,
    pub events: tokio::sync::broadcast::Sender<String>,
}

#[tokio::main]
async fn main() -> anyhow::Result<()> {
    tracing_subscriber::fmt::init();
    dotenvy::dotenv().ok();

    let db_url = std::env::var("DATABASE_URL")
        .expect("DATABASE_URL belum di-set (lihat rust/crates/central/.env)");

    let pool = PgPoolOptions::new()
        .max_connections(5)
        .after_connect(|conn, _meta| {
            Box::pin(async move {
                // Samakan zona waktu koneksi dgn app (WIB) agar current_date konsisten.
                sqlx::query("SET TIME ZONE 'Asia/Jakarta'").execute(conn).await?;
                Ok(())
            })
        })
        .connect_lazy(&db_url)?;
    tracing::info!("Pool PostgreSQL siap (lazy, TZ=Asia/Jakarta)");

    // Path absolut berbasis lokasi crate (cwd-independent), pakai '/' agar aman di Windows.
    let manifest_dir = env!("CARGO_MANIFEST_DIR").replace('\\', "/");
    let tera = Tera::new(&format!("{manifest_dir}/templates/**/*.html"))?;
    let assets_dir = format!("{manifest_dir}/../../../public/assets");
    let storage_dir = format!("{manifest_dir}/../../../storage/app/public");

    // DB lokal (SQLite) untuk mode offline / local-first.
    let local_db = format!("{manifest_dir}/local.db");
    let local = sqlx::sqlite::SqlitePoolOptions::new()
        .connect_with(
            sqlx::sqlite::SqliteConnectOptions::new()
                .filename(&local_db)
                .create_if_missing(true),
        )
        .await?;
    sqlx::query(sync::LOCAL_SCHEMA_ORDERS).execute(&local).await?;
    sqlx::query(sync::LOCAL_SCHEMA_DETAILS).execute(&local).await?;
    sqlx::query(sync::LOCAL_SCHEMA_TABLES).execute(&local).await?;
    sqlx::query(sync::LOCAL_SCHEMA_MENUS).execute(&local).await?;
    sqlx::query(sync::LOCAL_SCHEMA_CATEGORIES).execute(&local).await?;
    sqlx::query(sync::LOCAL_SCHEMA_SETTINGS).execute(&local).await?;
    tracing::info!("SQLite lokal siap: {local_db}");

    let state = AppState {
        pool,
        local,
        tera: Arc::new(tera),
        limiter: Arc::new(ratelimit::RateLimiter::default()),
        force_offline: Arc::new(std::sync::atomic::AtomicBool::new(false)),
        events: tokio::sync::broadcast::channel::<String>(256).0,
    };

    // Background: auto-sync order lokal ke pusat tiap 30 detik.
    tokio::spawn(sync::auto_sync_loop(state.clone()));

    // Tarik master data awal ke cache lokal (best-effort) agar Kasir siap dipakai offline.
    let _ = sync::pull_master(&state).await;

    // Session disimpan di memori (cukup untuk Fase 1; nanti diganti store persisten).
    let session_layer = SessionManagerLayer::new(MemoryStore::default())
        .with_secure(false)
        .with_name("dinesync_session");

    // Rute yang wajib login.
    let protected = Router::new()
        .route("/admin/dashboard", get(dashboard))
        .route("/admin/users", get(usermgmt::users_index).post(usermgmt::user_store))
        .route("/admin/users/{id}", post(usermgmt::user_update))
        .route("/admin/users/{id}/delete", post(usermgmt::user_delete))
        .route("/admin/users/{id}/ban", post(usermgmt::user_ban))
        .route("/admin/users/{id}/unban", post(usermgmt::user_unban))
        .route("/admin/roles", get(usermgmt::roles_index).post(usermgmt::role_store))
        .route("/admin/roles/{id}", post(usermgmt::role_update))
        .route("/admin/roles/{id}/delete", post(usermgmt::role_delete))
        .route("/admin/shifts", get(shift::shift_index))
        .route("/admin/shifts/open", post(shift::shift_open))
        .route("/admin/shifts/close/{id}", post(shift::shift_close))
        .route("/admin/kasir", get(kasir::index))
        .route("/admin/kasir/table-detail/{id}", get(kasir::table_detail))
        .route("/admin/kasir/order/{table_id}", get(kasir::create_order))
        .route("/admin/kasir/store", post(kasir::store_order))
        .route("/admin/kasir/pay-existing", post(kasir::pay_existing))
        .route("/admin/kasir/clear-table/{id}", post(kasir::clear_table))
        .route("/admin/kasir/print/{id}", get(kasir::print_receipt))
        .route("/admin/kitchen", get(kitchen::index))
        .route("/admin/kitchen/order-status", post(kitchen::order_status))
        .route("/admin/categories", get(master::categories_index).post(master::category_store))
        .route("/admin/categories/{id}", post(master::category_update))
        .route("/admin/categories/{id}/delete", post(master::category_delete))
        .route("/admin/menus", get(master::menus_index).post(master::menu_store))
        .route("/admin/menus/{id}", post(master::menu_update))
        .route("/admin/menus/{id}/delete", post(master::menu_delete))
        .route("/admin/tables", get(master::tables_index).post(master::table_store))
        .route("/admin/tables/{id}", post(master::table_update))
        .route("/admin/tables/{id}/delete", post(master::table_delete))
        .route("/admin/promos", get(master::promos_index).post(master::promo_store))
        .route("/admin/promos/{id}", post(master::promo_update))
        .route("/admin/promos/{id}/delete", post(master::promo_delete))
        .route("/admin/suppliers", get(master::suppliers_index).post(master::supplier_store))
        .route("/admin/suppliers/{id}", post(master::supplier_update))
        .route("/admin/suppliers/{id}/delete", post(master::supplier_delete))
        .route("/admin/ingredients", get(master::ingredients_index).post(master::ingredient_store))
        .route("/admin/ingredients/{id}", post(master::ingredient_update))
        .route("/admin/ingredients/{id}/delete", post(master::ingredient_delete))
        .route("/admin/expenses", get(finance::expenses_index).post(finance::expense_store))
        .route("/admin/expenses/budget", post(finance::set_budget))
        .route("/admin/expenses/{id}", post(finance::expense_update))
        .route("/admin/expenses/{id}/delete", post(finance::expense_delete))
        .route("/admin/reports/sales", get(report::sales_report))
        .route("/admin/reports/items", get(report::item_sales_report))
        .route("/admin/stocks", get(stock::stocks_index).post(stock::stock_store))
        .route("/admin/stocks/{id}/delete", post(stock::stock_delete))
        .route("/admin/recipes", get(stock::recipes_index))
        .route("/admin/recipes/{menu_id}", get(stock::recipe_edit).post(stock::recipe_add))
        .route("/admin/recipe-row/{id}/delete", post(stock::recipe_row_delete))
        .route("/admin/stock-opname", get(stock::opname_index).post(stock::opname_store))
        .route("/admin/stock-movements", get(stock::ledger_index))
        .route("/admin/queues", get(queue::admin_index))
        .route("/admin/queues/{id}/status", post(queue::admin_status))
        .route("/admin/tables/{id}/print-qr", get(customer::print_qr))
        .route("/admin/log-activity", get(logactivity::index))
        .route("/admin/sync", get(sync::sync_page))
        .route("/admin/sync/now", post(sync::sync_run))
        .route("/admin/sync/toggle", post(sync::toggle_offline))
        .route_layer(from_fn(auth::require_auth));

    let app = Router::new()
        .route("/", get(|| async { Redirect::to("/admin/dashboard") }))
        .route("/health", get(|| async { "ok" }))
        .route(
            "/admin/login",
            get(auth::login_page).post(auth::login_submit),
        )
        .route("/admin/logout", get(auth::logout).post(auth::logout))
        // Rute publik pelanggan (online-only): scan QR meja → menu → checkout.
        .route("/scan/{uuid}", get(customer::scan_page).post(customer::scan_start))
        .route("/menu/{uuid}", get(customer::menu_page))
        .route("/menu/{uuid}/checkout", post(customer::checkout))
        .route("/order-success/{uuid}", get(customer::success_page))
        // Antrian publik: kiosk ambil nomor + layar TV.
        .route("/kiosk", get(queue::kiosk_page).post(queue::kiosk_take))
        .route("/kiosk/take", post(queue::kiosk_take))
        .route("/display", get(queue::display_page))
        .route("/ws", get(realtime::ws_handler))
        .merge(protected)
        .nest_service("/assets", ServeDir::new(assets_dir))
        .nest_service("/storage", ServeDir::new(storage_dir))
        .with_state(state)
        .layer(session_layer);

    let addr = "127.0.0.1:8088";
    let listener = tokio::net::TcpListener::bind(addr).await?;
    tracing::info!("DineSync (central/Rust) jalan di http://{addr}");
    axum::serve(
        listener,
        app.into_make_service_with_connect_info::<std::net::SocketAddr>(),
    )
    .await?;
    Ok(())
}

#[derive(serde::Serialize)]
struct TopProduct {
    name: String,
    category: String,
    qty: i64,
    revenue_fmt: String,
}

#[derive(serde::Serialize)]
struct UnavailableMenu {
    name: String,
    category: String,
}

/// Format ribuan dengan titik, mirip number_format(n, 0, ',', '.').
pub fn format_rupiah(n: i64) -> String {
    let digits = n.unsigned_abs().to_string();
    let bytes = digits.as_bytes();
    let mut out = String::new();
    for (i, c) in bytes.iter().enumerate() {
        if i > 0 && (bytes.len() - i) % 3 == 0 {
            out.push('.');
        }
        out.push(*c as char);
    }
    if n < 0 {
        format!("-{out}")
    } else {
        out
    }
}

/// Dashboard analytics (terproteksi) — meniru dashboard Laravel.
/// Semua query memakai `unwrap_or` agar halaman tetap render meski data kosong.
async fn dashboard(user: CurrentUser, State(state): State<AppState>) -> Html<String> {
    let pool = &state.pool;

    let revenue: i64 = sqlx::query_scalar(
        "SELECT COALESCE(SUM(grand_total),0)::bigint FROM orders WHERE payment_status='paid' AND created_at >= date_trunc('month', now())",
    ).fetch_one(pool).await.unwrap_or(0);
    let hpp: i64 = sqlx::query_scalar(
        "SELECT COALESCE(SUM(od.hpp),0)::bigint FROM order_details od JOIN orders o ON o.id = od.order_id WHERE o.payment_status='paid' AND o.created_at >= date_trunc('month', now())",
    ).fetch_one(pool).await.unwrap_or(0);
    let expense: i64 = sqlx::query_scalar(
        "SELECT COALESCE(SUM(amount),0)::bigint FROM expenses WHERE date >= date_trunc('month', now())::date",
    ).fetch_one(pool).await.unwrap_or(0);
    let net_profit = revenue - hpp - expense;

    let top_products: Vec<TopProduct> = sqlx::query_as::<_, (String, Option<String>, i64, i64)>(
        "SELECT m.name, c.name, COALESCE(SUM(od.qty),0)::bigint, COALESCE(SUM(od.subtotal),0)::bigint \
         FROM order_details od JOIN orders o ON o.id = od.order_id JOIN menus m ON m.id = od.menu_id \
         LEFT JOIN categories c ON c.id = m.category_id \
         WHERE o.payment_status='paid' AND o.created_at >= date_trunc('month', now()) \
         GROUP BY m.id, m.name, c.name ORDER BY 3 DESC LIMIT 5",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(name, category, qty, rev)| TopProduct {
        name,
        category: category.unwrap_or_else(|| "-".into()),
        qty,
        revenue_fmt: format_rupiah(rev),
    })
    .collect();

    let unavailable_menus: Vec<UnavailableMenu> = sqlx::query_as::<_, (String, Option<String>)>(
        "SELECT m.name, c.name FROM menus m LEFT JOIN categories c ON c.id = m.category_id WHERE m.is_available = false ORDER BY m.name",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(name, category)| UnavailableMenu {
        name,
        category: category.unwrap_or_else(|| "-".into()),
    })
    .collect();

    let chart_rows: Vec<(String, i64, i64)> = sqlx::query_as(
        "SELECT to_char(d, 'DD Mon') AS label, \
            COALESCE(s.total,0)::bigint AS sales, COALESCE(t.amount,0)::bigint AS target \
         FROM generate_series(date_trunc('month', now()), now(), interval '1 day') d \
         LEFT JOIN (SELECT created_at::date dt, SUM(grand_total) total FROM orders WHERE payment_status='paid' AND created_at >= date_trunc('month', now()) GROUP BY 1) s ON s.dt = d::date \
         LEFT JOIN daily_sales_targets t ON t.date = d::date \
         ORDER BY d",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default();
    let chart_categories: Vec<String> = chart_rows.iter().map(|r| r.0.clone()).collect();
    let chart_sales: Vec<i64> = chart_rows.iter().map(|r| r.1).collect();
    let chart_targets: Vec<i64> = chart_rows.iter().map(|r| r.2).collect();

    let mut ctx = view::base_context(&state, &user, "dashboard").await;
    ctx.insert("revenue_fmt", &format_rupiah(revenue));
    ctx.insert("hpp_fmt", &format_rupiah(hpp));
    ctx.insert("expense_fmt", &format_rupiah(expense));
    ctx.insert("net_profit_fmt", &format_rupiah(net_profit));
    ctx.insert("top_products", &top_products);
    ctx.insert("unavailable_menus", &unavailable_menus);
    ctx.insert("chart_categories", &chart_categories);
    ctx.insert("chart_sales", &chart_sales);
    ctx.insert("chart_targets", &chart_targets);

    match state.tera.render("dashboard.html", &ctx) {
        Ok(html) => Html(html),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")),
    }
}

