# Deploy DineSync POS ke VPS (Linux) — Model Terpusat

Panduan menaruh aplikasi di VPS/cloud. Model: **terpusat** — VPS menjalankan PostgreSQL + `central` (versi Linux) + domain/HTTPS; semua device (kasir, dapur, HP pelanggan) cukup **buka browser**.

> Catatan OS: biner `central.exe` yang kita buat di Windows TIDAK jalan di Linux. Kodenya **portabel** (tidak ada API khusus Windows; semua crate lintas-platform, tanpa OpenSSL/libpq) → **build ulang langsung di VPS**.

---

## 1. Prasyarat
- VPS Ubuntu 22.04/24.04 (atau Debian), akses SSH/root.
- (Disarankan) **domain** diarahkan ke IP VPS (A record), mis. `pos.namatoko.com` → untuk HTTPS. Tanpa domain bisa pakai IP (lihat §7).
- Repo ada di GitHub: `rendyirawann/dine-sync-pos-rust`.

## 2. Install PostgreSQL + siapkan database
```bash
sudo apt update && sudo apt install -y postgresql
sudo -u postgres psql -c "ALTER USER postgres PASSWORD 'PASSWORD_KUAT';"
sudo -u postgres psql -c "CREATE DATABASE dinesync_pos_rust;"
```
Pindahkan skema + data dari yang sekarang (jalankan di mesin lama, lalu unggah file-nya):
```bash
# di mesin sekarang (Windows): ekspor
pg_dump -h 127.0.0.1 -p 5433 -U postgres dinesync_pos_rust > dump.sql
# unggah dump.sql ke VPS (scp), lalu di VPS:
sudo -u postgres psql -d dinesync_pos_rust -f dump.sql
```
> PostgreSQL biarkan hanya listen lokal (default `127.0.0.1`) — `central` di VPS yang sama mengaksesnya via localhost. JANGAN buka 5432 ke internet.

## 3. Install Rust + build `central` (Linux)
```bash
sudo apt install -y build-essential pkg-config git curl
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh -s -- -y
source "$HOME/.cargo/env"
git clone https://github.com/rendyirawann/dine-sync-pos-rust.git /opt/dinesync
cd /opt/dinesync
cargo build --release --manifest-path rust/crates/central/Cargo.toml
```
Biner hasil: `/opt/dinesync/rust/target/release/central`. Template sudah **embedded**; aset Metronic dibaca dari `public/assets` (sudah ada di repo) — relatif lokasi biner: biner mencari `assets/`/`storage/` di sampingnya, fallback ke pohon sumber, jadi cukup jalankan dari `/opt/dinesync`.

## 4. Konfigurasi `.env`
Buat `/opt/dinesync/.env` (dibaca dari CWD saat dijalankan):
```
DATABASE_URL=postgres://postgres:PASSWORD_KUAT@127.0.0.1:5432/dinesync_pos_rust?sslmode=disable
PUBLIC_URL=https://pos.namatoko.com
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxx
MIDTRANS_IS_PRODUCTION=false
```
- `PUBLIC_URL` → dipakai untuk **URL di QR meja** + **notifikasi/webhook Midtrans** (otomatis diarahkan ke `PUBLIC_URL/api/midtrans-webhook`).
- Saat siap produksi: ganti kunci Midtrans ke production + `MIDTRANS_IS_PRODUCTION=true`.

## 5. Jalankan sebagai service (autostart + auto-restart)
`/etc/systemd/system/dinesync.service`:
```ini
[Unit]
Description=DineSync POS
After=network.target postgresql.service

[Service]
WorkingDirectory=/opt/dinesync
ExecStart=/opt/dinesync/rust/target/release/central
Restart=always
RestartSec=3
User=root

[Install]
WantedBy=multi-user.target
```
```bash
sudo systemctl daemon-reload
sudo systemctl enable --now dinesync
sudo systemctl status dinesync     # cek jalan
curl http://127.0.0.1:8088/health  # harus "ok"
```

## 6. HTTPS + domain (Caddy = paling mudah, sertifikat otomatis)
```bash
sudo apt install -y debian-keyring debian-archive-keyring apt-transport-https curl
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update && sudo apt install -y caddy
```
`/etc/caddy/Caddyfile`:
```
pos.namatoko.com {
    reverse_proxy 127.0.0.1:8088
}
```
```bash
sudo systemctl restart caddy   # otomatis ambil sertifikat Let's Encrypt
```
Sekarang aplikasi di `https://pos.namatoko.com`.

### Firewall
```bash
sudo ufw allow 22/tcp && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp
sudo ufw enable
# 5432 (Postgres) TIDAK dibuka ke publik.
```

## 7. Tanpa domain (pakai IP)
- `.env`: `PUBLIC_URL=http://IP_VPS:8088`.
- Buka firewall `8088/tcp`, akses `http://IP_VPS:8088`. (HTTP — cukup untuk uji; untuk pembayaran produksi pakai domain+HTTPS.)

## 8. Scan QR pelanggan & Midtrans
- Cetak QR meja dari **Master → Meja → tombol QR**. Karena `PUBLIC_URL` di-set, QR otomatis berisi `https://pos.namatoko.com/scan/{uuid}` (bukan localhost) — bisa dipindai HP pelanggan dari mana saja.
- Webhook Midtrans otomatis diarahkan ke `PUBLIC_URL/api/midtrans-webhook` (lewat header X-Override-Notification). Pastikan di dashboard Midtrans, atau biarkan override ini menanganinya.
- Suara panggilan antrian + Layar TV: buka `https://pos.namatoko.com/display` (semua memakai instance VPS yang sama → suara nyambung).

---

## 9. Alur UPDATE (catatan penting: apa yang dijalankan di mana)

**Di LOKAL (Windows, untuk ngoding/uji):**
1. Edit kode di mesin dev.
2. Uji: `cargo run --manifest-path rust\crates\central\Cargo.toml` (atau jalankan `central.exe` debug) → buka `http://127.0.0.1:8088`.
   - `cargo run` HANYA untuk dev. Tidak dipakai di produksi.
3. Commit & push: `git add -A; git commit -m "..."; git push`.

**Di VPS (Linux, setiap ada update kode):**
```bash
cd /opt/dinesync
git pull
cargo build --release --manifest-path rust/crates/central/Cargo.toml
sudo systemctl restart dinesync
```
- Template & logika ikut otomatis (template embedded saat build release).
- **Perubahan SKEMA database** (tambah/ubah tabel/kolom) TIDAK otomatis — jalankan SQL-nya manual di Postgres VPS (atau via migrasi Laravel bila masih dipakai), mis:
  ```bash
  sudo -u postgres psql -d dinesync_pos_rust -f perubahan.sql
  ```

**Kalau nanti pakai device local-first (offline per device, Windows):**
- Di lokal: `cargo build --release` lalu `powershell -File rust\package.ps1` → folder `rust\dist\dinesync-pos`.
- Copy folder itu ke device, isi `.env` (DATABASE_URL → VPS/VPN, PUBLIC_URL → domain), jalankan `run.bat`.

## 10. Backup rutin
```bash
pg_dump -U postgres dinesync_pos_rust > /root/backup_$(date +%F).sql   # jadwalkan via cron
```

## 11. Verifikasi setelah deploy
- `curl https://pos.namatoko.com/health` → `ok`.
- Login admin → cek beberapa halaman, atau salin `rust/smoke-test.ps1` logikanya (login + GET halaman utama) untuk cek cepat.
