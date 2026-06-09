use tera::Context;

use crate::{format_rupiah, rbac::CurrentUser, AppState};

/// Context dasar untuk semua halaman admin (shell Metronic):
/// info user, flag permission untuk menu, dan statistik sidebar (hari ini, data nyata).
pub async fn base_context(state: &AppState, user: &CurrentUser, active: &str) -> Context {
    let pool = &state.pool;
    let mut ctx = Context::new();
    ctx.insert("active", active);

    // Info user (navbar + sidebar dropdown).
    ctx.insert("user_name", &user.name);
    ctx.insert("user_email", &user.email);
    ctx.insert("user_role", user.roles.first().map(String::as_str).unwrap_or("Staff"));
    ctx.insert("user_avatar", "/assets/media/avatars/blank.png");

    // Flag permission untuk menampilkan menu (Superadmin bypass via .can()).
    ctx.insert("can_kasir", &user.can("view_kasir"));
    ctx.insert("can_kitchen", &user.can("view_kitchen"));
    ctx.insert("can_queue", &user.can("view_queue"));
    ctx.insert("can_master", &user.can("view_data_master"));
    ctx.insert("can_finance", &user.can("view_finance"));
    ctx.insert("can_report", &user.can("view_report"));
    ctx.insert("can_resources", &user.can("view_resources"));

    // Statistik sidebar — data nyata HARI INI (graceful 0 bila query gagal/kosong).
    let target: i64 = sqlx::query_scalar(
        "SELECT COALESCE((SELECT amount FROM daily_sales_targets WHERE date = current_date), 0)::bigint",
    ).fetch_one(pool).await.unwrap_or(0);
    let budget: i64 = sqlx::query_scalar(
        "SELECT COALESCE((SELECT amount FROM daily_budgets WHERE date = current_date), 0)::bigint",
    ).fetch_one(pool).await.unwrap_or(0);
    let income: i64 = sqlx::query_scalar(
        "SELECT COALESCE(SUM(grand_total),0)::bigint FROM orders WHERE payment_status='paid' AND created_at::date = current_date",
    ).fetch_one(pool).await.unwrap_or(0);
    let spent: i64 = sqlx::query_scalar(
        "SELECT COALESCE(SUM(amount),0)::bigint FROM expenses WHERE date = current_date",
    ).fetch_one(pool).await.unwrap_or(0);

    let sales_pct = if target > 0 { income * 100 / target } else { 0 };
    let expense_pct = if budget > 0 { spent * 100 / budget } else { 0 };

    ctx.insert("sales_target", &format_rupiah(target));
    ctx.insert("income", &format_rupiah(income));
    ctx.insert("budget", &format_rupiah(budget));
    ctx.insert("spent", &format_rupiah(spent));
    ctx.insert("sales_percentage", &sales_pct);
    ctx.insert("sales_bar_width", &sales_pct.min(100));
    ctx.insert("sales_progress_color", if sales_pct >= 100 { "bg-success" } else { "bg-warning" });
    ctx.insert("expense_percentage", &expense_pct);
    ctx.insert("progress_color", if expense_pct >= 100 { "bg-danger" } else { "bg-primary" });

    ctx
}
