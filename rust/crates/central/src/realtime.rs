use axum::{
    extract::{
        ws::{Message, WebSocket, WebSocketUpgrade},
        State,
    },
    response::Response,
};
use serde_json::json;

use crate::AppState;

/// GET /ws — koneksi WebSocket publik untuk layar display, antrian, dan dapur.
/// Server hanya melakukan broadcast (one-way); klien mendengarkan event JSON.
pub async fn ws_handler(State(state): State<AppState>, ws: WebSocketUpgrade) -> Response {
    ws.on_upgrade(move |socket| handle_socket(socket, state))
}

async fn handle_socket(mut socket: WebSocket, state: AppState) {
    let mut rx = state.events.subscribe();
    loop {
        tokio::select! {
            msg = rx.recv() => {
                match msg {
                    Ok(text) => {
                        if socket.send(Message::Text(text.into())).await.is_err() {
                            break; // klien terputus
                        }
                    }
                    Err(tokio::sync::broadcast::error::RecvError::Lagged(_)) => {} // tertinggal — lewati
                    Err(_) => break, // channel tutup
                }
            }
            incoming = socket.recv() => {
                match incoming {
                    Some(Ok(Message::Close(_))) | None => break,
                    Some(Ok(_)) => {} // ping/teks dari klien — abaikan
                    Some(Err(_)) => break,
                }
            }
        }
    }
}

/// Kirim satu event JSON ke semua klien WS (best-effort; tak ada klien = tak apa).
pub fn emit(state: &AppState, value: serde_json::Value) {
    let _ = state.events.send(value.to_string());
}

/// Eja nomor agar TTS membacanya per karakter: "A001" → "A-0-0-1".
fn spell(number: &str) -> String {
    number.chars().map(|c| c.to_string()).collect::<Vec<_>>().join("-")
}

/// Panggil antrian (TTS + update kartu di layar TV).
pub fn call_queue(state: &AppState, number: &str, name: &str) {
    let text = format!("Nomor antrian, {}, atas nama, {}. Silakan menuju meja resepsionis.", spell(number), name);
    emit(state, json!({"event":"call-event","type":"queue","number":number,"name":name,"text_to_speak":text}));
}

/// Pesanan siap diambil (TTS).
pub fn food_ready(state: &AppState, name: &str, number: &str) {
    let text = format!("Pesanan atas nama, {name}, sudah siap untuk diambil.");
    emit(state, json!({"event":"call-event","type":"food","number":number,"name":name,"text_to_speak":text}));
}

/// Antrian baru dari kiosk (display & dashboard antrian refresh).
pub fn new_queue(state: &AppState) {
    emit(state, json!({"event":"new-queue"}));
}

/// Ada perubahan order (baru/ubah status) → layar dapur refresh.
pub fn kitchen_update(state: &AppState) {
    emit(state, json!({"event":"kitchen-update"}));
}
