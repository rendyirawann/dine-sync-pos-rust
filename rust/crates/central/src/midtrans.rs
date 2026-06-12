use axum::{
    extract::State,
    http::StatusCode,
    response::{IntoResponse, Response},
    Json,
};
use serde_json::json;

use crate::AppState;

fn base_url(production: bool) -> &'static str {
    if production {
        "https://app.midtrans.com"
    } else {
        "https://app.sandbox.midtrans.com"
    }
}

/// Minta Snap token ke Midtrans (REST API langsung, HTTP Basic = base64(server_key:)).
pub async fn create_snap_token(state: &AppState, order_id: &str, gross_amount: i64, customer_name: &str) -> anyhow::Result<String> {
    if state.midtrans_server_key.is_empty() {
        anyhow::bail!("Midtrans belum dikonfigurasi (MIDTRANS_SERVER_KEY kosong)");
    }
    let url = format!("{}/snap/v1/transactions", base_url(state.midtrans_production));
    let body = json!({
        "transaction_details": { "order_id": order_id, "gross_amount": gross_amount },
        "customer_details": { "first_name": customer_name }
    });
    let resp = reqwest::Client::new()
        .post(&url)
        .basic_auth(&state.midtrans_server_key, Some(""))
        .header("Accept", "application/json")
        .json(&body)
        .send()
        .await?;
    let status = resp.status();
    let v: serde_json::Value = resp.json().await?;
    match v.get("token").and_then(|t| t.as_str()) {
        Some(token) => Ok(token.to_string()),
        None => anyhow::bail!("Midtrans gagal ({status}): {v}"),
    }
}

/// Verifikasi tanda tangan webhook: sha512(order_id + status_code + gross_amount + server_key).
pub fn verify_signature(server_key: &str, order_id: &str, status_code: &str, gross_amount: &str, signature_key: &str) -> bool {
    use sha2::{Digest, Sha512};
    let mut h = Sha512::new();
    h.update(format!("{order_id}{status_code}{gross_amount}{server_key}").as_bytes());
    let hex: String = h.finalize().iter().map(|b| format!("{b:02x}")).collect();
    hex == signature_key
}

#[derive(serde::Deserialize)]
pub struct Webhook {
    order_id: String,
    #[serde(default)]
    status_code: String,
    #[serde(default)]
    gross_amount: String,
    #[serde(default)]
    signature_key: String,
    #[serde(default)]
    transaction_status: String,
    #[serde(default)]
    payment_type: String,
}

/// POST /api/midtrans-webhook — notifikasi pembayaran dari Midtrans (publik, tanpa CSRF/auth).
pub async fn webhook(State(state): State<AppState>, Json(wh): Json<Webhook>) -> Response {
    if !verify_signature(&state.midtrans_server_key, &wh.order_id, &wh.status_code, &wh.gross_amount, &wh.signature_key) {
        return (StatusCode::FORBIDDEN, Json(json!({"message": "Invalid signature"}))).into_response();
    }
    // Invoice asli (buang suffix retry "-R123" bila ada).
    let invoice = wh.order_id.split("-R").next().unwrap_or(&wh.order_id);
    let mapped = match wh.transaction_status.as_str() {
        "settlement" | "capture" => Some("paid"),
        "pending" => Some("unpaid"),
        "deny" | "expire" | "cancel" => Some("failed"),
        _ => None,
    };
    match mapped {
        Some("paid") => {
            let _ = sqlx::query("UPDATE orders SET payment_status='paid', payment_method=$1, updated_at=now() WHERE invoice_no=$2")
                .bind(&wh.payment_type)
                .bind(invoice)
                .execute(&state.pool)
                .await;
            crate::realtime::kitchen_update(&state);
        }
        Some(st) => {
            let _ = sqlx::query("UPDATE orders SET payment_status=$1, updated_at=now() WHERE invoice_no=$2")
                .bind(st)
                .bind(invoice)
                .execute(&state.pool)
                .await;
        }
        None => {}
    }
    Json(json!({ "message": "Webhook diterima" })).into_response()
}
