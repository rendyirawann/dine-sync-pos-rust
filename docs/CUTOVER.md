# Fase 6 — Cutover Laravel → Rust (DineSync POS)

Panduan beralih dari aplikasi lama (Laravel/PHP) ke aplikasi baru (Rust) untuk dipakai sehari-hari, lalu mematikan Laravel.

## 0. Intinya

- **Cutover** = momen resmi berhenti pakai Laravel, mulai pakai Rust.
- **Tidak ada migrasi data.** Laravel & Rust memakai **PostgreSQL yang sama** (`dinesync_pos_rust` @ `127.0.0.1:5433`). Keduanya membaca/menulis tabel yang sama. Cutover = sekadar ganti aplikasi yang dibuka + matikan Laravel.
- Strategi: **jalankan berdampingan** sebentar (Laravel siaga sebagai cadangan), buktikan Rust aman, baru beralih penuh.

## 1. Prasyarat sebelum hari-H

- [ ] PostgreSQL pusat hidup & dapat diakses semua device.
- [ ] **Backup database** dulu: `pg_dump -h HOST -p 5433 -U postgres dinesync_pos_rust > backup_precutover.sql`
- [ ] Build rilis Rust: `cargo build --release --manifest-path rust\crates\central\Cargo.toml`
- [ ] Bundle distribusi: `powershell -ExecutionPolicy Bypass -File rust\package.ps1` → `rust\dist\dinesync-pos`
- [ ] Isi `.env` di tiap device (DATABASE_URL pusat + kunci Midtrans bila pakai bayar online).
- [ ] Set **Pengaturan Toko** di Rust (`/admin/settings`): nama toko, alamat, telepon, **pajak %** (penting — dipakai semua total order).

## 2. Verifikasi (wajib hijau sebelum alih)

Jalankan smoke test saat `central.exe` berjalan:

```
powershell -ExecutionPolicy Bypass -File rust\smoke-test.ps1
```

Harus **semua OK** (27 halaman: dashboard, kasir, dapur, antrian, master data, settings, expenses, stok, laporan, user/role, log, sync). Lalu uji manual alur kritis: buka shift → buat order tunai → cetak struk → dapur masak (stok terpotong) → tutup shift; scan QR pelanggan → pesan; panggil antrian (suara keluar di layar TV).

## 3. Paritas fitur (Laravel vs Rust)

**Sudah diport penuh:** Auth/login + RBAC, Dashboard (omzet/HPP/laba/grafik), Kasir+Shift, Dapur (+potong stok FEFO), Antrian (kiosk+TV+suara), Customer scan-QR, Master Data (kategori/menu/meja/promo/supplier/bahan/resep), **Pengaturan Toko**, Expenses+Budget, Stok Masuk/Opname/Kartu Stok, Laporan Sales/Items (+ tombol Cetak/PDF), User/Role + Ban/Unban, Log Activity, Midtrans (webhook + Snap sisi pelanggan), Real-time (WebSocket).

**Nilai tambah Rust (tak ada di Laravel):** mode **offline local-first** (Kasir/Dapur tetap jalan saat server mati + sinkron otomatis), Kartu Stok (ledger mutasi), endpoint `/health`, distribusi `.exe` standalone.

**Belum diport (backlog pasca-cutover, lihat §6).** Tidak memblokir operasi inti.

## 4. Langkah cutover (go-live)

1. Umumkan jadwal ke staf; pilih jam sepi.
2. Pastikan backup DB (lihat §1) sudah ada.
3. Jalankan Rust di tiap device (`run.bat` / `central.exe`), buka `http://127.0.0.1:8088/admin/login`.
4. Jalankan smoke test (§2) → semua OK.
5. **Periode berdampingan** (mis. 1–3 hari): staf pakai Rust, Laravel tetap menyala sebagai cadangan (jangan dipakai bersamaan untuk transaksi yang sama). Karena DB sama, data konsisten di kedua sisi.
6. Bila stabil → **beralih penuh ke Rust**.

## 5. Rencana rollback (kalau bermasalah)

- Rust dan Laravel berbagi DB yang sama → **rollback = cukup buka kembali Laravel** (`php artisan serve --port=8300`) dan hentikan Rust. Tidak ada data yang perlu dikembalikan.
- Bila data rusak: restore `backup_precutover.sql`.

## 6. Backlog pasca-cutover

**Sudah ditutup 2026-06-12** (tidak lagi jadi celah): ✅ Midtrans sisi **kasir** (order baru + tagih order existing) · ✅ Dapur **panggil ulang (recall)** + **lihat resep** per item · ✅ **Cetak/PDF Stock Opname** (+ Laporan Sales/Items) · ✅ **Akun Saya** ganti password mandiri.

**Sisa (prioritas rendah, tidak memblokir; tutup bila perlu):**

| Fitur | Catatan / mitigasi |
|---|---|
| Dapur: tandai **status per-item** (bukan seluruh order) | Saat ini "Masak Semua"/"Selesai" per order — cukup utk alur normal. |
| **Lupa password** via email | Superadmin reset via Edit User + user ganti sendiri via Akun Saya — sudah cukup utk 1 outlet. |
| **Avatar**/foto profil | Kosmetik. |
| Drill-down audit **per-user**, **mass-delete** user/role, **toggle cepat** promo aktif/nonaktif | Log global + hapus satuan + edit promo sudah ada. |

## 7. Pensiunkan Laravel

Setelah Rust stabil (mis. 1–2 minggu tanpa masalah):

1. Hentikan service Laravel (`php artisan serve` / Octane / web server PHP).
2. Arsipkan kode Laravel (sudah ada di repo/Git) — jangan hapus, simpan sebagai cadangan.
3. Cabut autostart Laravel bila ada.
4. Database tetap dipakai Rust (tidak disentuh).

> Catatan: agar Rust autostart saat device dinyalakan, daftarkan `central.exe` sebagai layanan Windows (mis. `sc create` / NSSM) atau buat shortcut di folder Startup. Folder `assets`, `storage`, dan `.env` harus tetap di samping `central.exe`.
