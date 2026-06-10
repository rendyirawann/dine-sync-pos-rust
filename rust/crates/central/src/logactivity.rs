use axum::{
    extract::State,
    response::{Html, IntoResponse, Response},
};
use serde::Serialize;
use tower_sessions::Session;

use crate::{rbac::CurrentUser, view, AppState};

#[derive(Serialize)]
struct LogRow {
    log_name: String,
    description: String,
    causer: String,
    ip: String,
    agent: String,
    time: String,
}

/// Viewer activity_log (spatie). Superadmin lihat semua, lainnya hanya aktivitasnya sendiri.
pub async fn index(user: CurrentUser, State(state): State<AppState>, _session: Session) -> Response {
    const SQL_ALL: &str = "SELECT a.log_name, a.description, COALESCE(u.name,'System'), \
                a.properties->>'ip', a.properties->'agent'->>'raw', \
                to_char(a.created_at,'DD Mon YYYY HH24:MI:SS') \
         FROM activity_log a LEFT JOIN users u ON u.id = a.causer_id \
         ORDER BY a.id DESC LIMIT 300";
    const SQL_OWN: &str = "SELECT a.log_name, a.description, COALESCE(u.name,'System'), \
                a.properties->>'ip', a.properties->'agent'->>'raw', \
                to_char(a.created_at,'DD Mon YYYY HH24:MI:SS') \
         FROM activity_log a LEFT JOIN users u ON u.id = a.causer_id \
         WHERE a.causer_id = $1 ORDER BY a.id DESC LIMIT 300";

    let raw: Vec<(Option<String>, String, Option<String>, Option<String>, Option<String>, Option<String>)> = if user.is_superadmin() {
        sqlx::query_as(SQL_ALL).fetch_all(&state.pool).await.unwrap_or_default()
    } else {
        sqlx::query_as(SQL_OWN).bind(user.id).fetch_all(&state.pool).await.unwrap_or_default()
    };

    let rows: Vec<LogRow> = raw
        .into_iter()
        .map(|(log_name, description, causer, ip, agent, time)| LogRow {
            log_name: log_name.unwrap_or_else(|| "default".into()),
            description,
            causer: causer.unwrap_or_else(|| "System".into()),
            ip: ip.unwrap_or_else(|| "-".into()),
            agent: agent.unwrap_or_else(|| "-".into()),
            time: time.unwrap_or_default(),
        })
        .collect();

    let mut ctx = view::base_context(&state, &user, "log").await;
    ctx.insert("rows", &rows);
    ctx.insert("is_superadmin", &user.is_superadmin());
    match state.tera.render("help/log_activity.html", &ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}
