use axum::{
    extract::{Form, Path, Query, State},
    http::StatusCode,
    response::{Html, IntoResponse, Redirect, Response},
};
use serde::{Deserialize, Serialize};
use tower_sessions::Session;

use crate::{auth, format_rupiah as fmt, rbac::CurrentUser, view, AppState};

fn forbidden() -> Response {
    (StatusCode::FORBIDDEN, Html("<h3>403 — butuh izin <code>view_finance</code></h3>".to_string())).into_response()
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
        Some("saved") => Some("Pengeluaran berhasil dicatat."),
        Some("updated") => Some("Pengeluaran berhasil diperbarui."),
        Some("deleted") => Some("Pengeluaran berhasil dihapus."),
        Some("budget") => Some("Budget & target harian berhasil disimpan."),
        _ => None,
    }
}
fn flash_err(c: Option<&str>) -> Option<&'static str> {
    match c {
        Some("csrf") => Some("Sesi kedaluwarsa, muat ulang halaman."),
        Some("fail") => Some("Gagal menyimpan — periksa input."),
        _ => None,
    }
}

#[derive(Serialize)]
struct ExpenseRow {
    id: i64,
    date_fmt: String,
    date_raw: String,
    category: String,
    notes: Option<String>,
    amount: i64,
    amount_fmt: String,
    user_name: String,
}
#[derive(Deserialize)]
pub struct ExpenseForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    date: String,
    category: String,
    amount: i64,
    #[serde(default)]
    notes: Option<String>,
}
#[derive(Deserialize)]
pub struct BudgetForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    date: String,
    budget: i64,
    target: i64,
}

/// GET /admin/expenses
pub async fn expenses_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can("view_finance") {
        return forbidden();
    }
    let pool = &state.pool;
    let rows: Vec<ExpenseRow> = sqlx::query_as::<_, (i64, String, String, String, Option<String>, i64, Option<String>)>(
        "SELECT e.id, to_char(e.date,'DD Mon YYYY'), to_char(e.date,'YYYY-MM-DD'), e.category, e.notes, e.amount::bigint, u.name \
         FROM expenses e LEFT JOIN users u ON u.id=e.user_id ORDER BY e.date DESC, e.created_at DESC LIMIT 200",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, date_fmt, date_raw, category, notes, amount, user_name)| ExpenseRow {
        id,
        date_fmt,
        date_raw,
        category,
        notes,
        amount,
        amount_fmt: fmt(amount),
        user_name: user_name.unwrap_or_else(|| "Sistem".into()),
    })
    .collect();

    let today_budget: i64 = sqlx::query_scalar("SELECT COALESCE((SELECT amount FROM daily_budgets WHERE date=current_date),0)::bigint").fetch_one(pool).await.unwrap_or(0);
    let today_target: i64 = sqlx::query_scalar("SELECT COALESCE((SELECT amount FROM daily_sales_targets WHERE date=current_date),0)::bigint").fetch_one(pool).await.unwrap_or(0);
    let today_str: String = sqlx::query_scalar("SELECT to_char(current_date,'YYYY-MM-DD')").fetch_one(pool).await.unwrap_or_default();

    let mut ctx = view::base_context(&state, &user, "finance").await;
    ctx.insert("rows", &rows);
    ctx.insert("today", &today_str);
    ctx.insert("today_budget", &today_budget);
    ctx.insert("today_target", &today_target);
    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);
    ctx.insert("flash_ok", &flash_ok(f.ok.as_deref()));
    ctx.insert("flash_err", &flash_err(f.err.as_deref()));
    match state.tera.render("finance/expenses.html", &ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}

pub async fn expense_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<ExpenseForm>) -> Response {
    if !user.can("view_finance") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/expenses?err=csrf").into_response();
    }
    let r = sqlx::query(
        "INSERT INTO expenses (uuid, date, category, notes, amount, user_id, created_at, updated_at) \
         VALUES (gen_random_uuid(), $1::date, $2, $3, $4::numeric, $5, now(), now())",
    )
    .bind(&form.date)
    .bind(form.category.trim())
    .bind(form.notes.as_deref().filter(|s| !s.is_empty()))
    .bind(form.amount)
    .bind(user.id)
    .execute(&state.pool)
    .await;
    if r.is_ok() {
        crate::audit::log(&state, &user, "tambah pengeluaran", &format!("Mencatat pengeluaran {} Rp{}", form.category.trim(), form.amount)).await;
    }
    redirect("/admin/expenses", r.is_ok(), "saved")
}

pub async fn expense_update(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<ExpenseForm>) -> Response {
    if !user.can("view_finance") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/expenses?err=csrf").into_response();
    }
    let r = sqlx::query("UPDATE expenses SET date=$1::date, category=$2, notes=$3, amount=$4::numeric, updated_at=now() WHERE id=$5")
        .bind(&form.date)
        .bind(form.category.trim())
        .bind(form.notes.as_deref().filter(|s| !s.is_empty()))
        .bind(form.amount)
        .bind(id)
        .execute(&state.pool)
        .await;
    if r.is_ok() {
        crate::audit::log(&state, &user, "edit pengeluaran", &format!("Mengubah pengeluaran #{id}")).await;
    }
    redirect("/admin/expenses", r.is_ok(), "updated")
}

pub async fn expense_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can("view_finance") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/expenses?err=csrf").into_response();
    }
    let r = sqlx::query("DELETE FROM expenses WHERE id=$1").bind(id).execute(&state.pool).await;
    if r.is_ok() {
        crate::audit::log(&state, &user, "hapus pengeluaran", &format!("Menghapus pengeluaran #{id}")).await;
    }
    redirect("/admin/expenses", r.is_ok(), "deleted")
}

/// POST /admin/expenses/budget — set budget & target harian (upsert).
pub async fn set_budget(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<BudgetForm>) -> Response {
    if !user.can("view_finance") {
        return forbidden();
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/expenses?err=csrf").into_response();
    }
    let pool = &state.pool;
    let r1 = sqlx::query("INSERT INTO daily_budgets (date, amount, created_at, updated_at) VALUES ($1::date, $2::numeric, now(), now()) ON CONFLICT (date) DO UPDATE SET amount=$2::numeric, updated_at=now()")
        .bind(&form.date).bind(form.budget).execute(pool).await;
    let r2 = sqlx::query("INSERT INTO daily_sales_targets (date, amount, created_at, updated_at) VALUES ($1::date, $2::numeric, now(), now()) ON CONFLICT (date) DO UPDATE SET amount=$2::numeric, updated_at=now()")
        .bind(&form.date).bind(form.target).execute(pool).await;
    if r1.is_ok() && r2.is_ok() {
        crate::audit::log(&state, &user, "set budget", &format!("Set budget/target harian tgl {}", form.date)).await;
    }
    redirect("/admin/expenses", r1.is_ok() && r2.is_ok(), "budget")
}

#[derive(Deserialize)]
pub struct CsrfOnly {
    #[serde(rename = "_token", default)]
    csrf: String,
}

fn redirect(base: &str, ok: bool, ok_code: &str) -> Response {
    let target = if ok { format!("{base}?ok={ok_code}") } else { format!("{base}?err=fail") };
    Redirect::to(&target).into_response()
}
