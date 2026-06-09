use axum::{
    extract::{Form, Path, Query, State},
    http::StatusCode,
    response::{Html, IntoResponse, Redirect, Response},
    Json,
};
use serde::{Deserialize, Serialize};
use serde_json::json;
use tower_sessions::Session;

use crate::{auth, format_rupiah as fmt, rbac::CurrentUser, view, AppState};

// ---------- Structs ----------

#[derive(Serialize)]
struct TableCard {
    id: i64,
    number: String,
    capacity: i64,
    display: String, // available | unpaid | paid
}

#[derive(Serialize)]
struct Cat {
    id: i64,
    name: String,
}

#[derive(Serialize)]
struct MenuItem {
    id: i64,
    category_id: i64,
    name: String,
    price: i64,
    price_fmt: String,
    image: String,
}

#[derive(Serialize)]
struct DetailItem {
    name: String,
    qty: i64,
    subtotal_fmt: String,
    notes: Option<String>,
    status: String,
}

#[derive(Serialize)]
struct OrderCard {
    id: i64,
    invoice_no: String,
    order_type: String,
    customer_name: String,
    order_status: String,
    payment_status: String,
    grand_total: i64,
    grand_total_fmt: String,
    items: Vec<DetailItem>,
}

#[derive(Deserialize)]
pub struct CreateOrderQuery {
    #[serde(default)]
    customer: Option<String>,
    #[serde(rename = "type", default)]
    order_type: Option<String>,
}

#[derive(Deserialize)]
pub struct CartItem {
    pub id: i64,
    pub qty: i64,
    pub price: i64,
    pub subtotal: i64,
    #[serde(default)]
    pub note: Option<String>,
}

#[derive(Deserialize)]
pub struct StorePayload {
    #[serde(rename = "_token", default)]
    pub csrf: String,
    pub table_id: i64,
    pub customer_name: String,
    pub order_type: String,
    pub payment_method: String, // cash | pay_later
    pub cart: Vec<CartItem>,
}

#[derive(Deserialize)]
pub struct PayExistingForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    order_id: i64,
    payment_method: String,
}

#[derive(Deserialize)]
pub struct CsrfForm {
    #[serde(rename = "_token", default)]
    csrf: String,
}

// ---------- Handlers ----------

/// GET /admin/kasir — peta meja (wajib ada shift terbuka).
pub async fn index(user: CurrentUser, State(state): State<AppState>, session: Session) -> Response {
    let pool = &state.pool;

    let has_shift: bool = sqlx::query_scalar(
        "SELECT EXISTS(SELECT 1 FROM shifts WHERE user_id=$1 AND status='open')",
    )
    .bind(user.id)
    .fetch_one(pool)
    .await
    .unwrap_or(false);
    if !has_shift {
        return Redirect::to("/admin/shifts?error=noshift").into_response();
    }

    let tables: Vec<TableCard> = sqlx::query_as::<_, (i64, String, i64, String)>(
        "SELECT t.id, t.table_number, t.capacity::bigint, \
            CASE WHEN t.status='available' THEN 'available' \
                 WHEN EXISTS(SELECT 1 FROM orders o WHERE o.table_id=t.id AND o.order_status IN ('pending','cooking','served') AND o.payment_status='unpaid') THEN 'unpaid' \
                 ELSE 'paid' END \
         FROM tables t ORDER BY t.table_number",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, number, capacity, display)| TableCard { id, number, capacity, display })
    .collect();

    let empty_count = tables.iter().filter(|t| t.display == "available").count();
    let unpaid_count = tables.iter().filter(|t| t.display == "unpaid").count();
    let paid_count = tables.iter().filter(|t| t.display == "paid").count();

    let mut ctx = view::base_context(&state, &user, "kasir").await;
    ctx.insert("tables", &tables);
    ctx.insert("empty_count", &empty_count);
    ctx.insert("unpaid_count", &unpaid_count);
    ctx.insert("paid_count", &paid_count);
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);

    render(&state, "kasir/index.html", &ctx)
}

/// GET /admin/kasir/table-detail/{id} — JSON untuk modal.
pub async fn table_detail(State(state): State<AppState>, Path(id): Path<i64>) -> Response {
    let pool = &state.pool;

    let row: Option<(String, String)> =
        sqlx::query_as("SELECT table_number, status FROM tables WHERE id=$1")
            .bind(id)
            .fetch_optional(pool)
            .await
            .ok()
            .flatten();
    let Some((table_number, status)) = row else {
        return Json(json!({"status": "available", "table_number": "?"})).into_response();
    };
    if status == "available" {
        return Json(json!({"status": "available", "table_number": table_number})).into_response();
    }

    let orders_raw: Vec<(i64, String, Option<String>, Option<String>, String, String, i64)> =
        sqlx::query_as(
            "SELECT id, invoice_no, order_type, customer_name, order_status, payment_status, grand_total::bigint \
             FROM orders WHERE table_id=$1 AND order_status IN ('pending','cooking','served') ORDER BY created_at DESC",
        )
        .bind(id)
        .fetch_all(pool)
        .await
        .unwrap_or_default();

    if orders_raw.is_empty() {
        return Json(json!({"status": "available", "table_number": table_number})).into_response();
    }

    let mut orders: Vec<OrderCard> = Vec::new();
    for (oid, invoice_no, otype, cust, ostatus, pstatus, grand) in orders_raw {
        let items: Vec<DetailItem> = sqlx::query_as::<_, (Option<String>, i64, i64, Option<String>, String)>(
            "SELECT m.name, od.qty::bigint, od.subtotal::bigint, od.notes, od.status \
             FROM order_details od LEFT JOIN menus m ON m.id=od.menu_id WHERE od.order_id=$1",
        )
        .bind(oid)
        .fetch_all(pool)
        .await
        .unwrap_or_default()
        .into_iter()
        .map(|(name, qty, subtotal, notes, status)| DetailItem {
            name: name.unwrap_or_else(|| "Menu Dihapus".into()),
            qty,
            subtotal_fmt: fmt(subtotal),
            notes,
            status,
        })
        .collect();
        orders.push(OrderCard {
            id: oid,
            invoice_no,
            order_type: otype.unwrap_or_else(|| "dine_in".into()),
            customer_name: cust.unwrap_or_else(|| "Tanpa Nama".into()),
            order_status: ostatus,
            payment_status: pstatus,
            grand_total: grand,
            grand_total_fmt: fmt(grand),
            items,
        });
    }

    let mut ctx = tera::Context::new();
    ctx.insert("table_id", &id);
    ctx.insert("table_number", &table_number);
    ctx.insert("orders", &orders);
    let html = state
        .tera
        .render("kasir/table_detail.html", &ctx)
        .unwrap_or_else(|e| format!("<pre>{e:#}</pre>"));
    Json(json!({"status": "occupied", "html": html})).into_response()
}

/// GET /admin/kasir/order/{table_id} — halaman menu + keranjang.
pub async fn create_order(
    user: CurrentUser,
    State(state): State<AppState>,
    session: Session,
    Path(table_id): Path<i64>,
    Query(q): Query<CreateOrderQuery>,
) -> Response {
    let pool = &state.pool;

    let table: Option<(String, String)> =
        sqlx::query_as("SELECT table_number, status FROM tables WHERE id=$1")
            .bind(table_id)
            .fetch_optional(pool)
            .await
            .ok()
            .flatten();
    let Some((table_number, status)) = table else {
        return Redirect::to("/admin/kasir").into_response();
    };
    if status == "occupied" {
        return Redirect::to("/admin/kasir").into_response();
    }

    let categories: Vec<Cat> = sqlx::query_as::<_, (i64, String)>(
        "SELECT id, name FROM categories ORDER BY name",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name)| Cat { id, name })
    .collect();

    let menus: Vec<MenuItem> = sqlx::query_as::<_, (i64, i64, String, i64, Option<String>)>(
        "SELECT id, category_id, name, price::bigint, image FROM menus WHERE is_available = true ORDER BY name",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, category_id, name, price, image)| MenuItem {
        id,
        category_id,
        name,
        price,
        price_fmt: fmt(price),
        image: match image {
            Some(img) if !img.is_empty() => format!("/storage/menus/{img}"),
            _ => "/assets/media/svg/files/blank-image.svg".to_string(),
        },
    })
    .collect();

    let tax_rate: i64 = sqlx::query_scalar("SELECT COALESCE((SELECT tax_rate FROM settings LIMIT 1), 10)::bigint")
        .fetch_one(pool)
        .await
        .unwrap_or(10);

    let mut ctx = view::base_context(&state, &user, "kasir").await;
    ctx.insert("table_id", &table_id);
    ctx.insert("table_number", &table_number);
    ctx.insert("customer_name", &q.customer.unwrap_or_else(|| "Walk-in".into()));
    ctx.insert("order_type", &q.order_type.unwrap_or_else(|| "dine_in".into()));
    ctx.insert("categories", &categories);
    ctx.insert("menus", &menus);
    ctx.insert("tax_rate", &tax_rate);
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);

    render(&state, "kasir/order.html", &ctx)
}

/// POST /admin/kasir/store — simpan order (cash/pay_later).
pub async fn store_order(
    State(state): State<AppState>,
    session: Session,
    Json(payload): Json<StorePayload>,
) -> Response {
    if !auth::verify_csrf(&session, &payload.csrf).await {
        return (StatusCode::UNPROCESSABLE_ENTITY, Json(json!({"success": false, "error": "Sesi kedaluwarsa"}))).into_response();
    }

    // LOCAL-FIRST: kalau server pusat online → simpan ke pusat; kalau offline → simpan ke SQLite lokal + outbox.
    if crate::sync::central_online(&state).await {
        match store_inner(&state, &payload).await {
            Ok((order_id, kind)) => {
                let message = if kind == "cash" { "Pembayaran tunai berhasil!" } else { "Pesanan dikirim ke dapur (Pay Later)." };
                Json(json!({"success": true, "type": kind, "order_id": order_id, "offline": false, "message": message})).into_response()
            }
            Err(e) => (StatusCode::INTERNAL_SERVER_ERROR, Json(json!({"success": false, "error": e.to_string()}))).into_response(),
        }
    } else {
        match crate::sync::store_order_local(&state, &payload).await {
            Ok(kind) => Json(json!({"success": true, "type": kind, "order_id": serde_json::Value::Null, "offline": true, "message": "📴 Server offline — order disimpan di perangkat ini & akan disinkronkan otomatis saat server online."})).into_response(),
            Err(e) => (StatusCode::INTERNAL_SERVER_ERROR, Json(json!({"success": false, "error": e.to_string()}))).into_response(),
        }
    }
}

async fn store_inner(state: &AppState, payload: &StorePayload) -> anyhow::Result<(i64, &'static str)> {
    let tax_rate: i64 = sqlx::query_scalar("SELECT COALESCE((SELECT tax_rate FROM settings LIMIT 1), 10)::bigint")
        .fetch_one(&state.pool)
        .await
        .unwrap_or(10);

    let subtotal: i64 = payload.cart.iter().map(|i| i.subtotal).sum();
    let tax = (subtotal * tax_rate + 50) / 100; // round half-up
    let grand_total = subtotal + tax;
    let is_cash = payload.payment_method == "cash";
    let pay_method: Option<&str> = if is_cash { Some("cash") } else { None };

    let mut tx = state.pool.begin().await?;

    let (order_id,): (i64,) = sqlx::query_as(
        "INSERT INTO orders (uuid, invoice_no, table_id, promo_id, customer_name, order_type, subtotal, discount_amount, tax, grand_total, payment_method, payment_status, order_status, created_at, updated_at) \
         VALUES (gen_random_uuid(), \
            'INV-' || to_char(now(),'YYYYMMDDHH24MISS') || lpad((floor(random()*90)+10)::int::text, 2, '0'), \
            $1, NULL, $2, $3, $4::numeric, 0, $5::numeric, $6::numeric, $7, 'unpaid', 'pending', now(), now()) \
         RETURNING id",
    )
    .bind(payload.table_id)
    .bind(&payload.customer_name)
    .bind(&payload.order_type)
    .bind(subtotal)
    .bind(tax)
    .bind(grand_total)
    .bind(pay_method)
    .fetch_one(&mut *tx)
    .await?;

    for item in &payload.cart {
        sqlx::query(
            "INSERT INTO order_details (order_id, menu_id, qty, price, subtotal, notes, status, created_at, updated_at) \
             VALUES ($1, $2, $3, $4::numeric, $5::numeric, $6, 'pending', now(), now())",
        )
        .bind(order_id)
        .bind(item.id)
        .bind(item.qty)
        .bind(item.price)
        .bind(item.subtotal)
        .bind(item.note.as_deref())
        .execute(&mut *tx)
        .await?;
    }

    sqlx::query("UPDATE tables SET status='occupied' WHERE id=$1")
        .bind(payload.table_id)
        .execute(&mut *tx)
        .await?;

    if is_cash {
        sqlx::query("UPDATE orders SET payment_status='paid' WHERE id=$1")
            .bind(order_id)
            .execute(&mut *tx)
            .await?;
    }

    tx.commit().await?;
    Ok((order_id, if is_cash { "cash" } else { "pay_later" }))
}

/// POST /admin/kasir/pay-existing — bayar order yang belum lunas (cash).
pub async fn pay_existing(
    State(state): State<AppState>,
    session: Session,
    Form(form): Form<PayExistingForm>,
) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Json(json!({"success": false, "error": "Sesi kedaluwarsa"})).into_response();
    }
    if form.payment_method != "cash" {
        return Json(json!({"success": false, "error": "Metode belum didukung (Midtrans = Fase 5)."})).into_response();
    }
    let res = sqlx::query("UPDATE orders SET payment_method='cash', payment_status='paid', updated_at=now() WHERE id=$1")
        .bind(form.order_id)
        .execute(&state.pool)
        .await;
    match res {
        Ok(_) => Json(json!({"success": true, "type": "cash", "order_id": form.order_id, "message": "Pembayaran tunai lunas!"})).into_response(),
        Err(e) => (StatusCode::INTERNAL_SERVER_ERROR, Json(json!({"success": false, "error": e.to_string()}))).into_response(),
    }
}

/// POST /admin/kasir/clear-table/{id} — kosongkan meja.
pub async fn clear_table(
    State(state): State<AppState>,
    session: Session,
    Path(id): Path<i64>,
    Form(form): Form<CsrfForm>,
) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Json(json!({"success": false, "error": "Sesi kedaluwarsa"})).into_response();
    }
    let pool = &state.pool;

    let unfinished: i64 = sqlx::query_scalar(
        "SELECT COUNT(*) FROM order_details od JOIN orders o ON o.id=od.order_id \
         WHERE o.table_id=$1 AND o.order_status IN ('pending','cooking','served') AND od.status IN ('pending','cooking')",
    )
    .bind(id)
    .fetch_one(pool)
    .await
    .unwrap_or(0);
    if unfinished > 0 {
        return (StatusCode::BAD_REQUEST, Json(json!({"success": false, "error": format!("Tidak bisa mengosongkan meja! Masih ada {unfinished} pesanan yang belum diselesaikan Koki.")}))).into_response();
    }

    let _ = sqlx::query("UPDATE orders SET order_status='completed', updated_at=now() WHERE table_id=$1 AND order_status IN ('pending','cooking','served')")
        .bind(id)
        .execute(pool)
        .await;
    let _ = sqlx::query("UPDATE tables SET status='available' WHERE id=$1")
        .bind(id)
        .execute(pool)
        .await;

    Json(json!({"success": true, "message": "Meja berhasil dikosongkan dan siap digunakan kembali!"})).into_response()
}

/// GET /admin/kasir/print/{id} — struk thermal.
pub async fn print_receipt(State(state): State<AppState>, Path(id): Path<i64>) -> Html<String> {
    let pool = &state.pool;

    let order: Option<(String, Option<String>, i64, i64, i64, Option<String>, String, Option<String>)> =
        sqlx::query_as(
            "SELECT o.invoice_no, o.customer_name, o.subtotal::bigint, o.tax::bigint, o.grand_total::bigint, o.payment_method, \
                to_char(o.created_at,'DD/MM/YYYY HH24:MI'), t.table_number \
             FROM orders o LEFT JOIN tables t ON t.id=o.table_id WHERE o.id=$1",
        )
        .bind(id)
        .fetch_optional(pool)
        .await
        .ok()
        .flatten();
    let Some((invoice_no, customer, subtotal, tax, grand, method, created, table_number)) = order else {
        return Html("<h3>Order tidak ditemukan</h3>".to_string());
    };

    let items: Vec<serde_json::Value> = sqlx::query_as::<_, (Option<String>, i64, i64, i64)>(
        "SELECT m.name, od.qty::bigint, od.price::bigint, od.subtotal::bigint FROM order_details od LEFT JOIN menus m ON m.id=od.menu_id WHERE od.order_id=$1",
    )
    .bind(id)
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(name, qty, price, sub)| json!({
        "name": name.unwrap_or_else(|| "Menu Dihapus".into()),
        "qty": qty, "price_fmt": fmt(price), "subtotal_fmt": fmt(sub)
    }))
    .collect();

    let setting: (String, Option<String>, Option<String>, i64) = sqlx::query_as(
        "SELECT COALESCE((SELECT store_name FROM settings LIMIT 1),'DineSync POS'), \
            (SELECT address FROM settings LIMIT 1), (SELECT phone FROM settings LIMIT 1), \
            COALESCE((SELECT tax_rate FROM settings LIMIT 1),10)::bigint",
    )
    .fetch_one(pool)
    .await
    .unwrap_or(("DineSync POS".into(), None, None, 10));

    let mut ctx = tera::Context::new();
    ctx.insert("invoice_no", &invoice_no);
    ctx.insert("customer", &customer);
    ctx.insert("created", &created);
    ctx.insert("table_number", &table_number.unwrap_or_else(|| "Walk-in".into()));
    ctx.insert("items", &items);
    ctx.insert("subtotal_fmt", &fmt(subtotal));
    ctx.insert("tax_fmt", &fmt(tax));
    ctx.insert("grand_fmt", &fmt(grand));
    ctx.insert("method", &method.unwrap_or_else(|| "CASH".into()));
    ctx.insert("store_name", &setting.0);
    ctx.insert("store_address", &setting.1);
    ctx.insert("store_phone", &setting.2);
    ctx.insert("tax_rate", &setting.3);

    match state.tera.render("kasir/print.html", &ctx) {
        Ok(html) => Html(html),
        Err(e) => Html(format!("<pre>{e:#}</pre>")),
    }
}

fn render(state: &AppState, name: &str, ctx: &tera::Context) -> Response {
    match state.tera.render(name, ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}
