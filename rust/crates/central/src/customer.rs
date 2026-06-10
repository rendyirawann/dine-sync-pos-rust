use axum::{
    extract::{Form, Json, Path, State},
    http::{HeaderMap, StatusCode},
    response::{Html, IntoResponse, Redirect, Response},
};
use serde::{Deserialize, Serialize};
use serde_json::json;
use tower_sessions::Session;

use crate::{auth, format_rupiah as fmt, rbac::CurrentUser, AppState};

const SESS_NAME: &str = "cust_name";

fn render(state: &AppState, name: &str, ctx: &tera::Context) -> Response {
    match state.tera.render(name, ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}
fn not_found(msg: &str) -> Response {
    (StatusCode::NOT_FOUND, Html(format!("<div style='font-family:sans-serif;padding:40px;text-align:center'><h2>{msg}</h2><p>Pindai ulang QR di meja Anda.</p></div>"))).into_response()
}

/// Cari meja by uuid → (id, table_number, status). None bila tak ada.
async fn find_table(state: &AppState, uuid: &str) -> Option<(i64, String, String)> {
    sqlx::query_as::<_, (i64, String, String)>("SELECT id, table_number, status FROM tables WHERE uuid::text=$1")
        .bind(uuid)
        .fetch_optional(&state.pool)
        .await
        .ok()
        .flatten()
}

// ---------- SCAN (publik) ----------
pub async fn scan_page(State(state): State<AppState>, session: Session, Path(uuid): Path<String>) -> Response {
    let Some((_id, table_number, status)) = find_table(&state, &uuid).await else {
        return not_found("Meja tidak ditemukan");
    };
    // Bila sudah ada nama di sesi, langsung ke menu.
    if let Ok(Some(_n)) = session.get::<String>(SESS_NAME).await {
        return Redirect::to(&format!("/menu/{uuid}")).into_response();
    }
    let mut ctx = tera::Context::new();
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);
    ctx.insert("uuid", &uuid);
    ctx.insert("table_number", &table_number);
    ctx.insert("occupied", &(status == "occupied"));
    render(&state, "customer/scan.html", &ctx)
}

#[derive(Deserialize)]
pub struct StartForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    customer_name: String,
}
pub async fn scan_start(State(_state): State<AppState>, session: Session, Path(uuid): Path<String>, Form(form): Form<StartForm>) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to(&format!("/scan/{uuid}")).into_response();
    }
    let name = form.customer_name.trim();
    if name.is_empty() {
        return Redirect::to(&format!("/scan/{uuid}")).into_response();
    }
    let _ = session.insert(SESS_NAME, name.to_string()).await;
    Redirect::to(&format!("/menu/{uuid}")).into_response()
}

// ---------- MENU (publik) ----------
#[derive(Serialize)]
struct MenuItem {
    id: i64,
    name: String,
    description: String,
    price: i64,
    price_fmt: String,
    image: String,
    category_id: i64,
}
#[derive(Serialize)]
struct Cat {
    id: i64,
    name: String,
}
#[derive(Serialize)]
struct ActiveOrder {
    invoice_no: String,
    status: String,
    grand_fmt: String,
}

pub async fn menu_page(State(state): State<AppState>, session: Session, Path(uuid): Path<String>) -> Response {
    let Some((table_id, table_number, _status)) = find_table(&state, &uuid).await else {
        return not_found("Meja tidak ditemukan");
    };
    let Ok(Some(name)) = session.get::<String>(SESS_NAME).await else {
        return Redirect::to(&format!("/scan/{uuid}")).into_response();
    };

    let categories: Vec<Cat> = sqlx::query_as::<_, (i64, String)>("SELECT id, name FROM categories ORDER BY name")
        .fetch_all(&state.pool)
        .await
        .unwrap_or_default()
        .into_iter()
        .map(|(id, name)| Cat { id, name })
        .collect();

    let menus: Vec<MenuItem> = sqlx::query_as::<_, (i64, String, Option<String>, i64, Option<String>, Option<i64>)>(
        "SELECT id, name, description, price::bigint, image, category_id::bigint FROM menus WHERE is_available = true ORDER BY name",
    )
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name, desc, price, image, cat)| MenuItem {
        id,
        name,
        description: desc.unwrap_or_default(),
        price,
        price_fmt: fmt(price),
        image: match image {
            Some(img) if !img.is_empty() => format!("/storage/menus/{img}"),
            _ => "/assets/media/svg/files/blank-image.svg".to_string(),
        },
        category_id: cat.unwrap_or(0),
    })
    .collect();

    let active: Vec<ActiveOrder> = sqlx::query_as::<_, (String, String, i64)>(
        "SELECT invoice_no, order_status, grand_total::bigint FROM orders \
         WHERE table_id=$1 AND order_status IN ('pending','cooking','served') ORDER BY created_at DESC",
    )
    .bind(table_id)
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(invoice_no, status, grand)| ActiveOrder { invoice_no, status, grand_fmt: fmt(grand) })
    .collect();

    let tax_rate: i64 = sqlx::query_scalar("SELECT COALESCE((SELECT tax_rate FROM settings LIMIT 1), 10)::bigint")
        .fetch_one(&state.pool)
        .await
        .unwrap_or(10);

    let mut ctx = tera::Context::new();
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);
    ctx.insert("uuid", &uuid);
    ctx.insert("table_number", &table_number);
    ctx.insert("customer_name", &name);
    ctx.insert("categories", &categories);
    ctx.insert("menus", &menus);
    ctx.insert("active_orders", &active);
    ctx.insert("tax_rate", &tax_rate);
    render(&state, "customer/menu.html", &ctx)
}

// ---------- CHECKOUT (publik, pay-later) ----------
#[derive(Deserialize)]
pub struct CartLine {
    id: i64, // menu id
    qty: i64,
    #[serde(default)]
    note: Option<String>,
}
#[derive(Deserialize)]
pub struct CheckoutPayload {
    #[serde(rename = "_token", default)]
    csrf: String,
    cart: Vec<CartLine>,
}

pub async fn checkout(State(state): State<AppState>, session: Session, Path(uuid): Path<String>, Json(payload): Json<CheckoutPayload>) -> Response {
    if !auth::verify_csrf(&session, &payload.csrf).await {
        return (StatusCode::FORBIDDEN, Json(json!({"error":"Sesi kedaluwarsa, muat ulang halaman."}))).into_response();
    }
    let Some((table_id, _tn, _st)) = find_table(&state, &uuid).await else {
        return (StatusCode::NOT_FOUND, Json(json!({"error":"Meja tidak ditemukan."}))).into_response();
    };
    let name = session.get::<String>(SESS_NAME).await.ok().flatten().unwrap_or_else(|| "Tamu".into());
    if payload.cart.is_empty() {
        return (StatusCode::BAD_REQUEST, Json(json!({"error":"Keranjang kosong."}))).into_response();
    }

    // Hitung subtotal dari harga DB (anti-manipulasi harga dari klien).
    let mut lines: Vec<(i64, i64, i64, Option<String>)> = Vec::new(); // (menu_id, qty, price, note)
    let mut subtotal: i64 = 0;
    for line in &payload.cart {
        let qty = line.qty.max(1);
        let price: Option<i64> = sqlx::query_scalar("SELECT price::bigint FROM menus WHERE id=$1 AND is_available=true")
            .bind(line.id)
            .fetch_optional(&state.pool)
            .await
            .unwrap_or(None);
        let Some(price) = price else { continue };
        subtotal += price * qty;
        lines.push((line.id, qty, price, line.note.clone().filter(|s| !s.trim().is_empty())));
    }
    if lines.is_empty() {
        return (StatusCode::BAD_REQUEST, Json(json!({"error":"Menu tidak tersedia."}))).into_response();
    }

    let tax_rate: i64 = sqlx::query_scalar("SELECT COALESCE((SELECT tax_rate FROM settings LIMIT 1), 10)::bigint")
        .fetch_one(&state.pool)
        .await
        .unwrap_or(10);
    let tax = (subtotal * tax_rate + 50) / 100;
    let grand_total = subtotal + tax;

    let tx = state.pool.begin().await;
    let Ok(mut tx) = tx else {
        return (StatusCode::INTERNAL_SERVER_ERROR, Json(json!({"error":"Gagal memproses pesanan."}))).into_response();
    };
    // Order: pay-later (payment_method NULL, unpaid), self-order pelanggan.
    let order_id: Result<(i64,), _> = sqlx::query_as(
        "INSERT INTO orders (uuid, invoice_no, table_id, promo_id, customer_name, order_type, subtotal, discount_amount, tax, grand_total, payment_method, payment_status, order_status, created_at, updated_at) \
         VALUES (gen_random_uuid(), \
            'INV-' || to_char(now(),'YYYYMMDDHH24MISS') || lpad((floor(random()*90)+10)::int::text, 2, '0'), \
            $1, NULL, $2, 'dine_in', $3::numeric, 0, $4::numeric, $5::numeric, NULL, 'unpaid', 'pending', now(), now()) RETURNING id",
    )
    .bind(table_id)
    .bind(&name)
    .bind(subtotal)
    .bind(tax)
    .bind(grand_total)
    .fetch_one(&mut *tx)
    .await;
    let Ok((order_id,)) = order_id else {
        return (StatusCode::INTERNAL_SERVER_ERROR, Json(json!({"error":"Gagal menyimpan pesanan."}))).into_response();
    };
    for (menu_id, qty, price, note) in &lines {
        let sub = price * qty;
        let _ = sqlx::query(
            "INSERT INTO order_details (order_id, menu_id, qty, price, subtotal, notes, status, created_at, updated_at) \
             VALUES ($1, $2, $3, $4::numeric, $5::numeric, $6, 'pending', now(), now())",
        )
        .bind(order_id)
        .bind(menu_id)
        .bind(qty)
        .bind(price)
        .bind(sub)
        .bind(note)
        .execute(&mut *tx)
        .await;
    }
    let _ = sqlx::query("UPDATE tables SET status='occupied' WHERE id=$1").bind(table_id).execute(&mut *tx).await;
    if tx.commit().await.is_err() {
        return (StatusCode::INTERNAL_SERVER_ERROR, Json(json!({"error":"Gagal menyimpan pesanan."}))).into_response();
    }
    Json(json!({"type":"pay_later","redirect":format!("/order-success/{uuid}")})).into_response()
}

pub async fn success_page(State(state): State<AppState>, session: Session, Path(uuid): Path<String>) -> Response {
    let table_number = find_table(&state, &uuid).await.map(|t| t.1).unwrap_or_default();
    let name = session.get::<String>(SESS_NAME).await.ok().flatten().unwrap_or_default();
    let mut ctx = tera::Context::new();
    ctx.insert("uuid", &uuid);
    ctx.insert("table_number", &table_number);
    ctx.insert("customer_name", &name);
    render(&state, "customer/success.html", &ctx)
}

// ---------- QR PRINT (admin) ----------
pub async fn print_qr(user: CurrentUser, State(state): State<AppState>, headers: HeaderMap, Path(id): Path<i64>) -> Response {
    if !user.can("view_data_master") {
        return (StatusCode::FORBIDDEN, Html("<h3>403 — butuh izin <code>view_data_master</code></h3>".to_string())).into_response();
    }
    let row: Option<(String, String)> = sqlx::query_as("SELECT table_number, uuid::text FROM tables WHERE id=$1")
        .bind(id)
        .fetch_optional(&state.pool)
        .await
        .ok()
        .flatten();
    let Some((table_number, uuid)) = row else {
        return not_found("Meja tidak ditemukan");
    };
    let host = headers.get("host").and_then(|v| v.to_str().ok()).unwrap_or("127.0.0.1:8088");
    let url = format!("http://{host}/scan/{uuid}");
    let svg = qrcode::QrCode::new(url.as_bytes())
        .map(|c| c.render::<qrcode::render::svg::Color>().min_dimensions(260, 260).quiet_zone(true).build())
        .unwrap_or_else(|_| "<p>Gagal membuat QR</p>".into());
    let mut ctx = tera::Context::new();
    ctx.insert("table_number", &table_number);
    ctx.insert("url", &url);
    ctx.insert("qr_svg", &svg);
    render(&state, "customer/qr.html", &ctx)
}
