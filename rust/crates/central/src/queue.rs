use axum::{
    extract::{Form, Path, State},
    http::StatusCode,
    response::{Html, IntoResponse, Redirect, Response},
};
use serde::{Deserialize, Serialize};
use tower_sessions::Session;

use crate::{auth, rbac::CurrentUser, view, AppState};

const GATE: &str = "view_queue";

fn forbidden() -> Response {
    (StatusCode::FORBIDDEN, Html("<h3>403 — butuh izin <code>view_queue</code></h3>".to_string())).into_response()
}
fn render(state: &AppState, name: &str, ctx: &tera::Context) -> Response {
    match state.tera.render(name, ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}

#[derive(Serialize)]
struct QueueRow {
    id: i64,
    queue_number: String,
    customer_name: String,
    pax: i64,
    status: String,
    time: String,
}

/// Ambil antrian HARI INI (status non-final dulu) untuk dashboard admin.
async fn today_queues(state: &AppState) -> Vec<QueueRow> {
    sqlx::query_as::<_, (i64, String, String, i64, String, Option<String>)>(
        "SELECT id, queue_number, customer_name, pax::bigint, status, to_char(created_at,'HH24:MI') \
         FROM queues WHERE created_at::date = current_date \
         ORDER BY CASE status WHEN 'waiting' THEN 0 WHEN 'called' THEN 1 WHEN 'seated' THEN 2 ELSE 3 END, id",
    )
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, queue_number, customer_name, pax, status, time)| QueueRow {
        id,
        queue_number,
        customer_name,
        pax,
        status,
        time: time.unwrap_or_default(),
    })
    .collect()
}

// ---------- ADMIN ----------
#[derive(Deserialize, Default)]
pub struct AdminFlash {
    #[serde(default)]
    ok: Option<String>,
}
pub async fn admin_index(user: CurrentUser, State(state): State<AppState>, session: Session, axum::extract::Query(f): axum::extract::Query<AdminFlash>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    let rows = today_queues(&state).await;
    let waiting = rows.iter().filter(|r| r.status == "waiting").count();
    let mut ctx = view::base_context(&state, &user, "queue").await;
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);
    ctx.insert("rows", &rows);
    ctx.insert("waiting_count", &waiting);
    ctx.insert("called", &f.ok);
    render(&state, "queue/admin.html", &ctx)
}

#[derive(Deserialize)]
pub struct StatusForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    status: String,
}
pub async fn admin_status(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<StatusForm>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/queues").into_response();
    }
    let status = match form.status.as_str() {
        "called" | "seated" | "cancelled" | "waiting" => form.status.as_str(),
        _ => return Redirect::to("/admin/queues").into_response(),
    };
    let _ = sqlx::query("UPDATE queues SET status=$1, updated_at=now() WHERE id=$2")
        .bind(status)
        .bind(id)
        .execute(&state.pool)
        .await;
    // Saat memanggil: broadcast ke layar TV (TTS + update kartu).
    if status == "called" {
        let row: Option<(String, String)> = sqlx::query_as("SELECT queue_number, customer_name FROM queues WHERE id=$1").bind(id).fetch_optional(&state.pool).await.unwrap_or(None);
        if let Some((n, name)) = row {
            crate::realtime::call_queue(&state, &n, &name);
            return Redirect::to(&format!("/admin/queues?ok={n}")).into_response();
        }
    }
    Redirect::to("/admin/queues").into_response()
}

// ---------- KIOSK (publik) ----------
pub async fn kiosk_page(State(state): State<AppState>, session: Session) -> Response {
    let mut ctx = tera::Context::new();
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);
    ctx.insert("taken", &Option::<String>::None);
    render(&state, "queue/kiosk.html", &ctx)
}

#[derive(Deserialize)]
pub struct TakeForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    customer_name: String,
    #[serde(default)]
    pax: i64,
}
pub async fn kiosk_take(State(state): State<AppState>, session: Session, Form(form): Form<TakeForm>) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/kiosk").into_response();
    }
    let name = form.customer_name.trim();
    let pax = form.pax.max(1);
    if name.is_empty() {
        return Redirect::to("/kiosk").into_response();
    }
    // Prefix berdasarkan jumlah tamu: A (1-2), B (3-4), C (5+).
    let prefix = if pax <= 2 { "A" } else if pax <= 4 { "B" } else { "C" };
    // Nomor urut per-prefix per-hari.
    let next: i64 = sqlx::query_scalar(
        "SELECT COALESCE(MAX(substring(queue_number from 2)::int), 0)::bigint + 1 \
         FROM queues WHERE created_at::date = current_date AND queue_number LIKE $1",
    )
    .bind(format!("{prefix}%"))
    .fetch_one(&state.pool)
    .await
    .unwrap_or(1);
    let queue_number = format!("{prefix}{:03}", next);
    let r = sqlx::query("INSERT INTO queues (queue_number, customer_name, pax, status, created_at, updated_at) VALUES ($1,$2,$3,'waiting',now(),now())")
        .bind(&queue_number)
        .bind(name)
        .bind(pax)
        .execute(&state.pool)
        .await;
    if r.is_ok() {
        crate::realtime::new_queue(&state); // beri tahu layar TV & dashboard antrian
    }
    let mut ctx = tera::Context::new();
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);
    ctx.insert("taken", &if r.is_ok() { Some(queue_number) } else { None });
    ctx.insert("taken_name", name);
    render(&state, "queue/kiosk.html", &ctx)
}

// ---------- DISPLAY TV (publik) ----------
pub async fn display_page(State(state): State<AppState>) -> Response {
    let rows = today_queues(&state).await;
    let called: Vec<&QueueRow> = rows.iter().filter(|r| r.status == "called").collect();
    let current = called.first().map(|r| r.queue_number.clone()).unwrap_or_else(|| "—".into());
    let waiting: Vec<&QueueRow> = rows.iter().filter(|r| r.status == "waiting").take(8).collect();
    let mut ctx = tera::Context::new();
    ctx.insert("current", &current);
    ctx.insert("waiting", &waiting);
    render(&state, "queue/display.html", &ctx)
}
