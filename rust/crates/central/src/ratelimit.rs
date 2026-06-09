use std::collections::HashMap;
use std::sync::Mutex;
use std::time::{Duration, Instant};

/// Catatan percobaan login per-key (login|ip).
#[derive(Default)]
struct Attempt {
    fails: u32,
    locked_until: Option<Instant>,
}

/// Rate limiter login dengan hukuman bertingkat (mirip LoginRequest Laravel):
/// gagal ke-3 → 10s, ke-4 → 15s, ke-5 → 20s, ke-6+ → 60s.
#[derive(Default)]
pub struct RateLimiter {
    inner: Mutex<HashMap<String, Attempt>>,
}

impl RateLimiter {
    /// Sisa detik lockout bila key sedang terkunci.
    pub fn locked_seconds(&self, key: &str) -> Option<u64> {
        let map = self.inner.lock().unwrap();
        let until = map.get(key)?.locked_until?;
        let now = Instant::now();
        if until > now {
            Some((until - now).as_secs() + 1)
        } else {
            None
        }
    }

    /// Catat satu kegagalan; kembalikan durasi lockout (detik) bila kena hukuman.
    pub fn record_failure(&self, key: &str) -> Option<u64> {
        let mut map = self.inner.lock().unwrap();
        let a = map.entry(key.to_string()).or_default();
        a.fails += 1;
        let dur = match a.fails {
            3 => 10,
            4 => 15,
            5 => 20,
            n if n >= 6 => 60,
            _ => 0,
        };
        if dur > 0 {
            a.locked_until = Some(Instant::now() + Duration::from_secs(dur));
            Some(dur)
        } else {
            None
        }
    }

    /// Hapus catatan (dipanggil saat login berhasil).
    pub fn clear(&self, key: &str) {
        self.inner.lock().unwrap().remove(key);
    }
}
