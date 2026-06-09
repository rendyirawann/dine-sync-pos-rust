use std::collections::HashSet;

use axum::{
    extract::FromRequestParts,
    http::request::Parts,
    response::{IntoResponse, Redirect, Response},
};
use sqlx::types::Uuid;
use tower_sessions::Session;

use crate::{auth::SESSION_USER_KEY, AppState};

/// User yang sedang login beserta role & permission efektifnya.
#[derive(Clone)]
pub struct CurrentUser {
    pub id: Uuid,
    pub name: String,
    pub email: String,
    pub roles: Vec<String>,
    pub permissions: HashSet<String>,
}

impl CurrentUser {
    /// Superadmin melewati semua pengecekan izin (mirip Gate::before di Laravel).
    pub fn is_superadmin(&self) -> bool {
        self.roles.iter().any(|r| r.eq_ignore_ascii_case("superadmin"))
    }

    pub fn has_role(&self, role: &str) -> bool {
        self.roles.iter().any(|r| r.eq_ignore_ascii_case(role))
    }

    /// Apakah user boleh melakukan suatu permission (Superadmin selalu boleh).
    pub fn can(&self, permission: &str) -> bool {
        self.is_superadmin() || self.permissions.contains(permission)
    }
}

/// Muat role + permission efektif (lewat role maupun langsung) dari tabel spatie.
pub async fn load_current_user(state: &AppState, user_id: Uuid) -> Option<CurrentUser> {
    let (name, email): (String, String) =
        sqlx::query_as("SELECT name, email FROM users WHERE id = $1")
            .bind(user_id)
            .fetch_optional(&state.pool)
            .await
            .ok()
            .flatten()?;

    let roles: Vec<String> = sqlx::query_scalar(
        "SELECT r.name FROM roles r \
         JOIN model_has_roles mhr ON mhr.role_id = r.id \
         WHERE mhr.model_id = $1",
    )
    .bind(user_id)
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default();

    let permissions: Vec<String> = sqlx::query_scalar(
        "SELECT p.name FROM permissions p \
         JOIN role_has_permissions rhp ON rhp.permission_id = p.id \
         JOIN model_has_roles mhr ON mhr.role_id = rhp.role_id \
         WHERE mhr.model_id = $1 \
         UNION \
         SELECT p.name FROM permissions p \
         JOIN model_has_permissions mhp ON mhp.permission_id = p.id \
         WHERE mhp.model_id = $1",
    )
    .bind(user_id)
    .fetch_all(&state.pool)
    .await
    .unwrap_or_default();

    Some(CurrentUser {
        id: user_id,
        name,
        email,
        roles,
        permissions: permissions.into_iter().collect(),
    })
}

/// Extractor: memuat CurrentUser dari session. Redirect ke login bila belum auth.
impl FromRequestParts<AppState> for CurrentUser {
    type Rejection = Response;

    async fn from_request_parts(parts: &mut Parts, state: &AppState) -> Result<Self, Self::Rejection> {
        let to_login = || Redirect::to("/admin/login").into_response();

        let session = Session::from_request_parts(parts, state)
            .await
            .map_err(|_| to_login())?;

        let uid: Option<String> = session.get(SESSION_USER_KEY).await.unwrap_or(None);
        let Some(uid) = uid else {
            return Err(to_login());
        };
        let Ok(user_id) = Uuid::parse_str(&uid) else {
            return Err(to_login());
        };

        load_current_user(state, user_id).await.ok_or_else(to_login)
    }
}
