use crate::{rbac::CurrentUser, AppState};

/// Catat satu aksi admin ke activity_log (spatie/activitylog). Fire-and-forget —
/// kegagalan pencatatan tidak boleh menggagalkan aksi utama.
pub async fn log(state: &AppState, user: &CurrentUser, log_name: &str, description: &str) {
    let _ = sqlx::query(
        "INSERT INTO activity_log (log_name, event, description, causer_type, causer_id, properties, created_at, updated_at) \
         VALUES ($1, NULL, $2, $3, $4, NULL, now(), now())",
    )
    .bind(log_name)
    .bind(description)
    .bind("App\\Models\\User")
    .bind(user.id)
    .execute(&state.pool)
    .await;
}
