use axum::{
    extract::{Form, Path, Query, State},
    http::StatusCode,
    response::{Html, IntoResponse, Redirect, Response},
};
use serde::{Deserialize, Serialize};
use tower_sessions::Session;

use crate::{auth, format_rupiah as fmt, rbac::CurrentUser, view, AppState};

// ---------- util ----------

fn slugify(s: &str) -> String {
    let mut out = String::new();
    let mut prev_dash = false;
    for c in s.to_lowercase().chars() {
        if c.is_alphanumeric() {
            out.push(c);
            prev_dash = false;
        } else if !prev_dash {
            out.push('-');
            prev_dash = true;
        }
    }
    let t = out.trim_matches('-').to_string();
    if t.is_empty() { "item".into() } else { t }
}

fn forbidden() -> Response {
    (StatusCode::FORBIDDEN, Html("<h3>403 — butuh izin <code>view_data_master</code></h3>".to_string())).into_response()
}

fn render(state: &AppState, name: &str, ctx: &tera::Context) -> Response {
    match state.tera.render(name, ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}

#[derive(Deserialize, Default)]
pub struct Flash {
    #[serde(default)]
    ok: Option<String>,
    #[serde(default)]
    err: Option<String>,
}
fn flash_ok(c: Option<&str>) -> Option<&'static str> {
    match c {
        Some("saved") => Some("Data berhasil disimpan."),
        Some("updated") => Some("Data berhasil diperbarui."),
        Some("deleted") => Some("Data berhasil dihapus."),
        _ => None,
    }
}
fn flash_err(c: Option<&str>) -> Option<&'static str> {
    match c {
        Some("csrf") => Some("Sesi kedaluwarsa, muat ulang halaman."),
        Some("fail") => Some("Gagal menyimpan — periksa input (mis. nama/slug duplikat)."),
        Some("used") => Some("Tidak bisa dihapus: data sedang dipakai (ada relasi)."),
        _ => None,
    }
}

async fn ctx_with_flash(state: &AppState, user: &CurrentUser, active: &str, session: &Session, f: &Flash) -> tera::Context {
    let mut ctx = view::base_context(state, user, active).await;
    ctx.insert("csrf_token", &auth::ensure_csrf(session).await);
    ctx.insert("flash_ok", &flash_ok(f.ok.as_deref()));
    ctx.insert("flash_err", &flash_err(f.err.as_deref()));
    ctx
}

// ================= CATEGORIES =================
#[derive(Serialize)]
struct CategoryRow {
    id: i64,
    name: String,
    slug: String,
}
#[derive(Deserialize)]
pub struct CategoryForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    name: String,
}

pub async fn categories_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    let rows: Vec<CategoryRow> = sqlx::query_as::<_, (i64, String, String)>("SELECT id, name, slug FROM categories ORDER BY name")
        .fetch_all(&state.pool)
        .await
        .unwrap_or_default()
        .into_iter()
        .map(|(id, name, slug)| CategoryRow { id, name, slug })
        .collect();
    let mut ctx = ctx_with_flash(&state, &user, "master", &session, &f).await;
    ctx.insert("rows", &rows);
    render(&state, "master/categories.html", &ctx)
}

pub async fn category_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<CategoryForm>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/categories?err=csrf").into_response();
    }
    let r = sqlx::query("INSERT INTO categories (name, slug, created_at, updated_at) VALUES ($1, $2, now(), now())")
        .bind(form.name.trim())
        .bind(slugify(&form.name))
        .execute(&state.pool)
        .await;
    redirect_master("/admin/categories", r.is_ok(), "saved")
}

pub async fn category_update(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CategoryForm>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/categories?err=csrf").into_response();
    }
    let r = sqlx::query("UPDATE categories SET name=$1, slug=$2, updated_at=now() WHERE id=$3")
        .bind(form.name.trim())
        .bind(slugify(&form.name))
        .bind(id)
        .execute(&state.pool)
        .await;
    redirect_master("/admin/categories", r.is_ok(), "updated")
}

pub async fn category_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/categories?err=csrf").into_response();
    }
    let used: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM menus WHERE category_id=$1)").bind(id).fetch_one(&state.pool).await.unwrap_or(false);
    if used {
        return Redirect::to("/admin/categories?err=used").into_response();
    }
    let r = sqlx::query("DELETE FROM categories WHERE id=$1").bind(id).execute(&state.pool).await;
    redirect_master("/admin/categories", r.is_ok(), "deleted")
}

// ================= MENUS =================
#[derive(Serialize)]
struct MenuRow {
    id: i64,
    name: String,
    category_id: i64,
    category_name: String,
    price: i64,
    price_fmt: String,
    description: Option<String>,
    is_available: bool,
}
#[derive(Deserialize)]
pub struct MenuForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    category_id: i64,
    name: String,
    #[serde(default)]
    description: Option<String>,
    price: i64,
    #[serde(default)]
    is_available: Option<String>, // checkbox: "on" bila dicentang
}

pub async fn menus_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    let rows: Vec<MenuRow> = sqlx::query_as::<_, (i64, String, i64, Option<String>, i64, Option<String>, bool)>(
        "SELECT m.id, m.name, m.category_id, c.name, m.price::bigint, m.description, m.is_available \
         FROM menus m LEFT JOIN categories c ON c.id=m.category_id ORDER BY m.name",
    )
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name, category_id, cat, price, description, is_available)| MenuRow {
        id,
        name,
        category_id,
        category_name: cat.unwrap_or_else(|| "-".into()),
        price,
        price_fmt: fmt(price),
        description,
        is_available,
    })
    .collect();
    let cats: Vec<(i64, String)> = sqlx::query_as("SELECT id, name FROM categories ORDER BY name").fetch_all(&state.pool).await.unwrap_or_default();
    let categories: Vec<CategoryRow> = cats.into_iter().map(|(id, name)| CategoryRow { id, name, slug: String::new() }).collect();

    let mut ctx = ctx_with_flash(&state, &user, "master", &session, &f).await;
    ctx.insert("rows", &rows);
    ctx.insert("categories", &categories);
    render(&state, "master/menus.html", &ctx)
}

pub async fn menu_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<MenuForm>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/menus?err=csrf").into_response();
    }
    let r = sqlx::query(
        "INSERT INTO menus (uuid, category_id, name, description, price, image, is_available, discount_percent, created_at, updated_at) \
         VALUES (gen_random_uuid(), $1, $2, $3, $4::numeric, NULL, $5, 0, now(), now())",
    )
    .bind(form.category_id)
    .bind(form.name.trim())
    .bind(form.description.as_deref().filter(|s| !s.is_empty()))
    .bind(form.price)
    .bind(form.is_available.is_some())
    .execute(&state.pool)
    .await;
    redirect_master("/admin/menus", r.is_ok(), "saved")
}

pub async fn menu_update(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<MenuForm>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/menus?err=csrf").into_response();
    }
    let r = sqlx::query(
        "UPDATE menus SET category_id=$1, name=$2, description=$3, price=$4::numeric, is_available=$5, updated_at=now() WHERE id=$6",
    )
    .bind(form.category_id)
    .bind(form.name.trim())
    .bind(form.description.as_deref().filter(|s| !s.is_empty()))
    .bind(form.price)
    .bind(form.is_available.is_some())
    .bind(id)
    .execute(&state.pool)
    .await;
    redirect_master("/admin/menus", r.is_ok(), "updated")
}

pub async fn menu_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/menus?err=csrf").into_response();
    }
    let used: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM order_details WHERE menu_id=$1)").bind(id).fetch_one(&state.pool).await.unwrap_or(false);
    if used {
        return Redirect::to("/admin/menus?err=used").into_response();
    }
    let r = sqlx::query("DELETE FROM menus WHERE id=$1").bind(id).execute(&state.pool).await;
    redirect_master("/admin/menus", r.is_ok(), "deleted")
}

// ================= TABLES (meja) =================
#[derive(Serialize)]
struct TableRow {
    id: i64,
    table_number: String,
    capacity: i64,
    status: String,
}
#[derive(Deserialize)]
pub struct TableForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    table_number: String,
    capacity: i64,
    status: String,
}

pub async fn tables_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    let rows: Vec<TableRow> = sqlx::query_as::<_, (i64, String, i64, String)>("SELECT id, table_number, capacity::bigint, status FROM tables ORDER BY table_number")
        .fetch_all(&state.pool)
        .await
        .unwrap_or_default()
        .into_iter()
        .map(|(id, table_number, capacity, status)| TableRow { id, table_number, capacity, status })
        .collect();
    let mut ctx = ctx_with_flash(&state, &user, "master", &session, &f).await;
    ctx.insert("rows", &rows);
    render(&state, "master/tables.html", &ctx)
}

pub async fn table_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<TableForm>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/tables?err=csrf").into_response();
    }
    let r = sqlx::query("INSERT INTO tables (uuid, table_number, capacity, status, created_at, updated_at) VALUES (gen_random_uuid(), $1, $2, $3, now(), now())")
        .bind(form.table_number.trim())
        .bind(form.capacity)
        .bind(&form.status)
        .execute(&state.pool)
        .await;
    redirect_master("/admin/tables", r.is_ok(), "saved")
}

pub async fn table_update(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<TableForm>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/tables?err=csrf").into_response();
    }
    let r = sqlx::query("UPDATE tables SET table_number=$1, capacity=$2, status=$3, updated_at=now() WHERE id=$4")
        .bind(form.table_number.trim())
        .bind(form.capacity)
        .bind(&form.status)
        .bind(id)
        .execute(&state.pool)
        .await;
    redirect_master("/admin/tables", r.is_ok(), "updated")
}

pub async fn table_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can("view_data_master") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/tables?err=csrf").into_response();
    }
    let used: bool = sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM orders WHERE table_id=$1)").bind(id).fetch_one(&state.pool).await.unwrap_or(false);
    if used {
        return Redirect::to("/admin/tables?err=used").into_response();
    }
    let r = sqlx::query("DELETE FROM tables WHERE id=$1").bind(id).execute(&state.pool).await;
    redirect_master("/admin/tables", r.is_ok(), "deleted")
}

#[derive(Deserialize)]
pub struct CsrfOnly {
    #[serde(rename = "_token", default)]
    csrf: String,
}

fn redirect_master(base: &str, ok: bool, ok_code: &str) -> Response {
    let target = if ok {
        format!("{base}?ok={ok_code}")
    } else {
        format!("{base}?err=fail")
    };
    Redirect::to(&target).into_response()
}
