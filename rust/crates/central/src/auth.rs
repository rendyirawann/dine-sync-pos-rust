use std::net::SocketAddr;

use axum::{
    extract::{ConnectInfo, Form, Request, State},
    http::{HeaderMap, StatusCode},
    middleware::Next,
    response::{Html, IntoResponse, Redirect, Response},
    Json,
};
use serde::Deserialize;
use serde_json::json;
use sqlx::types::Uuid;
use tower_sessions::Session;

use crate::AppState;

pub const SESSION_USER_KEY: &str = "user_id";
pub const SESSION_CSRF_KEY: &str = "csrf_token";

/// Ambil token CSRF dari session; buat baru bila belum ada.
pub async fn ensure_csrf(session: &Session) -> String {
    if let Ok(Some(tok)) = session.get::<String>(SESSION_CSRF_KEY).await {
        return tok;
    }
    let tok = Uuid::new_v4().to_string();
    let _ = session.insert(SESSION_CSRF_KEY, &tok).await;
    tok
}

/// Verifikasi token CSRF dari form terhadap yang tersimpan di session.
pub async fn verify_csrf(session: &Session, token: &str) -> bool {
    !token.is_empty()
        && matches!(session.get::<String>(SESSION_CSRF_KEY).await, Ok(Some(t)) if t == token)
}

#[derive(Deserialize)]
pub struct LoginForm {
    pub email: String,
    pub password: String,
    #[serde(rename = "_token", default)]
    pub csrf: String,
    #[serde(default)]
    pub remember: Option<String>,
}

#[derive(sqlx::FromRow)]
struct AuthUser {
    id: Uuid,
    #[allow(dead_code)]
    name: String,
    password: String,
    banned_at: Option<sqlx::types::chrono::NaiveDateTime>,
    is_active: bool,
}

/// GET /admin/login — tampilkan halaman login (atau redirect bila sudah login).
pub async fn login_page(State(state): State<AppState>, session: Session) -> Response {
    if is_logged_in(&session).await {
        return Redirect::to("/admin/dashboard").into_response();
    }
    let mut ctx = tera::Context::new();
    ctx.insert("csrf_token", &ensure_csrf(&session).await);
    match state.tera.render("auth/login.html", &ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}

/// POST /admin/login — autentikasi multi-field + bcrypt, dengan CSRF & rate-limit.
pub async fn login_submit(
    State(state): State<AppState>,
    session: Session,
    ConnectInfo(addr): ConnectInfo<SocketAddr>,
    headers: HeaderMap,
    Form(form): Form<LoginForm>,
) -> Response {
    // 1. CSRF
    if !verify_csrf(&session, &form.csrf).await {
        return error_json(
            StatusCode::from_u16(419).unwrap_or(StatusCode::FORBIDDEN),
            "Sesi kedaluwarsa, silakan muat ulang halaman.",
        );
    }

    let login = form.email.trim();
    let ip = addr.ip().to_string();
    let key = format!("{}|{}", login.to_lowercase(), ip);

    // 2. Sedang dalam masa lockout?
    if let Some(secs) = state.limiter.locked_seconds(&key) {
        return lockout_json(secs);
    }

    // 3. Deteksi field login (email / no_wa / name) seperti LoginRequest Laravel.
    let user = if login.contains('@') {
        find_user(&state, "SELECT id, name, password, banned_at, is_active FROM users WHERE email = $1", login).await
    } else if !login.is_empty() && login.chars().all(|c| c.is_ascii_digit()) {
        find_user(&state, "SELECT id, name, password, banned_at, is_active FROM users WHERE no_wa = $1", login).await
    } else {
        find_user(&state, "SELECT id, name, password, banned_at, is_active FROM users WHERE name = $1", login).await
    };

    let Some(user) = user else {
        return invalid_credentials(&state, &key);
    };
    if !bcrypt::verify(&form.password, &user.password).unwrap_or(false) {
        return invalid_credentials(&state, &key);
    }

    // 4. Password benar → reset limiter.
    state.limiter.clear(&key);

    if user.banned_at.is_some() {
        return error_json(StatusCode::FORBIDDEN, "Akun Anda telah dibekukan.");
    }
    if !user.is_active {
        return error_json(StatusCode::FORBIDDEN, "Akun Anda tidak aktif.");
    }

    // 5. Update last login + catat activity log.
    let ua = headers
        .get("user-agent")
        .and_then(|v| v.to_str().ok())
        .unwrap_or("")
        .to_string();
    let _ = sqlx::query("UPDATE users SET last_ip = $1, last_login = now() WHERE id = $2")
        .bind(&ip)
        .bind(user.id)
        .execute(&state.pool)
        .await;
    log_activity(&state, "login", "Login berhasil", user.id, &ip, &ua).await;

    // 6. Simpan session.
    if session.insert(SESSION_USER_KEY, user.id.to_string()).await.is_err() {
        return error_json(StatusCode::INTERNAL_SERVER_ERROR, "Gagal membuat sesi.");
    }

    Json(json!({
        "status": "success",
        "message": "Login berhasil, mengalihkan...",
        "redirect": "/admin/dashboard"
    }))
    .into_response()
}

/// GET/POST /admin/logout — catat log lalu hapus session.
pub async fn logout(
    State(state): State<AppState>,
    session: Session,
    ConnectInfo(addr): ConnectInfo<SocketAddr>,
    headers: HeaderMap,
) -> Response {
    if let Ok(Some(uid)) = session.get::<String>(SESSION_USER_KEY).await {
        if let Ok(user_id) = Uuid::parse_str(&uid) {
            let ip = addr.ip().to_string();
            let ua = headers
                .get("user-agent")
                .and_then(|v| v.to_str().ok())
                .unwrap_or("")
                .to_string();
            log_activity(&state, "logout", "Logout berhasil", user_id, &ip, &ua).await;
        }
    }
    let _ = session.flush().await;
    Redirect::to("/admin/login").into_response()
}

/// Middleware: tolak akses bila belum login.
pub async fn require_auth(session: Session, req: Request, next: Next) -> Response {
    if is_logged_in(&session).await {
        next.run(req).await
    } else {
        Redirect::to("/admin/login").into_response()
    }
}

async fn is_logged_in(session: &Session) -> bool {
    matches!(session.get::<String>(SESSION_USER_KEY).await, Ok(Some(_)))
}

async fn find_user(state: &AppState, sql: &'static str, value: &str) -> Option<AuthUser> {
    sqlx::query_as::<_, AuthUser>(sql)
        .bind(value)
        .fetch_optional(&state.pool)
        .await
        .ok()
        .flatten()
}

/// Catat ke tabel activity_log (spatie/activitylog) — versi ringkas.
async fn log_activity(
    state: &AppState,
    log_name: &str,
    description: &str,
    user_id: Uuid,
    ip: &str,
    user_agent: &str,
) {
    let props = json!({ "ip": ip, "agent": { "raw": user_agent } }).to_string();
    let _ = sqlx::query(
        "INSERT INTO activity_log (log_name, event, description, causer_type, causer_id, properties, created_at, updated_at) \
         VALUES ($1, NULL, $2, $3, $4, $5::json, now(), now())",
    )
    .bind(log_name)
    .bind(description)
    .bind("App\\Models\\User")
    .bind(user_id)
    .bind(props)
    .execute(&state.pool)
    .await;
}

fn invalid_credentials(state: &AppState, key: &str) -> Response {
    match state.limiter.record_failure(key) {
        Some(secs) => lockout_json(secs),
        None => error_json(StatusCode::UNPROCESSABLE_ENTITY, "Akun atau Password salah."),
    }
}

fn lockout_json(secs: u64) -> Response {
    (
        StatusCode::TOO_MANY_REQUESTS,
        Json(json!({
            "errors": {
                "email": [format!("Terlalu banyak percobaan. Tunggu {secs} detik.")],
                "seconds": [secs]
            }
        })),
    )
        .into_response()
}

fn error_json(status: StatusCode, msg: &str) -> Response {
    (status, Json(json!({ "errors": { "email": [msg] } }))).into_response()
}
