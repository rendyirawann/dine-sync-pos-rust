use axum::{
    extract::{Query, State},
    http::StatusCode,
    response::{Html, IntoResponse, Response},
};
use serde::{Deserialize, Serialize};

use crate::{format_rupiah as fmt, rbac::CurrentUser, view, AppState};

fn forbidden() -> Response {
    (StatusCode::FORBIDDEN, Html("<h3>403 — butuh izin <code>view_report</code></h3>".to_string())).into_response()
}

/// Resolusi rentang tanggal: pakai query atau default (awal bulan s/d hari ini).
async fn resolve_range(state: &AppState, start: Option<String>, end: Option<String>) -> (String, String) {
    let (def_start, def_end): (String, String) = sqlx::query_as("SELECT to_char(date_trunc('month',now()),'YYYY-MM-DD'), to_char(now(),'YYYY-MM-DD')")
        .fetch_one(&state.pool)
        .await
        .unwrap_or(("".into(), "".into()));
    (start.filter(|s| !s.is_empty()).unwrap_or(def_start), end.filter(|s| !s.is_empty()).unwrap_or(def_end))
}

// ---------- SALES REPORT ----------
#[derive(Deserialize, Default)]
pub struct SalesQuery {
    #[serde(default)]
    start: Option<String>,
    #[serde(default)]
    end: Option<String>,
    #[serde(default)]
    method: Option<String>,
}
#[derive(Serialize)]
struct SalesRow {
    date: String,
    invoice: String,
    customer: String,
    table: String,
    method: String,
    discount_fmt: String,
    grand_fmt: String,
}

pub async fn sales_report(user: CurrentUser, State(state): State<AppState>, Query(q): Query<SalesQuery>) -> Response {
    if !user.can("view_report") {
        return forbidden();
    }
    let pool = &state.pool;
    let (start, end) = resolve_range(&state, q.start, q.end).await;
    let method = q.method.filter(|m| !m.is_empty()).unwrap_or_else(|| "all".into());

    let (revenue, discount, count): (i64, i64, i64) = sqlx::query_as(
        "SELECT COALESCE(SUM(grand_total),0)::bigint, COALESCE(SUM(discount_amount),0)::bigint, COUNT(*) \
         FROM orders WHERE payment_status='paid' AND created_at >= $1::date AND created_at < ($2::date + interval '1 day') AND ($3='all' OR payment_method=$3)",
    )
    .bind(&start)
    .bind(&end)
    .bind(&method)
    .fetch_one(pool)
    .await
    .unwrap_or((0, 0, 0));
    let hpp: i64 = sqlx::query_scalar(
        "SELECT COALESCE(SUM(od.hpp),0)::bigint FROM order_details od JOIN orders o ON o.id=od.order_id \
         WHERE o.payment_status='paid' AND o.created_at >= $1::date AND o.created_at < ($2::date + interval '1 day') AND ($3='all' OR o.payment_method=$3)",
    )
    .bind(&start)
    .bind(&end)
    .bind(&method)
    .fetch_one(pool)
    .await
    .unwrap_or(0);

    let rows: Vec<SalesRow> = sqlx::query_as::<_, (String, String, Option<String>, Option<String>, Option<String>, i64, i64)>(
        "SELECT to_char(o.created_at,'DD Mon YYYY HH24:MI'), o.invoice_no, o.customer_name, t.table_number, o.payment_method, o.discount_amount::bigint, o.grand_total::bigint \
         FROM orders o LEFT JOIN tables t ON t.id=o.table_id \
         WHERE o.payment_status='paid' AND o.created_at >= $1::date AND o.created_at < ($2::date + interval '1 day') AND ($3='all' OR o.payment_method=$3) \
         ORDER BY o.created_at DESC LIMIT 500",
    )
    .bind(&start)
    .bind(&end)
    .bind(&method)
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(date, invoice, cust, table, pm, disc, grand)| SalesRow {
        date,
        invoice,
        customer: cust.unwrap_or_default(),
        table: table.unwrap_or_else(|| "Walk-in".into()),
        method: pm.unwrap_or_else(|| "-".into()),
        discount_fmt: fmt(disc),
        grand_fmt: fmt(grand),
    })
    .collect();

    let mut ctx = view::base_context(&state, &user, "report").await;
    ctx.insert("start", &start);
    ctx.insert("end", &end);
    ctx.insert("method", &method);
    ctx.insert("revenue_fmt", &fmt(revenue));
    ctx.insert("discount_fmt", &fmt(discount));
    ctx.insert("hpp_fmt", &fmt(hpp));
    ctx.insert("profit_fmt", &fmt(revenue - hpp));
    ctx.insert("count", &count);
    ctx.insert("rows", &rows);
    render(&state, "reports/sales.html", &ctx)
}

// ---------- ITEM SALES REPORT ----------
#[derive(Deserialize, Default)]
pub struct ItemQuery {
    #[serde(default)]
    start: Option<String>,
    #[serde(default)]
    end: Option<String>,
}
#[derive(Serialize)]
struct ItemRow {
    rank: usize,
    name: String,
    category: String,
    qty: i64,
    revenue_fmt: String,
}

pub async fn item_sales_report(user: CurrentUser, State(state): State<AppState>, Query(q): Query<ItemQuery>) -> Response {
    if !user.can("view_report") {
        return forbidden();
    }
    let pool = &state.pool;
    let (start, end) = resolve_range(&state, q.start, q.end).await;

    let rows: Vec<ItemRow> = sqlx::query_as::<_, (String, Option<String>, i64, i64)>(
        "SELECT m.name, c.name, COALESCE(SUM(od.qty),0)::bigint, COALESCE(SUM(od.subtotal),0)::bigint \
         FROM order_details od JOIN orders o ON o.id=od.order_id JOIN menus m ON m.id=od.menu_id \
         LEFT JOIN categories c ON c.id=m.category_id \
         WHERE o.payment_status='paid' AND o.created_at >= $1::date AND o.created_at < ($2::date + interval '1 day') \
         GROUP BY m.id, m.name, c.name ORDER BY 3 DESC LIMIT 200",
    )
    .bind(&start)
    .bind(&end)
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .enumerate()
    .map(|(i, (name, category, qty, revenue))| ItemRow {
        rank: i + 1,
        name,
        category: category.unwrap_or_else(|| "-".into()),
        qty,
        revenue_fmt: fmt(revenue),
    })
    .collect();

    let mut ctx = view::base_context(&state, &user, "report").await;
    ctx.insert("start", &start);
    ctx.insert("end", &end);
    ctx.insert("rows", &rows);
    render(&state, "reports/items.html", &ctx)
}

fn render(state: &AppState, name: &str, ctx: &tera::Context) -> Response {
    match state.tera.render(name, ctx) {
        Ok(html) => Html(html).into_response(),
        Err(e) => Html(format!("<pre>Template error: {e:#}</pre>")).into_response(),
    }
}
