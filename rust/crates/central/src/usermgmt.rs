use axum::{
    extract::{Form, Path, Query, State},
    http::StatusCode,
    response::{Html, IntoResponse, Redirect, Response},
};
use serde::{Deserialize, Serialize};
use tower_sessions::Session;

use crate::{auth, rbac::CurrentUser, view, AppState};

const GATE: &str = "view_resources";
const USER_MODEL: &str = "App\\Models\\User";

// ---------- util ----------
fn forbidden() -> Response {
    (StatusCode::FORBIDDEN, Html("<h3>403 — butuh izin <code>view_resources</code></h3>".to_string())).into_response()
}
fn render(state: &AppState, name: &str, ctx: &tera::Context) -> Response {
    match state.tera.render(name, ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}
fn redirect(base: &str, ok: bool, ok_code: &str, err_code: &str) -> Response {
    let target = if ok { format!("{base}?ok={ok_code}") } else { format!("{base}?err={err_code}") };
    Redirect::to(&target).into_response()
}
/// Parse CSV id list ("1,3,5") menjadi Vec<i64>, abaikan token kosong/invalid.
fn parse_ids(csv: &str) -> Vec<i64> {
    csv.split(',').filter_map(|t| t.trim().parse::<i64>().ok()).collect()
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
        Some("fail") => Some("Gagal menyimpan — periksa input (mis. username/email duplikat)."),
        Some("required") => Some("Lengkapi semua kolom wajib (nama, username, email, password, role)."),
        Some("used") => Some("Tidak bisa dihapus: masih dipakai (role terpasang ke user)."),
        Some("self") => Some("Tidak bisa menghapus akun Anda sendiri."),
        Some("protected") => Some("Akun/role Superadmin dilindungi dan tidak bisa dihapus."),
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

// ===================== USERS =====================
#[derive(Serialize)]
struct UserRow {
    id: String,
    name: String,
    username: String,
    email: String,
    no_wa: String,
    is_active: bool,
    last_login: String,
    roles: String,
    role_ids: String,
}
#[derive(Deserialize)]
pub struct UserForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    #[serde(default)]
    name: String,
    #[serde(default)]
    username: String,
    #[serde(default)]
    no_wa: String,
    #[serde(default)]
    email: String,
    #[serde(default)]
    password: String,
    #[serde(default)]
    roles: String, // CSV role id
}

pub async fn users_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    let rows: Vec<UserRow> = sqlx::query_as::<_, (String, String, String, String, Option<String>, bool, Option<String>, Option<String>, Option<String>)>(
        "SELECT u.id::text, u.name, u.username, u.email, u.no_wa, u.is_active, \
                to_char(u.last_login,'DD Mon YYYY HH24:MI'), \
                string_agg(r.name, ', ' ORDER BY r.name), \
                string_agg(mhr.role_id::text, ',') \
         FROM users u \
         LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id \
         LEFT JOIN roles r ON r.id = mhr.role_id \
         GROUP BY u.id, u.name, u.username, u.email, u.no_wa, u.is_active, u.last_login, u.created_at \
         ORDER BY u.created_at DESC NULLS LAST",
    )
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name, username, email, no_wa, is_active, last_login, roles, role_ids)| UserRow {
        id,
        name,
        username,
        email,
        no_wa: no_wa.unwrap_or_default(),
        is_active,
        last_login: last_login.unwrap_or_else(|| "Belum pernah".into()),
        roles: roles.filter(|s| !s.is_empty()).unwrap_or_else(|| "—".into()),
        role_ids: role_ids.unwrap_or_default(),
    })
    .collect();

    let all_roles: Vec<(i64, String)> = sqlx::query_as("SELECT id, name FROM roles ORDER BY name")
        .fetch_all(&state.pool)
        .await
        .unwrap_or_default();
    let roles: Vec<RoleOpt> = all_roles.into_iter().map(|(id, name)| RoleOpt { id, name }).collect();

    let mut ctx = ctx_with_flash(&state, &user, "resources", &session, &f).await;
    ctx.insert("rows", &rows);
    ctx.insert("all_roles", &roles);
    render(&state, "usermgmt/users.html", &ctx)
}
#[derive(Serialize)]
struct RoleOpt {
    id: i64,
    name: String,
}

pub async fn user_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<UserForm>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/users", false, "", "csrf");
    }
    let (name, username, email, pwd) = (form.name.trim(), form.username.trim(), form.email.trim(), form.password.trim());
    let role_ids = parse_ids(&form.roles);
    if name.is_empty() || username.is_empty() || email.is_empty() || pwd.is_empty() || role_ids.is_empty() {
        return redirect("/admin/users", false, "", "required");
    }
    let hash = match bcrypt::hash(pwd, bcrypt::DEFAULT_COST) {
        Ok(h) => h,
        Err(_) => return redirect("/admin/users", false, "", "fail"),
    };
    let no_wa = form.no_wa.trim();
    let new_id: Result<(String,), _> = sqlx::query_as(
        "INSERT INTO users (id, name, username, email, no_wa, password, is_active, created_at, updated_at) \
         VALUES (gen_random_uuid(), $1, $2, $3, $4, $5, true, now(), now()) RETURNING id::text",
    )
    .bind(name)
    .bind(username)
    .bind(email)
    .bind(if no_wa.is_empty() { None } else { Some(no_wa) })
    .bind(&hash)
    .fetch_one(&state.pool)
    .await;
    let uid = match new_id {
        Ok((id,)) => id,
        Err(_) => return redirect("/admin/users", false, "", "fail"),
    };
    assign_roles(&state, &uid, &role_ids).await;
    redirect("/admin/users", true, "saved", "")
}

pub async fn user_update(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<String>, Form(form): Form<UserForm>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/users", false, "", "csrf");
    }
    let (name, email) = (form.name.trim(), form.email.trim());
    let role_ids = parse_ids(&form.roles);
    if name.is_empty() || email.is_empty() || role_ids.is_empty() {
        return redirect("/admin/users", false, "", "required");
    }
    let no_wa = form.no_wa.trim();
    let pwd = form.password.trim();
    // Update kolom dasar.
    let r = sqlx::query("UPDATE users SET name=$1, email=$2, no_wa=$3, updated_at=now() WHERE id::text=$4")
        .bind(name)
        .bind(email)
        .bind(if no_wa.is_empty() { None } else { Some(no_wa) })
        .bind(&id)
        .execute(&state.pool)
        .await;
    if r.is_err() {
        return redirect("/admin/users", false, "", "fail");
    }
    // Ganti password hanya bila diisi.
    if !pwd.is_empty() {
        if let Ok(h) = bcrypt::hash(pwd, bcrypt::DEFAULT_COST) {
            let _ = sqlx::query("UPDATE users SET password=$1, updated_at=now() WHERE id::text=$2")
                .bind(&h)
                .bind(&id)
                .execute(&state.pool)
                .await;
        }
    }
    // Sync role.
    let _ = sqlx::query("DELETE FROM model_has_roles WHERE model_id::text=$1").bind(&id).execute(&state.pool).await;
    assign_roles(&state, &id, &role_ids).await;
    redirect("/admin/users", true, "updated", "")
}

pub async fn user_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<String>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/users", false, "", "csrf");
    }
    if id == user.id.to_string() {
        return redirect("/admin/users", false, "", "self");
    }
    // Lindungi akun superadmin.
    let uname: Option<String> = sqlx::query_scalar("SELECT username FROM users WHERE id::text=$1").bind(&id).fetch_optional(&state.pool).await.unwrap_or(None);
    if uname.as_deref() == Some("superadmin") {
        return redirect("/admin/users", false, "", "protected");
    }
    let _ = sqlx::query("DELETE FROM model_has_roles WHERE model_id::text=$1").bind(&id).execute(&state.pool).await;
    let r = sqlx::query("DELETE FROM users WHERE id::text=$1").bind(&id).execute(&state.pool).await;
    redirect("/admin/users", r.is_ok(), "deleted", "fail")
}

async fn assign_roles(state: &AppState, user_id: &str, role_ids: &[i64]) {
    for rid in role_ids {
        let _ = sqlx::query("INSERT INTO model_has_roles (role_id, model_type, model_id) VALUES ($1, $2, $3::uuid) ON CONFLICT DO NOTHING")
            .bind(rid)
            .bind(USER_MODEL)
            .bind(user_id)
            .execute(&state.pool)
            .await;
    }
}

// ===================== ROLES =====================
#[derive(Serialize)]
struct RoleRow {
    id: i64,
    name: String,
    guard_name: String,
    perm_count: i64,
    perm_ids: String,
    protected: bool,
}
#[derive(Serialize)]
struct PermItem {
    id: i64,
    name: String,
}
#[derive(Serialize)]
struct PermGroup {
    group: String,
    items: Vec<PermItem>,
}
#[derive(Deserialize)]
pub struct RoleForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    #[serde(default)]
    name: String,
    #[serde(default)]
    permissions: String, // CSV permission id
}
#[derive(Deserialize)]
pub struct CsrfOnly {
    #[serde(rename = "_token", default)]
    csrf: String,
}

fn perm_group(name: &str) -> String {
    let cut = name.find('.').or_else(|| name.find('_'));
    match cut {
        Some(i) => name[..i].to_string(),
        None => "umum".to_string(),
    }
}

pub async fn roles_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    let rows: Vec<RoleRow> = sqlx::query_as::<_, (i64, String, String, i64, Option<String>)>(
        "SELECT r.id, r.name, r.guard_name, COUNT(rhp.permission_id)::bigint, string_agg(rhp.permission_id::text, ',') \
         FROM roles r LEFT JOIN role_has_permissions rhp ON rhp.role_id = r.id \
         GROUP BY r.id, r.name, r.guard_name ORDER BY r.id",
    )
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name, guard_name, perm_count, perm_ids)| RoleRow {
        protected: name == "Superadmin",
        id,
        name,
        guard_name,
        perm_count,
        perm_ids: perm_ids.unwrap_or_default(),
    })
    .collect();

    // Semua permission, dikelompokkan per prefix (mis. user.*, role.*, view_*).
    let perms: Vec<(i64, String)> = sqlx::query_as("SELECT id, name FROM permissions ORDER BY name")
        .fetch_all(&state.pool)
        .await
        .unwrap_or_default();
    let mut groups: Vec<PermGroup> = Vec::new();
    for (id, name) in perms {
        let g = perm_group(&name);
        if let Some(grp) = groups.iter_mut().find(|x| x.group == g) {
            grp.items.push(PermItem { id, name });
        } else {
            groups.push(PermGroup { group: g, items: vec![PermItem { id, name }] });
        }
    }

    let mut ctx = ctx_with_flash(&state, &user, "resources", &session, &f).await;
    ctx.insert("rows", &rows);
    ctx.insert("perm_groups", &groups);
    render(&state, "usermgmt/roles.html", &ctx)
}

pub async fn role_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<RoleForm>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/roles", false, "", "csrf");
    }
    let name = form.name.trim();
    if name.is_empty() {
        return redirect("/admin/roles", false, "", "required");
    }
    let new_id: Result<(i64,), _> = sqlx::query_as(
        "INSERT INTO roles (name, guard_name, created_at, updated_at) VALUES ($1, 'web', now(), now()) RETURNING id",
    )
    .bind(name)
    .fetch_one(&state.pool)
    .await;
    let rid = match new_id {
        Ok((id,)) => id,
        Err(_) => return redirect("/admin/roles", false, "", "fail"),
    };
    sync_perms(&state, rid, &parse_ids(&form.permissions)).await;
    redirect("/admin/roles", true, "saved", "")
}

pub async fn role_update(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<RoleForm>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/roles", false, "", "csrf");
    }
    let name = form.name.trim();
    if name.is_empty() {
        return redirect("/admin/roles", false, "", "required");
    }
    let r = sqlx::query("UPDATE roles SET name=$1, updated_at=now() WHERE id=$2")
        .bind(name)
        .bind(id)
        .execute(&state.pool)
        .await;
    if r.is_err() {
        return redirect("/admin/roles", false, "", "fail");
    }
    let _ = sqlx::query("DELETE FROM role_has_permissions WHERE role_id=$1").bind(id).execute(&state.pool).await;
    sync_perms(&state, id, &parse_ids(&form.permissions)).await;
    redirect("/admin/roles", true, "updated", "")
}

pub async fn role_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can(GATE) {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/roles", false, "", "csrf");
    }
    // Lindungi role Superadmin.
    let rname: Option<String> = sqlx::query_scalar("SELECT name FROM roles WHERE id=$1").bind(id).fetch_optional(&state.pool).await.unwrap_or(None);
    if rname.as_deref() == Some("Superadmin") {
        return redirect("/admin/roles", false, "", "protected");
    }
    // Tolak hapus bila masih terpasang ke user.
    let used: i64 = sqlx::query_scalar("SELECT COUNT(*)::bigint FROM model_has_roles WHERE role_id=$1").bind(id).fetch_one(&state.pool).await.unwrap_or(0);
    if used > 0 {
        return redirect("/admin/roles", false, "", "used");
    }
    let _ = sqlx::query("DELETE FROM role_has_permissions WHERE role_id=$1").bind(id).execute(&state.pool).await;
    let r = sqlx::query("DELETE FROM roles WHERE id=$1").bind(id).execute(&state.pool).await;
    redirect("/admin/roles", r.is_ok(), "deleted", "fail")
}

async fn sync_perms(state: &AppState, role_id: i64, perm_ids: &[i64]) {
    for pid in perm_ids {
        let _ = sqlx::query("INSERT INTO role_has_permissions (permission_id, role_id) VALUES ($1, $2) ON CONFLICT DO NOTHING")
            .bind(pid)
            .bind(role_id)
            .execute(&state.pool)
            .await;
    }
}
