use axum::{
    extract::{Form, Path, Query, State},
    response::{Html, IntoResponse, Redirect, Response},
};
use serde::Deserialize;
use tower_sessions::Session;

use crate::{auth, format_rupiah as fmt, rbac::CurrentUser, view, AppState};

#[derive(Deserialize, Default)]
pub struct FlashQuery {
    #[serde(default)]
    success: Option<String>,
    #[serde(default)]
    error: Option<String>,
}

#[derive(serde::Serialize)]
struct ShiftHistory {
    start: String,
    end: String,
    modal: String,
    actual: String,
    difference: i64,
    difference_abs: String,
}

#[derive(Deserialize)]
pub struct OpenForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    starting_cash: i64,
    #[serde(default)]
    target_penjualan: Option<i64>,
    #[serde(default)]
    daily_budget: Option<i64>,
}

#[derive(Deserialize)]
pub struct CloseForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    actual_cash: i64,
}

fn flash_message(code: Option<&str>) -> Option<&'static str> {
    match code {
        Some("opened") => Some("Shift berhasil dibuka! Selamat bekerja."),
        Some("closed") => Some("Shift berhasil ditutup. Laporan kasir telah disimpan."),
        Some("active") => Some("Anda masih memiliki shift yang aktif!"),
        Some("pending") => Some("Masih ada pesanan belum dibayar / meja belum dikosongkan. Selesaikan dulu di Kasir."),
        Some("csrf") => Some("Sesi kedaluwarsa, silakan muat ulang halaman."),
        Some("fail") => Some("Terjadi kesalahan sistem."),
        _ => None,
    }
}

/// GET /admin/shifts
pub async fn shift_index(
    user: CurrentUser,
    State(state): State<AppState>,
    session: Session,
    Query(flash): Query<FlashQuery>,
) -> Html<String> {
    let pool = &state.pool;

    let current: Option<(i64, i64, String)> = sqlx::query_as(
        "SELECT id, starting_cash::bigint, to_char(start_time, 'DD Mon YYYY, HH24:MI') \
         FROM shifts WHERE user_id = $1 AND status = 'open' ORDER BY id DESC LIMIT 1",
    )
    .bind(user.id)
    .fetch_optional(pool)
    .await
    .ok()
    .flatten();

    let mut ctx = view::base_context(&state, &user, "").await;

    if let Some((id, starting, start_fmt)) = &current {
        let cash_sales: i64 = sqlx::query_scalar(
            "SELECT COALESCE(SUM(grand_total),0)::bigint FROM orders \
             WHERE payment_method='cash' AND payment_status='paid' \
             AND created_at >= (SELECT start_time FROM shifts WHERE id=$1)",
        )
        .bind(id)
        .fetch_one(pool)
        .await
        .unwrap_or(0);
        ctx.insert("has_shift", &true);
        ctx.insert("shift_id", id);
        ctx.insert("shift_start", start_fmt);
        ctx.insert("starting_cash", &fmt(*starting));
        ctx.insert("cash_sales", &fmt(cash_sales));
        ctx.insert("expected_cash", &fmt(starting + cash_sales));
    } else {
        ctx.insert("has_shift", &false);
        let is_first: bool = sqlx::query_scalar(
            "SELECT NOT EXISTS(SELECT 1 FROM daily_sales_targets WHERE date = current_date)",
        )
        .fetch_one(pool)
        .await
        .unwrap_or(true);
        ctx.insert("is_first_shift", &is_first);
    }

    let history: Vec<ShiftHistory> = sqlx::query_as::<_, (String, Option<String>, i64, i64, i64)>(
        "SELECT to_char(start_time,'DD/MM/YYYY HH24:MI'), to_char(end_time,'HH24:MI'), \
         starting_cash::bigint, COALESCE(actual_cash,0)::bigint, COALESCE(difference,0)::bigint \
         FROM shifts WHERE user_id=$1 AND status='closed' ORDER BY id DESC LIMIT 10",
    )
    .bind(user.id)
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(start, end, modal, actual, difference)| ShiftHistory {
        start,
        end: end.unwrap_or_default(),
        modal: fmt(modal),
        actual: fmt(actual),
        difference,
        difference_abs: fmt(difference.abs()),
    })
    .collect();
    ctx.insert("history", &history);

    ctx.insert("csrf_token", &auth::ensure_csrf(&session).await);
    ctx.insert("flash_success", &flash_message(flash.success.as_deref()));
    ctx.insert("flash_error", &flash_message(flash.error.as_deref()));

    match state.tera.render("kasir/shift.html", &ctx) {
        Ok(html) => Html(html),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")),
    }
}

/// POST /admin/shifts/open
pub async fn shift_open(
    user: CurrentUser,
    State(state): State<AppState>,
    session: Session,
    Form(form): Form<OpenForm>,
) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/shifts?error=csrf").into_response();
    }
    let pool = &state.pool;

    let open_exists: bool =
        sqlx::query_scalar("SELECT EXISTS(SELECT 1 FROM shifts WHERE user_id=$1 AND status='open')")
            .bind(user.id)
            .fetch_one(pool)
            .await
            .unwrap_or(false);
    if open_exists {
        return Redirect::to("/admin/shifts?error=active").into_response();
    }

    let is_first: bool = sqlx::query_scalar(
        "SELECT NOT EXISTS(SELECT 1 FROM daily_sales_targets WHERE date = current_date)",
    )
    .fetch_one(pool)
    .await
    .unwrap_or(true);
    if is_first {
        if let (Some(t), Some(b)) = (form.target_penjualan, form.daily_budget) {
            let _ = sqlx::query("INSERT INTO daily_sales_targets (date, amount, created_at, updated_at) VALUES (current_date, $1::numeric, now(), now()) ON CONFLICT (date) DO NOTHING")
                .bind(t).execute(pool).await;
            let _ = sqlx::query("INSERT INTO daily_budgets (date, amount, created_at, updated_at) VALUES (current_date, $1::numeric, now(), now()) ON CONFLICT (date) DO NOTHING")
                .bind(b).execute(pool).await;
        }
    }

    let res = sqlx::query(
        "INSERT INTO shifts (uuid, user_id, start_time, starting_cash, status, created_at, updated_at) \
         VALUES (gen_random_uuid(), $1, now(), $2::numeric, 'open', now(), now())",
    )
    .bind(user.id)
    .bind(form.starting_cash)
    .execute(pool)
    .await;

    match res {
        Ok(_) => Redirect::to("/admin/kasir").into_response(),
        Err(_) => Redirect::to("/admin/shifts?error=fail").into_response(),
    }
}

/// POST /admin/shifts/close/{id}
pub async fn shift_close(
    user: CurrentUser,
    State(state): State<AppState>,
    session: Session,
    Path(id): Path<i64>,
    Form(form): Form<CloseForm>,
) -> Response {
    if !auth::verify_csrf(&session, &form.csrf).await {
        return Redirect::to("/admin/shifts?error=csrf").into_response();
    }
    let pool = &state.pool;

    // Shift harus milik user & masih open.
    let starting: Option<i64> = sqlx::query_scalar(
        "SELECT starting_cash::bigint FROM shifts WHERE id=$1 AND user_id=$2 AND status='open'",
    )
    .bind(id)
    .bind(user.id)
    .fetch_optional(pool)
    .await
    .ok()
    .flatten();
    let Some(starting) = starting else {
        return Redirect::to("/admin/shifts?error=fail").into_response();
    };

    // Pencegat: ada pesanan menggantung di shift ini?
    let pending: i64 = sqlx::query_scalar(
        "SELECT COUNT(*) FROM orders WHERE created_at >= (SELECT start_time FROM shifts WHERE id=$1) \
         AND (order_status IN ('pending','cooking','served') OR payment_status='unpaid')",
    )
    .bind(id)
    .fetch_one(pool)
    .await
    .unwrap_or(0);
    if pending > 0 {
        return Redirect::to("/admin/shifts?error=pending").into_response();
    }

    let cash_sales: i64 = sqlx::query_scalar(
        "SELECT COALESCE(SUM(grand_total),0)::bigint FROM orders \
         WHERE payment_method='cash' AND payment_status='paid' \
         AND created_at >= (SELECT start_time FROM shifts WHERE id=$1)",
    )
    .bind(id)
    .fetch_one(pool)
    .await
    .unwrap_or(0);

    let expected = starting + cash_sales;
    let difference = form.actual_cash - expected;

    let _ = sqlx::query(
        "UPDATE shifts SET end_time=now(), cash_sales=$1::numeric, expected_cash=$2::numeric, \
         actual_cash=$3::numeric, difference=$4::numeric, status='closed', updated_at=now() \
         WHERE id=$5 AND user_id=$6",
    )
    .bind(cash_sales)
    .bind(expected)
    .bind(form.actual_cash)
    .bind(difference)
    .bind(id)
    .bind(user.id)
    .execute(pool)
    .await;

    Redirect::to("/admin/shifts?success=closed").into_response()
}
