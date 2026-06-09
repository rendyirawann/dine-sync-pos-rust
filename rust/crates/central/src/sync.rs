use std::sync::atomic::Ordering;
use std::time::{Duration, SystemTime, UNIX_EPOCH};

use axum::{
    extract::{Form, State},
    response::{Html, IntoResponse, Response},
    Json,
};
use serde_json::json;
use tower_sessions::Session;

use crate::{auth, kasir::StorePayload, rbac::CurrentUser, view, AppState};

/// Skema SQLite lokal (dijalankan saat startup).
pub const LOCAL_SCHEMA_ORDERS: &str = "CREATE TABLE IF NOT EXISTS local_orders (\
    uuid TEXT PRIMARY KEY, invoice_no TEXT, table_id INTEGER, customer_name TEXT, order_type TEXT, \
    subtotal INTEGER, tax INTEGER, grand_total INTEGER, payment_method TEXT, payment_status TEXT, \
    created_at TEXT DEFAULT (datetime('now')), synced INTEGER DEFAULT 0)";
pub const LOCAL_SCHEMA_DETAILS: &str = "CREATE TABLE IF NOT EXISTS local_order_details (\
    id INTEGER PRIMARY KEY AUTOINCREMENT, order_uuid TEXT, menu_id INTEGER, qty INTEGER, \
    price INTEGER, subtotal INTEGER, notes TEXT)";

/// Apakah server pusat (Postgres) bisa dijangkau? (dengan override simulasi offline)
pub async fn central_online(state: &AppState) -> bool {
    if state.force_offline.load(Ordering::Relaxed) {
        return false;
    }
    matches!(
        tokio::time::timeout(Duration::from_secs(2), sqlx::query("SELECT 1").execute(&state.pool)).await,
        Ok(Ok(_))
    )
}

/// Jumlah order lokal yang belum tersinkron.
pub async fn pending_count(state: &AppState) -> i64 {
    sqlx::query_scalar("SELECT COUNT(*) FROM local_orders WHERE synced = 0")
        .fetch_one(&state.local)
        .await
        .unwrap_or(0)
}

/// Simpan order ke SQLite lokal (saat server pusat offline). Mengembalikan jenis ('cash'/'pay_later').
pub async fn store_order_local(state: &AppState, payload: &StorePayload) -> anyhow::Result<&'static str> {
    let subtotal: i64 = payload.cart.iter().map(|i| i.subtotal).sum();
    let tax = (subtotal * 10 + 50) / 100; // default pajak 10% saat offline
    let grand = subtotal + tax;
    let is_cash = payload.payment_method == "cash";
    let payment_status = if is_cash { "paid" } else { "unpaid" };
    let pay_method: Option<&str> = if is_cash { Some("cash") } else { None };

    let uuid = uuid::Uuid::new_v4().to_string();
    let secs = SystemTime::now().duration_since(UNIX_EPOCH).map(|d| d.as_secs()).unwrap_or(0);
    let invoice = format!("INV-{secs}-{}", &uuid[..4]);

    let mut tx = state.local.begin().await?;
    sqlx::query(
        "INSERT INTO local_orders (uuid, invoice_no, table_id, customer_name, order_type, subtotal, tax, grand_total, payment_method, payment_status, synced) \
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)",
    )
    .bind(&uuid)
    .bind(&invoice)
    .bind(payload.table_id)
    .bind(&payload.customer_name)
    .bind(&payload.order_type)
    .bind(subtotal)
    .bind(tax)
    .bind(grand)
    .bind(pay_method)
    .bind(payment_status)
    .execute(&mut *tx)
    .await?;
    for item in &payload.cart {
        sqlx::query(
            "INSERT INTO local_order_details (order_uuid, menu_id, qty, price, subtotal, notes) VALUES (?, ?, ?, ?, ?, ?)",
        )
        .bind(&uuid)
        .bind(item.id)
        .bind(item.qty)
        .bind(item.price)
        .bind(item.subtotal)
        .bind(item.note.as_deref())
        .execute(&mut *tx)
        .await?;
    }
    tx.commit().await?;
    Ok(if is_cash { "cash" } else { "pay_later" })
}

/// Push semua order lokal yang belum tersinkron ke pusat (idempoten via UUID).
/// Mengembalikan (jumlah_tersinkron, sisa_pending).
pub async fn sync_now(state: &AppState) -> anyhow::Result<(i64, i64)> {
    if !central_online(state).await {
        return Ok((0, pending_count(state).await));
    }
    let pg = &state.pool;
    let local = &state.local;

    let orders: Vec<(String, String, i64, Option<String>, Option<String>, i64, i64, i64, Option<String>, String)> =
        sqlx::query_as(
            "SELECT uuid, invoice_no, table_id, customer_name, order_type, subtotal, tax, grand_total, payment_method, payment_status \
             FROM local_orders WHERE synced = 0 ORDER BY created_at",
        )
        .fetch_all(local)
        .await?;

    let mut synced = 0i64;
    for (uuid, invoice, table_id, cust, otype, subtotal, tax, grand, pmethod, pstatus) in orders {
        // Insert idempoten: kalau uuid sudah ada di pusat, RETURNING tidak mengembalikan baris.
        let central_id: Option<i64> = sqlx::query_scalar(
            "INSERT INTO orders (uuid, invoice_no, table_id, customer_name, order_type, subtotal, discount_amount, tax, grand_total, payment_method, payment_status, order_status, created_at, updated_at) \
             VALUES ($1::uuid, $2, $3, $4, $5, $6::numeric, 0, $7::numeric, $8::numeric, $9, $10, 'pending', now(), now()) \
             ON CONFLICT (uuid) DO NOTHING RETURNING id",
        )
        .bind(&uuid)
        .bind(&invoice)
        .bind(table_id)
        .bind(&cust)
        .bind(&otype)
        .bind(subtotal)
        .bind(tax)
        .bind(grand)
        .bind(&pmethod)
        .bind(&pstatus)
        .fetch_optional(pg)
        .await?;

        if let Some(cid) = central_id {
            let details: Vec<(i64, i64, i64, i64, Option<String>)> = sqlx::query_as(
                "SELECT menu_id, qty, price, subtotal, notes FROM local_order_details WHERE order_uuid = ?",
            )
            .bind(&uuid)
            .fetch_all(local)
            .await?;
            for (menu_id, qty, price, sub, notes) in details {
                sqlx::query(
                    "INSERT INTO order_details (order_id, menu_id, qty, price, subtotal, notes, status, created_at, updated_at) \
                     VALUES ($1, $2, $3, $4::numeric, $5::numeric, $6, 'pending', now(), now())",
                )
                .bind(cid)
                .bind(menu_id)
                .bind(qty)
                .bind(price)
                .bind(sub)
                .bind(notes.as_deref())
                .execute(pg)
                .await?;
            }
            let _ = sqlx::query("UPDATE tables SET status='occupied' WHERE id=$1").bind(table_id).execute(pg).await;
        }
        sqlx::query("UPDATE local_orders SET synced = 1 WHERE uuid = ?").bind(&uuid).execute(local).await?;
        synced += 1;
    }
    Ok((synced, pending_count(state).await))
}

/// Loop auto-sync di background (tiap 30 detik).
pub async fn auto_sync_loop(state: AppState) {
    loop {
        tokio::time::sleep(Duration::from_secs(30)).await;
        if pending_count(&state).await > 0 {
            if let Ok((n, _)) = sync_now(&state).await {
                if n > 0 {
                    tracing::info!("Auto-sync: {n} order tersinkron ke pusat.");
                }
            }
        }
    }
}

// ---------- Handlers ----------

/// GET /admin/sync — halaman status sinkronisasi.
pub async fn sync_page(user: CurrentUser, State(state): State<AppState>, session: Session) -> Html<String> {
    let online = central_online(&state).await;
    let pending = pending_count(&state).await;
    let forced = state.force_offline.load(Ordering::Relaxed);

    let mut ctx = view::base_context(&state, &user, "sync").await;
    ctx.insert("online", &online);
    ctx.insert("pending", &pending);
    ctx.insert("forced_offline", &forced);
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);

    match state.tera.render("sync.html", &ctx) {
        Ok(html) => Html(html),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")),
    }
}

#[derive(serde::Deserialize)]
pub struct CsrfForm {
    #[serde(rename = "_token", default)]
    csrf: String,
}

/// POST /admin/sync/now — sinkron manual.
pub async fn sync_run(State(state): State<AppState>, session: Session, Form(form): Form<CsrfForm>) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Json(json!({"success": false, "error": "Sesi kedaluwarsa"})).into_response();
    }
    match sync_now(&state).await {
        Ok((synced, pending)) => Json(json!({"success": true, "synced": synced, "pending": pending, "online": central_online(&state).await})).into_response(),
        Err(e) => Json(json!({"success": false, "error": e.to_string()})).into_response(),
    }
}

/// POST /admin/sync/toggle — simulasi server offline/online (untuk demo & uji).
pub async fn toggle_offline(State(state): State<AppState>, session: Session, Form(form): Form<CsrfForm>) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Json(json!({"success": false, "error": "Sesi kedaluwarsa"})).into_response();
    }
    let new = !state.force_offline.load(Ordering::Relaxed);
    state.force_offline.store(new, Ordering::Relaxed);
    Json(json!({"success": true, "forced_offline": new})).into_response()
}
