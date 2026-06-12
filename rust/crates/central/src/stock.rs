use axum::{
    extract::{Form, Path, Query, State},
    http::StatusCode,
    response::{Html, IntoResponse, Redirect, Response},
};
use serde::{Deserialize, Serialize};
use tower_sessions::Session;

use crate::{auth, format_rupiah as fmt, rbac::CurrentUser, view, AppState};

// ---------- util ----------
fn forbidden(perm: &str) -> Response {
    (StatusCode::FORBIDDEN, Html(format!("<h3>403 — butuh izin <code>{perm}</code></h3>"))).into_response()
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
/// Format kuantitas (numeric pecahan): buang desimal nol.
fn qfmt(n: f64) -> String {
    if n.fract().abs() < 1e-9 { format!("{}", n.round() as i64) } else { format!("{n:.2}") }
}
/// Format uang dari f64 → rupiah (dibulatkan).
fn money(n: f64) -> String {
    fmt(n.round() as i64)
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
        Some("deleted") => Some("Data berhasil dihapus."),
        Some("opname") => Some("Stock opname tersimpan & stok disesuaikan."),
        _ => None,
    }
}
fn flash_err(c: Option<&str>) -> Option<&'static str> {
    match c {
        Some("csrf") => Some("Sesi kedaluwarsa, muat ulang halaman."),
        Some("fail") => Some("Gagal menyimpan — periksa input."),
        Some("qty") => Some("Jumlah harus lebih dari 0."),
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
fn opt_i64(s: &str) -> Option<i64> {
    s.trim().parse::<i64>().ok()
}
fn opt_str(s: &str) -> Option<String> {
    let t = s.trim();
    if t.is_empty() { None } else { Some(t.to_string()) }
}

// =================================================================
// SERVICE: pemotongan stok FEFO (dipakai kitchen saat cooking/done)
// =================================================================
async fn insert_movement(
    tx: &mut sqlx::PgConnection,
    ingredient_id: i64,
    batch: Option<i64>,
    order_detail_id: Option<i64>,
    mtype: &str,
    qty: f64,
    cost: f64,
    reason: &str,
    reference: &str,
) -> anyhow::Result<()> {
    sqlx::query(
        "INSERT INTO stock_movements (ingredient_id, ingredient_batch_id, order_detail_id, type, quantity, cost_total, reason, reference, created_at, updated_at) \
         VALUES ($1, $2, $3, $4, $5::numeric, $6::numeric, $7, $8, now(), now())",
    )
    .bind(ingredient_id)
    .bind(batch)
    .bind(order_detail_id)
    .bind(mtype)
    .bind(qty)
    .bind(cost)
    .bind(reason)
    .bind(reference)
    .execute(&mut *tx)
    .await?;
    Ok(())
}

/// Konsumsi stok satu bahan secara FEFO (expiry terdekat dulu, lalu entry_date).
/// Mengembalikan total biaya (HPP) bahan yang terkonsumsi. Kurang stok → clamp + catat out_of_stock.
async fn consume_fefo(
    tx: &mut sqlx::PgConnection,
    ingredient_id: i64,
    mut need: f64,
    reason: &str,
    reference: &str,
    order_detail_id: Option<i64>,
) -> anyhow::Result<f64> {
    if need <= 1e-9 {
        return Ok(0.0);
    }
    // FEFO portabel: batch tanpa expiry di belakang, lalu expiry asc, lalu entry_date asc.
    // FOR UPDATE: kunci baris batch agar deduksi konkuren (multi-device) terserialkan
    // — mencegah stok jadi negatif & menjaga akurasi clamp/out-of-stock.
    let batches: Vec<(i64, f64, f64)> = sqlx::query_as(
        "SELECT id, remaining_quantity::float8, buy_price::float8 FROM ingredient_batches \
         WHERE ingredient_id=$1 AND remaining_quantity > 0 \
         ORDER BY (expiry_date IS NULL), expiry_date, entry_date, id FOR UPDATE",
    )
    .bind(ingredient_id)
    .fetch_all(&mut *tx)
    .await?;

    let mut cost = 0.0;
    for (bid, remaining, unit_price) in batches {
        if need <= 1e-9 {
            break;
        }
        let ded = need.min(remaining);
        sqlx::query("UPDATE ingredient_batches SET remaining_quantity = remaining_quantity - $1::numeric, updated_at=now() WHERE id=$2")
            .bind(ded)
            .bind(bid)
            .execute(&mut *tx)
            .await?;
        let line_cost = ded * unit_price;
        cost += line_cost;
        insert_movement(&mut *tx, ingredient_id, Some(bid), order_detail_id, "out", ded, line_cost, reason, reference).await?;
        need -= ded;
    }
    if need > 1e-9 {
        // Kurang stok — jangan gagalkan penjualan; catat sebagai out-of-stock.
        insert_movement(&mut *tx, ingredient_id, None, order_detail_id, "out", need, 0.0, "sales_deduction_out_of_stock", reference).await?;
    }
    Ok(cost)
}

/// Potong stok untuk SEMUA item order yang belum dipotong (idempoten via is_stock_deducted).
/// Dipanggil di dalam transaksi kitchen saat cooking/done. Aman dipanggil ulang.
pub async fn deduct_order_stock(tx: &mut sqlx::PgConnection, order_id: i64) -> anyhow::Result<()> {
    let invoice: String = sqlx::query_scalar("SELECT invoice_no FROM orders WHERE id=$1")
        .bind(order_id)
        .fetch_one(&mut *tx)
        .await
        .unwrap_or_default();

    let details: Vec<(i64, i64, i64)> = sqlx::query_as(
        "SELECT id, menu_id, qty::bigint FROM order_details WHERE order_id=$1 AND NOT is_stock_deducted",
    )
    .bind(order_id)
    .fetch_all(&mut *tx)
    .await?;

    for (detail_id, menu_id, qty) in details {
        let recipe: Vec<(i64, f64)> = sqlx::query_as("SELECT ingredient_id, quantity::float8 FROM menu_ingredients WHERE menu_id=$1")
            .bind(menu_id)
            .fetch_all(&mut *tx)
            .await?;
        let mut hpp = 0.0;
        for (ing_id, per_portion) in recipe {
            let need = per_portion * qty as f64;
            hpp += consume_fefo(&mut *tx, ing_id, need, "sales_deduction", &invoice, Some(detail_id)).await?;
        }
        sqlx::query("UPDATE order_details SET hpp=$1::numeric, is_stock_deducted=true, updated_at=now() WHERE id=$2")
            .bind(hpp)
            .bind(detail_id)
            .execute(&mut *tx)
            .await?;
    }
    Ok(())
}

// =================================================================
// STOK MASUK (ingredient_batches) — gate view_finance
// =================================================================
#[derive(Serialize)]
struct CurrentStockRow {
    id: i64,
    name: String,
    unit: String,
    stock: String,
    min: String,
    low: bool,
}
#[derive(Serialize)]
struct BatchRow {
    id: i64,
    ingredient: String,
    supplier: String,
    unit: String,
    remaining: String,
    initial: String,
    buy_total: String,
    entry_date: String,
    expiry_date: String,
}
#[derive(Serialize)]
struct Opt {
    id: i64,
    name: String,
}
#[derive(Deserialize)]
pub struct BatchForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    ingredient_id: i64,
    #[serde(default)]
    initial_quantity: f64,
    #[serde(default)]
    buy_price_total: i64,
    #[serde(default)]
    entry_date: String,
    #[serde(default)]
    expiry_date: String,
    #[serde(default)]
    supplier_id: String,
}

pub async fn stocks_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can("view_finance") {
        return forbidden("view_finance");
    }
    let pool = &state.pool;
    let current: Vec<CurrentStockRow> = sqlx::query_as::<_, (i64, String, String, f64, f64)>(
        "SELECT i.id, i.name, i.unit, COALESCE(SUM(b.remaining_quantity),0)::float8, i.minimum_stock::float8 \
         FROM ingredients i LEFT JOIN ingredient_batches b ON b.ingredient_id=i.id \
         GROUP BY i.id, i.name, i.unit, i.minimum_stock ORDER BY i.name",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name, unit, stock, min)| CurrentStockRow {
        id,
        name,
        unit,
        low: min > 0.0 && stock <= min,
        stock: qfmt(stock),
        min: qfmt(min),
    })
    .collect();

    let batches: Vec<BatchRow> = sqlx::query_as::<_, (i64, String, Option<String>, String, f64, f64, i64, Option<String>, Option<String>)>(
        "SELECT b.id, i.name, s.name, i.unit, b.remaining_quantity::float8, b.initial_quantity::float8, \
                COALESCE(b.buy_price_total,0)::bigint, to_char(b.entry_date,'DD Mon YYYY'), to_char(b.expiry_date,'DD Mon YYYY') \
         FROM ingredient_batches b JOIN ingredients i ON i.id=b.ingredient_id LEFT JOIN suppliers s ON s.id=b.supplier_id \
         ORDER BY b.entry_date DESC, b.id DESC LIMIT 200",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, ingredient, supplier, unit, remaining, initial, buy_total, entry_date, expiry_date)| BatchRow {
        id,
        ingredient,
        supplier: supplier.unwrap_or_else(|| "—".into()),
        unit,
        remaining: qfmt(remaining),
        initial: qfmt(initial),
        buy_total: money(buy_total as f64),
        entry_date: entry_date.unwrap_or_default(),
        expiry_date: expiry_date.unwrap_or_else(|| "—".into()),
    })
    .collect();

    let ingredients: Vec<Opt> = sqlx::query_as("SELECT id, name FROM ingredients ORDER BY name").fetch_all(pool).await.unwrap_or_default().into_iter().map(|(id, name)| Opt { id, name }).collect();
    let suppliers: Vec<Opt> = sqlx::query_as("SELECT id, name FROM suppliers ORDER BY name").fetch_all(pool).await.unwrap_or_default().into_iter().map(|(id, name)| Opt { id, name }).collect();

    let mut ctx = ctx_with_flash(&state, &user, "finance", &session, &f).await;
    ctx.insert("current", &current);
    ctx.insert("batches", &batches);
    ctx.insert("ingredients", &ingredients);
    ctx.insert("suppliers", &suppliers);
    render(&state, "stock/stocks.html", &ctx)
}

pub async fn stock_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<BatchForm>) -> Response {
    if !user.can("view_finance") {
        return forbidden("view_finance");
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/stocks", false, "", "csrf");
    }
    if form.initial_quantity <= 0.0 {
        return redirect("/admin/stocks", false, "", "qty");
    }
    let unit_price = form.buy_price_total as f64 / form.initial_quantity;
    let entry = if form.entry_date.trim().is_empty() { None } else { Some(form.entry_date.trim().to_string()) };
    // Batch + movement dalam SATU transaksi agar ledger & stok selalu konsisten.
    let mut tx = match state.pool.begin().await {
        Ok(t) => t,
        Err(_) => return redirect("/admin/stocks", false, "", "fail"),
    };
    let batch_id: Result<(i64,), _> = sqlx::query_as(
        "INSERT INTO ingredient_batches (ingredient_id, supplier_id, initial_quantity, remaining_quantity, buy_price, buy_price_total, entry_date, expiry_date, created_at, updated_at) \
         VALUES ($1, $2, $3::numeric, $3::numeric, $4::numeric, $5::numeric, COALESCE($6::date, current_date), $7::date, now(), now()) RETURNING id",
    )
    .bind(form.ingredient_id)
    .bind(opt_i64(&form.supplier_id))
    .bind(form.initial_quantity)
    .bind(unit_price)
    .bind(form.buy_price_total)
    .bind(entry)
    .bind(opt_str(&form.expiry_date))
    .fetch_one(&mut *tx)
    .await;
    let Ok((bid,)) = batch_id else {
        return redirect("/admin/stocks", false, "", "fail"); // tx drop → rollback
    };
    if insert_movement(&mut tx, form.ingredient_id, Some(bid), None, "in", form.initial_quantity, 0.0, "purchase", &format!("Manual Input Batch #{bid}")).await.is_err() {
        return redirect("/admin/stocks", false, "", "fail"); // tx drop → rollback
    }
    if tx.commit().await.is_err() {
        return redirect("/admin/stocks", false, "", "fail");
    }
    crate::audit::log(&state, &user, "stok masuk", &format!("Stok masuk bahan #{} sejumlah {}", form.ingredient_id, qfmt(form.initial_quantity))).await;
    redirect("/admin/stocks", true, "saved", "")
}

#[derive(Deserialize)]
pub struct CsrfOnly {
    #[serde(rename = "_token", default)]
    csrf: String,
}
pub async fn stock_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can("view_finance") {
        return forbidden("view_finance");
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/stocks", false, "", "csrf");
    }
    let r = sqlx::query("DELETE FROM ingredient_batches WHERE id=$1").bind(id).execute(&state.pool).await;
    if r.is_ok() {
        crate::audit::log(&state, &user, "hapus batch", &format!("Menghapus batch stok #{id}")).await;
    }
    redirect("/admin/stocks", r.is_ok(), "deleted", "fail")
}

// =================================================================
// RESEP (menu_ingredients) — gate view_data_master
// =================================================================
#[derive(Serialize)]
struct MenuRecipeRow {
    id: i64,
    name: String,
    category: String,
    count: i64,
}
pub async fn recipes_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can("view_data_master") {
        return forbidden("view_data_master");
    }
    let rows: Vec<MenuRecipeRow> = sqlx::query_as::<_, (i64, String, Option<String>, i64)>(
        "SELECT m.id, m.name, c.name, COUNT(mi.id)::bigint \
         FROM menus m LEFT JOIN categories c ON c.id=m.category_id LEFT JOIN menu_ingredients mi ON mi.menu_id=m.id \
         GROUP BY m.id, m.name, c.name ORDER BY m.name",
    )
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name, category, count)| MenuRecipeRow { id, name, category: category.unwrap_or_else(|| "—".into()), count })
    .collect();
    let mut ctx = ctx_with_flash(&state, &user, "master", &session, &f).await;
    ctx.insert("rows", &rows);
    render(&state, "stock/recipes.html", &ctx)
}

#[derive(Serialize)]
struct RecipeLine {
    id: i64,
    ingredient: String,
    unit: String,
    qty: String,
}
pub async fn recipe_edit(user: CurrentUser, State(state): State<AppState>, session: Session, Path(menu_id): Path<i64>, Query(f): Query<Flash>) -> Response {
    if !user.can("view_data_master") {
        return forbidden("view_data_master");
    }
    let menu: Option<(String,)> = sqlx::query_as("SELECT name FROM menus WHERE id=$1").bind(menu_id).fetch_optional(&state.pool).await.unwrap_or(None);
    let Some((menu_name,)) = menu else {
        return redirect("/admin/recipes", false, "", "fail");
    };
    let lines: Vec<RecipeLine> = sqlx::query_as::<_, (i64, String, String, f64)>(
        "SELECT mi.id, i.name, i.unit, mi.quantity::float8 FROM menu_ingredients mi JOIN ingredients i ON i.id=mi.ingredient_id WHERE mi.menu_id=$1 ORDER BY i.name",
    )
    .bind(menu_id)
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, ingredient, unit, qty)| RecipeLine { id, ingredient, unit, qty: qfmt(qty) })
    .collect();
    let ingredients: Vec<Opt> = sqlx::query_as("SELECT id, name FROM ingredients ORDER BY name").fetch_all(&state.pool).await.unwrap_or_default().into_iter().map(|(id, name)| Opt { id, name }).collect();
    let mut ctx = ctx_with_flash(&state, &user, "master", &session, &f).await;
    ctx.insert("menu_id", &menu_id);
    ctx.insert("menu_name", &menu_name);
    ctx.insert("lines", &lines);
    ctx.insert("ingredients", &ingredients);
    render(&state, "stock/recipe.html", &ctx)
}

#[derive(Deserialize)]
pub struct RecipeForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    ingredient_id: i64,
    #[serde(default)]
    quantity: f64,
}
pub async fn recipe_add(user: CurrentUser, State(state): State<AppState>, session: Session, Path(menu_id): Path<i64>, Form(form): Form<RecipeForm>) -> Response {
    let back = format!("/admin/recipes/{menu_id}");
    if !user.can("view_data_master") {
        return forbidden("view_data_master");
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect(&back, false, "", "csrf");
    }
    if form.quantity <= 0.0 {
        return redirect(&back, false, "", "qty");
    }
    // Upsert: ganti baris bahan yang sama agar kuantitas selalu mutakhir.
    let _ = sqlx::query("DELETE FROM menu_ingredients WHERE menu_id=$1 AND ingredient_id=$2").bind(menu_id).bind(form.ingredient_id).execute(&state.pool).await;
    let r = sqlx::query("INSERT INTO menu_ingredients (menu_id, ingredient_id, quantity, created_at, updated_at) VALUES ($1, $2, $3::numeric, now(), now())")
        .bind(menu_id)
        .bind(form.ingredient_id)
        .bind(form.quantity)
        .execute(&state.pool)
        .await;
    if r.is_ok() {
        crate::audit::log(&state, &user, "edit resep", &format!("Mengubah resep menu #{menu_id} (bahan #{})", form.ingredient_id)).await;
    }
    redirect(&back, r.is_ok(), "saved", "fail")
}

pub async fn recipe_row_delete(user: CurrentUser, State(state): State<AppState>, session: Session, Path(id): Path<i64>, Form(form): Form<CsrfOnly>) -> Response {
    if !user.can("view_data_master") {
        return forbidden("view_data_master");
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/recipes", false, "", "csrf");
    }
    let menu_id: Option<i64> = sqlx::query_scalar("SELECT menu_id FROM menu_ingredients WHERE id=$1").bind(id).fetch_optional(&state.pool).await.unwrap_or(None);
    let _ = sqlx::query("DELETE FROM menu_ingredients WHERE id=$1").bind(id).execute(&state.pool).await;
    crate::audit::log(&state, &user, "edit resep", "Menghapus 1 bahan dari resep menu").await;
    let back = menu_id.map(|m| format!("/admin/recipes/{m}")).unwrap_or_else(|| "/admin/recipes".into());
    redirect(&back, true, "deleted", "")
}

// =================================================================
// STOCK OPNAME — gate view_finance
// =================================================================
#[derive(Serialize)]
struct OpnameIngRow {
    id: i64,
    name: String,
    unit: String,
    system: String,
}
#[derive(Serialize)]
struct OpnameHistRow {
    id: i64,
    date: String,
    user: String,
    notes: String,
    items: i64,
}
pub async fn opname_index(user: CurrentUser, State(state): State<AppState>, session: Session, Query(f): Query<Flash>) -> Response {
    if !user.can("view_finance") {
        return forbidden("view_finance");
    }
    let pool = &state.pool;
    let ings: Vec<OpnameIngRow> = sqlx::query_as::<_, (i64, String, String, f64)>(
        "SELECT i.id, i.name, i.unit, COALESCE(SUM(b.remaining_quantity),0)::float8 \
         FROM ingredients i LEFT JOIN ingredient_batches b ON b.ingredient_id=i.id \
         GROUP BY i.id, i.name, i.unit ORDER BY i.name",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, name, unit, system)| OpnameIngRow { id, name, unit, system: qfmt(system) })
    .collect();

    let history: Vec<OpnameHistRow> = sqlx::query_as::<_, (i64, Option<String>, Option<String>, Option<String>, i64)>(
        "SELECT o.id, to_char(o.date,'DD Mon YYYY'), u.name, o.notes, COUNT(d.id)::bigint \
         FROM stock_opnames o LEFT JOIN users u ON u.id=o.user_id LEFT JOIN stock_opname_details d ON d.stock_opname_id=o.id \
         GROUP BY o.id, o.date, u.name, o.notes ORDER BY o.id DESC LIMIT 100",
    )
    .fetch_all(pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(id, date, user, notes, items)| OpnameHistRow {
        id,
        date: date.unwrap_or_default(),
        user: user.unwrap_or_else(|| "—".into()),
        notes: notes.unwrap_or_default(),
        items,
    })
    .collect();

    let mut ctx = ctx_with_flash(&state, &user, "finance", &session, &f).await;
    ctx.insert("ings", &ings);
    ctx.insert("history", &history);
    render(&state, "stock/opname.html", &ctx)
}

#[derive(Deserialize)]
pub struct OpnameForm {
    #[serde(rename = "_token", default)]
    csrf: String,
    #[serde(default)]
    notes: String,
    #[serde(default)]
    adjustments: String, // JSON: [{"id":1,"physical":12.5}, ...]
}
#[derive(Deserialize)]
struct Adj {
    id: i64,
    physical: f64,
}
pub async fn opname_store(user: CurrentUser, State(state): State<AppState>, session: Session, Form(form): Form<OpnameForm>) -> Response {
    if !user.can("view_finance") {
        return forbidden("view_finance");
    }
    if !auth::verify_csrf(&session, &form.csrf).await {
        return redirect("/admin/stock-opname", false, "", "csrf");
    }
    let adjs: Vec<Adj> = serde_json::from_str(&form.adjustments).unwrap_or_default();

    let mut tx = match state.pool.begin().await {
        Ok(t) => t,
        Err(_) => return redirect("/admin/stock-opname", false, "", "fail"),
    };
    let notes = if form.notes.trim().is_empty() { "Stock Opname Berkala".to_string() } else { form.notes.trim().to_string() };
    let opname_id: Result<(i64,), _> = sqlx::query_as("INSERT INTO stock_opnames (user_id, date, notes, created_at, updated_at) VALUES ($1, current_date, $2, now(), now()) RETURNING id")
        .bind(user.id)
        .bind(&notes)
        .fetch_one(&mut *tx)
        .await;
    let Ok((opname_id,)) = opname_id else {
        return redirect("/admin/stock-opname", false, "", "fail");
    };

    for adj in &adjs {
        // Sistem qty saat ini (sumber kebenaran) untuk bahan ini.
        let system: f64 = sqlx::query_scalar("SELECT COALESCE(SUM(remaining_quantity),0)::float8 FROM ingredient_batches WHERE ingredient_id=$1")
            .bind(adj.id)
            .fetch_one(&mut *tx)
            .await
            .unwrap_or(0.0);
        let physical = adj.physical;
        let diff = physical - system;
        let _ = sqlx::query("INSERT INTO stock_opname_details (stock_opname_id, ingredient_id, system_qty, physical_qty, difference, created_at, updated_at) VALUES ($1, $2, $3::numeric, $4::numeric, $5::numeric, now(), now())")
            .bind(opname_id)
            .bind(adj.id)
            .bind(system)
            .bind(physical)
            .bind(diff)
            .execute(&mut *tx)
            .await;
        if diff < -1e-9 {
            // Kekurangan (loss) → potong FEFO.
            let _ = consume_fefo(&mut tx, adj.id, -diff, "stock_opname", "Stock Opname", None).await;
        } else if diff > 1e-9 {
            // Kelebihan (gain) → tambahkan ke batch terbaru; bila belum ada batch, BUAT batch baru
            // agar SUM(remaining_quantity) benar-benar naik ke jumlah fisik.
            let latest: Option<i64> = sqlx::query_scalar("SELECT id FROM ingredient_batches WHERE ingredient_id=$1 ORDER BY entry_date DESC, id DESC LIMIT 1")
                .bind(adj.id)
                .fetch_optional(&mut *tx)
                .await
                .unwrap_or(None);
            let batch_for_move = if let Some(bid) = latest {
                let _ = sqlx::query("UPDATE ingredient_batches SET remaining_quantity = remaining_quantity + $1::numeric, updated_at=now() WHERE id=$2")
                    .bind(diff)
                    .bind(bid)
                    .execute(&mut *tx)
                    .await;
                Some(bid)
            } else {
                let new_id: Option<(i64,)> = sqlx::query_as(
                    "INSERT INTO ingredient_batches (ingredient_id, supplier_id, initial_quantity, remaining_quantity, buy_price, buy_price_total, entry_date, expiry_date, created_at, updated_at) \
                     VALUES ($1, NULL, $2::numeric, $2::numeric, 0, 0, current_date, NULL, now(), now()) RETURNING id",
                )
                .bind(adj.id)
                .bind(diff)
                .fetch_optional(&mut *tx)
                .await
                .unwrap_or(None);
                new_id.map(|(i,)| i)
            };
            let _ = insert_movement(&mut tx, adj.id, batch_for_move, None, "in", diff, 0.0, "stock_opname", "Adjustment Gain").await;
        }
    }
    if tx.commit().await.is_err() {
        return redirect("/admin/stock-opname", false, "", "fail");
    }
    crate::audit::log(&state, &user, "stock opname", &format!("Stock opname — {} bahan disesuaikan", adjs.len())).await;
    redirect("/admin/stock-opname", true, "opname", "")
}

#[derive(Serialize)]
struct OpnameLine {
    ingredient: String,
    system: String,
    physical: String,
    diff: String,
    neg: bool,
    pos: bool,
}
/// GET /admin/stock-opname/{id}/print — lembar hasil opname (siap cetak/PDF via browser).
pub async fn opname_print(user: CurrentUser, State(state): State<AppState>, Path(id): Path<i64>) -> Response {
    if !user.can("view_finance") {
        return forbidden("view_finance");
    }
    let hdr: Option<(Option<String>, Option<String>, Option<String>)> = sqlx::query_as(
        "SELECT to_char(o.date,'DD Mon YYYY'), u.name, o.notes FROM stock_opnames o LEFT JOIN users u ON u.id=o.user_id WHERE o.id=$1",
    )
    .bind(id)
    .fetch_optional(&state.pool)
    .await
    .unwrap_or(None);
    let Some((date, petugas, notes)) = hdr else {
        return Redirect::to("/admin/stock-opname").into_response();
    };
    let lines: Vec<OpnameLine> = sqlx::query_as::<_, (String, f64, f64, f64)>(
        "SELECT i.name, d.system_qty::float8, d.physical_qty::float8, d.difference::float8 \
         FROM stock_opname_details d JOIN ingredients i ON i.id=d.ingredient_id WHERE d.stock_opname_id=$1 ORDER BY i.name",
    )
    .bind(id)
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(ingredient, system, physical, diff)| OpnameLine {
        ingredient,
        system: qfmt(system),
        physical: qfmt(physical),
        diff: format!("{}{}", if diff > 0.0 { "+" } else { "" }, qfmt(diff)),
        neg: diff < -1e-9,
        pos: diff > 1e-9,
    })
    .collect();
    let store: String = sqlx::query_scalar("SELECT COALESCE((SELECT store_name FROM settings LIMIT 1),'DineSync POS')").fetch_one(&state.pool).await.unwrap_or_else(|_| "DineSync POS".into());
    let mut ctx = tera::Context::new();
    ctx.insert("store", &store);
    ctx.insert("date", &date.unwrap_or_default());
    ctx.insert("petugas", &petugas.unwrap_or_else(|| "—".into()));
    ctx.insert("notes", &notes.unwrap_or_default());
    ctx.insert("lines", &lines);
    render(&state, "stock/opname_print.html", &ctx)
}

// =================================================================
// KARTU STOK (stock_movements) — gate view_finance
// =================================================================
#[derive(Serialize)]
struct LedgerRow {
    time: String,
    ingredient: String,
    mtype: String,
    qty: String,
    reason: String,
    reference: String,
}
pub async fn ledger_index(user: CurrentUser, State(state): State<AppState>, _session: Session) -> Response {
    if !user.can("view_finance") {
        return forbidden("view_finance");
    }
    let rows: Vec<LedgerRow> = sqlx::query_as::<_, (Option<String>, String, String, f64, String, Option<String>)>(
        "SELECT to_char(sm.created_at,'DD Mon YYYY HH24:MI'), i.name, sm.type, sm.quantity::float8, sm.reason, sm.reference \
         FROM stock_movements sm JOIN ingredients i ON i.id=sm.ingredient_id ORDER BY sm.id DESC LIMIT 300",
    )
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default()
    .into_iter()
    .map(|(time, ingredient, mtype, qty, reason, reference)| LedgerRow {
        time: time.unwrap_or_default(),
        ingredient,
        mtype,
        qty: qfmt(qty),
        reason,
        reference: reference.unwrap_or_else(|| "—".into()),
    })
    .collect();
    let mut ctx = view::base_context(&state, &user, "finance").await;
    ctx.insert("rows", &rows);
    render(&state, "stock/ledger.html", &ctx)
}
