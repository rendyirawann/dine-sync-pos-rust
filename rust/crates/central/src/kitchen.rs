use axum::{
    extract::{Form, Path, State},
    response::{Html, IntoResponse, Response},
    Json,
};
use serde::{Deserialize, Serialize};
use serde_json::json;
use tower_sessions::Session;

use crate::{auth, rbac::CurrentUser, view, AppState};

#[derive(Serialize)]
struct KItem {
    detail_id: i64,
    name: String,
    qty: i64,
    notes: Option<String>,
    status: String,
}

#[derive(Serialize)]
struct KOrder {
    id: i64,
    invoice_no: String,
    table_number: String,
    customer_name: String,
    order_status: String,
    has_pending: bool,
    items: Vec<KItem>,
}

#[derive(Deserialize)]
pub struct OrderStatusForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    order_id: i64,
    status: String, // cooking | done
}

/// GET /admin/kitchen — layar dapur.
pub async fn index(user: CurrentUser, State(state): State<AppState>, session: Session) -> Response {
    let online = crate::sync::central_online(&state).await;

    let active = if online {
        load_active_central(&state).await
    } else {
        load_active_local(&state).await
    };
    let completed = if online {
        load_completed_central(&state).await
    } else {
        Vec::new()
    };

    let mut ctx = view::base_context(&state, &user, "kitchen").await;
    ctx.insert("active_count", &active.len());
    ctx.insert("completed_count", &completed.len());
    ctx.insert("active", &active);
    ctx.insert("completed", &completed);
    ctx.insert("offline", &!online);
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);

    match state.tera.render("kitchen/index.html", &ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}

async fn load_active_central(state: &AppState) -> Vec<KOrder> {
    let pool = &state.pool;
    let orders: Vec<(i64, String, Option<String>, Option<String>, String)> = sqlx::query_as(
        "SELECT o.id, o.invoice_no, t.table_number, o.customer_name, o.order_status \
         FROM orders o LEFT JOIN tables t ON t.id=o.table_id \
         WHERE o.order_status IN ('pending','cooking') ORDER BY o.created_at",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default();
    let mut out = Vec::new();
    for (id, inv, tnum, cust, ostatus) in orders {
        let items: Vec<KItem> = sqlx::query_as::<_, (i64, Option<String>, i64, Option<String>, String)>(
            "SELECT od.id, m.name, od.qty::bigint, od.notes, od.status FROM order_details od \
             LEFT JOIN menus m ON m.id=od.menu_id WHERE od.order_id=$1 ORDER BY od.id",
        )
        .bind(id)
        .fetch_all(pool)
        .await
        .unwrap_or_default()
        .into_iter()
        .map(|(detail_id, name, qty, notes, status)| KItem {
            detail_id,
            name: name.unwrap_or_else(|| "Menu Dihapus".into()),
            qty,
            notes,
            status,
        })
        .collect();
        let has_pending = items.iter().any(|i| i.status == "pending");
        out.push(KOrder {
            id,
            invoice_no: inv,
            table_number: tnum.unwrap_or_else(|| "Walk-in".into()),
            customer_name: cust.unwrap_or_default(),
            order_status: ostatus,
            has_pending,
            items,
        });
    }
    out
}

async fn load_completed_central(state: &AppState) -> Vec<KOrder> {
    let pool = &state.pool;
    sqlx::query_as::<_, (i64, String, Option<String>, Option<String>, String)>(
        "SELECT o.id, o.invoice_no, t.table_number, o.customer_name, o.order_status \
         FROM orders o LEFT JOIN tables t ON t.id=o.table_id \
         WHERE o.order_status IN ('served','completed') AND o.updated_at >= now() - interval '3 days' \
         ORDER BY o.updated_at DESC LIMIT 30",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, inv, tnum, cust, ostatus)| KOrder {
        id,
        invoice_no: inv,
        table_number: tnum.unwrap_or_else(|| "Walk-in".into()),
        customer_name: cust.unwrap_or_default(),
        order_status: ostatus,
        has_pending: false,
        items: Vec::new(),
    })
    .collect()
}

/// Offline: baca order lokal (belum tersinkron) sebagai antrian dapur (read-only).
async fn load_active_local(state: &AppState) -> Vec<KOrder> {
    let local = &state.local;
    let orders: Vec<(String, String, Option<String>, Option<String>)> = sqlx::query_as(
        "SELECT lo.uuid, lo.invoice_no, (SELECT table_number FROM local_tables WHERE id=lo.table_id), lo.customer_name \
         FROM local_orders lo WHERE lo.synced=0 ORDER BY lo.created_at",
    )
    .fetch_all(local)
    .await
    .unwrap_or_default();
    let mut out = Vec::new();
    for (uuid, inv, tnum, cust) in orders {
        let items: Vec<KItem> = sqlx::query_as::<_, (i64, Option<String>, i64, Option<String>)>(
            "SELECT d.id, (SELECT name FROM local_menus WHERE id=d.menu_id), d.qty, d.notes FROM local_order_details d WHERE d.order_uuid=?",
        )
        .bind(&uuid)
        .fetch_all(local)
        .await
        .unwrap_or_default()
        .into_iter()
        .map(|(detail_id, name, qty, notes)| KItem {
            detail_id,
            name: name.unwrap_or_else(|| "Menu".into()),
            qty,
            notes,
            status: "pending".into(),
        })
        .collect();
        out.push(KOrder {
            id: 0,
            invoice_no: inv,
            table_number: tnum.unwrap_or_else(|| "Walk-in".into()),
            customer_name: cust.unwrap_or_default(),
            order_status: "pending".into(),
            has_pending: true,
            items,
        });
    }
    out
}

/// POST /admin/kitchen/order-status — tandai seluruh item order: cooking / done (online).
pub async fn order_status(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<OrderStatusForm>) -> Response {
    if !user.can("view_kitchen") {
        return Json(json!({"success": false, "error": "Tidak punya izin dapur"})).into_response();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Json(json!({"success": false, "error": "Sesi kedaluwarsa"})).into_response();
    }
    if !crate::sync::central_online(&state).await {
        return Json(json!({"success": false, "error": "Server offline — selesaikan saat online."})).into_response();
    }
    // Hanya 'cooking' / 'done' yang sah — nilai lain ditolak (cegah transisi served+deduksi tak sengaja).
    if form.status != "cooking" && form.status != "done" {
        return Json(json!({"success": false, "error": "Status tidak valid"})).into_response();
    }
    let is_finished = form.status == "done";
    // Transaksi: update status + potong stok (FEFO, idempoten) sekaligus.
    let mut tx = match state.pool.begin().await {
        Ok(t) => t,
        Err(e) => return Json(json!({"success": false, "error": e.to_string()})).into_response(),
    };
    let upd = if form.status == "cooking" {
        let _ = sqlx::query("UPDATE order_details SET status='cooking', updated_at=now() WHERE order_id=$1 AND status='pending'").bind(form.order_id).execute(&mut *tx).await;
        sqlx::query("UPDATE orders SET order_status='cooking', updated_at=now() WHERE id=$1").bind(form.order_id).execute(&mut *tx).await
    } else {
        let _ = sqlx::query("UPDATE order_details SET status='done', updated_at=now() WHERE order_id=$1").bind(form.order_id).execute(&mut *tx).await;
        sqlx::query("UPDATE orders SET order_status='served', updated_at=now() WHERE id=$1").bind(form.order_id).execute(&mut *tx).await
    };
    if let Err(e) = upd {
        return Json(json!({"success": false, "error": e.to_string()})).into_response();
    }
    // Potong stok bahan baku (FEFO) untuk item yang belum dipotong.
    if let Err(e) = crate::stock::deduct_order_stock(&mut tx, form.order_id).await {
        return Json(json!({"success": false, "error": format!("Gagal potong stok: {e}")})).into_response();
    }
    // (handler order_status berlanjut di bawah)
    match tx.commit().await {
        Ok(_) => {
            crate::realtime::kitchen_update(&state);
            if is_finished {
                if let Ok(Some((name, invoice))) = sqlx::query_as::<_, (Option<String>, String)>("SELECT customer_name, invoice_no FROM orders WHERE id=$1")
                    .bind(form.order_id)
                    .fetch_optional(&state.pool)
                    .await
                {
                    let num = invoice.split('-').nth(1).unwrap_or("").to_string();
                    crate::realtime::food_ready(&state, &name.unwrap_or_else(|| "Pelanggan".into()), &num);
                }
            }
            Json(json!({"success": true, "is_finished": is_finished})).into_response()
        }
        Err(e) => Json(json!({"success": false, "error": e.to_string()})).into_response(),
    }
}

#[derive(Deserialize)]
pub struct RecallForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    order_id: i64,
}
/// POST /admin/kitchen/recall — umumkan ulang pesanan yang sudah selesai (TTS).
pub async fn recall(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<RecallForm>) -> Response {
    if !user.can("view_kitchen") {
        return Json(json!({"success": false, "error": "Tidak punya izin dapur"})).into_response();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Json(json!({"success": false, "error": "Sesi kedaluwarsa"})).into_response();
    }
    if let Ok(Some((name, invoice))) = sqlx::query_as::<_, (Option<String>, String)>("SELECT customer_name, invoice_no FROM orders WHERE id=$1")
        .bind(form.order_id)
        .fetch_optional(&state.pool)
        .await
    {
        let num = invoice.split('-').nth(1).unwrap_or("").to_string();
        crate::realtime::food_ready(&state, &name.unwrap_or_else(|| "Pelanggan".into()), &num);
    }
    Json(json!({"success": true})).into_response()
}

#[derive(Serialize)]
struct RecipeItem {
    ingredient: String,
    qty: String,
    unit: String,
}
/// GET /admin/kitchen/recipe/{detail_id} — bahan baku satu item order (utk koki).
pub async fn recipe_details(user: CurrentUser, State(state): State<AppState>, Path(detail_id): Path<i64>) -> Response {
    if !user.can("view_kitchen") {
        return Json(json!({"error": "Tidak punya izin"})).into_response();
    }
    let row: Option<(i64, i64, Option<String>)> = sqlx::query_as(
        "SELECT od.menu_id, od.qty::bigint, m.name FROM order_details od JOIN menus m ON m.id=od.menu_id WHERE od.id=$1",
    )
    .bind(detail_id)
    .fetch_optional(&state.pool)
    .await
    .ok()
    .flatten();
    let Some((menu_id, qty, menu_name)) = row else {
        return Json(json!({"error": "Item tidak ditemukan"})).into_response();
    };
    let items: Vec<RecipeItem> = sqlx::query_as::<_, (String, String, f64)>(
        "SELECT i.name, i.unit, mi.quantity::float8 FROM menu_ingredients mi JOIN ingredients i ON i.id=mi.ingredient_id WHERE mi.menu_id=$1 ORDER BY i.name",
    )
    .bind(menu_id)
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(name, unit, q)| {
        let total = q * qty as f64;
        let qty_str = if total.fract().abs() < 1e-9 { format!("{}", total.round() as i64) } else { format!("{total:.2}") };
        RecipeItem { ingredient: name, unit, qty: qty_str }
    })
    .collect();
    Json(json!({"menu": menu_name.unwrap_or_default(), "qty": qty, "items": items})).into_response()
}
