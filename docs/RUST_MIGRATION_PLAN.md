# DineSync POS — Rencana Migrasi Backend Laravel → Rust (Local-First)

> **Status:** Perencanaan · **Dibuat:** 2026-06-09 · **Pemilik:** rendyirawann
> Dokumen ini adalah peta resmi proyek rewrite. Update bagian **§13 Checklist Progres** setiap selesai langkah.

---

## 0. TL;DR

Rewrite backend **Laravel 12 → Rust**, mempertahankan tampilan **Metronic** (server-rendered via Tera), dengan arsitektur **local-first**: tiap device POS menjalankan app Rust + database lokal sendiri sehingga tetap berfungsi walau **internet putus _maupun_ server pusat mati**, lalu **sinkron** ke server pusat (otomatis + tombol manual) saat online.

- **Motivasi:** performa + deployment seperti aplikasi desktop (.exe per device).
- **Sifat:** rewrite dari nol, **berbulan-bulan**, dikerjakan **bertahap** (vertical slice dulu).
- **Stack:** Axum + SQLx + Tera · SQLite (lokal) + PostgreSQL (pusat).

---

## 1. Tujuan & Non-Tujuan

### Tujuan
- Backend ditulis ulang dalam Rust, performa tinggi.
- UI Metronic dipertahankan (port Blade → Tera), **bukan** SPA.
- Operasi kasir tahan banting: jalan saat internet putus **dan** saat server pusat mati.
- Sinkronisasi data device ↔ pusat (auto + manual).
- Tiap device dapat di-install seperti aplikasi (.exe).

### Non-Tujuan (eksplisit TIDAK dikerjakan)
- Membuat **semua** 37 modul offline. Hanya alur kasir/order + data master yang dibutuhkan offline.
- Pembayaran digital (Midtrans QRIS/kartu) offline — secara teknis mustahil, butuh internet. Offline = **tunai saja**.
- Mengganti frontend customer (scan QR) jadi native — tetap web.

---

## 2. Kondisi Saat Ini (Baseline Laravel)

| Aspek | Detail |
|---|---|
| Framework | Laravel 12 (PHP 8.2) |
| Controllers | 37 file |
| Models | 20 file |
| Blade views | 88 file (~16.900 baris) |
| Migrations | 28 |
| Route web | 103 definisi |
| Database | PostgreSQL (port 5433, db `dinesync_pos_rust`) |
| Real-time | Laravel Reverb (WebSocket) — 3 event |
| Payment | Midtrans (sandbox) + webhook |
| Auth/RBAC | `spatie/laravel-permission` |
| Audit | `spatie/laravel-activitylog` |
| PDF / QR | `barryvdh/laravel-dompdf` · `simplesoftwareio/simple-qrcode` |
| Tabel data | `yajra/laravel-datatables` (server-side) |
| UI | Metronic (jQuery/Bootstrap) + Vite (Tailwind/Alpine utk frontend customer) |

> **Catatan penting:** Fitur offline/sync **belum ada sama sekali** di kode saat ini (sudah diverifikasi: tidak ada service worker, IndexedDB, kolom sync, atau endpoint sinkron). App saat ini murni online.
>
> **Catatan Windows:** Laravel Octane + RoadRunner **tidak bisa** jalan native di Windows (butuh ekstensi `pcntl` yang POSIX-only). Untuk dev PHP lokal pakai `php artisan serve`. Tidak relevan setelah pindah ke Rust.

---

## 3. Arsitektur Target — Local-First

```
        TIAP DEVICE (komputer kasir / layar dapur)
   ┌─────────────────────────────────────────────┐
   │  Browser  ──▶  Rust App lokal (localhost)     │
   │                 ├─ Axum (web server)          │
   │                 ├─ Tera (render HTML Metronic) │
   │                 ├─ SQLite (DB lokal device)    │
   │                 └─ Sync Engine                 │
   └──────────────────────────┼─────────────────────┘
                              │  (saat online)
                    push ▲    │   ▼ pull
              ┌───────────────────────────┐
              │       SERVER PUSAT        │
              │  Rust (Axum) + PostgreSQL │
              │  - agregasi semua device  │
              │  - laporan & dashboard     │
              │  - integrasi Midtrans      │
              └───────────────────────────┘
```

**Prinsip inti:** browser selalu bicara ke Rust **lokal** (`localhost`). Maka UI tetap server-rendered **dan** tetap hidup saat offline. Sync engine menangani pertukaran data ke pusat. Server pusat = sumber kebenaran untuk agregasi, laporan, dan integrasi yang butuh internet.

**Mode operasi:**
- **Online:** device baca/tulis lokal, sync engine push perubahan ke pusat + pull update (menu, harga, dll.) secara berkala.
- **Offline:** device baca/tulis lokal saja; perubahan ditumpuk di outbox.
- **Pulih:** sync otomatis jalan; tombol "Sinkron Sekarang" tersedia untuk manual.

---

## 4. Stack Teknologi

| Lapisan | Pilihan | Alasan |
|---|---|---|
| Web framework | **Axum** | Async (Tokio), ringan, ekosistem besar |
| DB akses | **SQLx** | Kode query sama untuk SQLite & Postgres; cek tipe saat compile |
| DB lokal (device) | **SQLite** | Embedded, tanpa setup, ideal per-device |
| DB pusat | **PostgreSQL** | Pertahankan yang sudah ada |
| Template HTML | **Tera** | Sintaks mirip Blade/Jinja → port Metronic mulus |
| Auth/session | `tower-sessions` + custom | Pengganti session Laravel |
| Password hash | `argon2` / `bcrypt` crate | Kompatibel verifikasi hash lama bila perlu |
| HTTP client (sync, Midtrans) | `reqwest` | — |
| Serialization | `serde` / `serde_json` | — |
| WebSocket (real-time) | `axum` + `tokio-tungstenite` | Pengganti Reverb (di pusat) |
| Migrasi DB | `sqlx migrate` | Dijalankan di lokal & pusat |
| Packaging desktop | Tauri (opsional) / service + browser kiosk | Hasil .exe per device |

> Kandidat alternatif yang sempat dipertimbangkan: SeaORM (ORM ala Eloquent), Actix-web. Diputuskan **Axum + SQLx** karena paling pas untuk dua backend DB (SQLite+Postgres) dan kontrol penuh atas query sync.

---

## 5. Klasifikasi Data (menentukan strategi sync)

| Kategori | Entitas | Pola sync | Kesulitan |
|---|---|---|---|
| **Local-created → push** | Order, OrderDetail, Shift, Queue, Expense, StockMovement | Dibuat di device, dikirim ke pusat. Konflik minim (tiap record lahir di 1 device). | 🟢 Mudah |
| **Central-owned → pull** | Menu, Category, MenuIngredient, Promo, Table (definisi), Supplier, Ingredient (definisi), Setting, User, Role/Permission, DailyBudget, DailySalesTarget | Diedit di pusat, didorong ke device (read-mostly di terminal). | 🟡 Sedang |
| **Shared-mutable → konflik** | **Stok** (qty IngredientBatch), StockOpname, **status Table** (kosong/terisi) | Berubah di banyak device bersamaan. | 🔴 Sulit |

**Aturan kunci:**
- Semua entitas yang disinkron pakai **UUID** sebagai primary key (bukan auto-increment) → cegah tabrakan ID antar-device. (App sudah pakai `ramsey/uuid`, konsep diteruskan.)
- **Stok disinkron sebagai DELTA** (selisih +/−), bukan angka absolut → dua device yang sama-sama menjual tidak saling menimpa; delta dijumlahkan di pusat. Over-sell tetap mungkin saat offline → tampilkan peringatan stok & rekonsiliasi via Stock Opname.
- Status meja: last-write-wins berbasis timestamp logis + indikator "perlu konfirmasi" jika bentrok.

---

## 6. Pemetaan Modul → Prioritas Migrasi & Kebutuhan Offline

| Domain | Controller | Prioritas | Butuh offline? |
|---|---|---|---|
| **Kasir** | Kasir, Shift | P0 (slice pertama) | ✅ Wajib |
| **Auth** | 9 controller (login, dst.) | P0 | ⚠️ Login ya (cache kredensial); reset password/email → online |
| **Kitchen** | Kitchen | P1 | ✅ Ya (+ real-time saat online) |
| **Master** | Categories, Menu, Ingredient, Promo, Supplier, Table | P1 | 📥 Pull (read-mostly di device) |
| **Frontend customer** | CustomerOrderController | P1 | ❌ **Online-only** — scan QR TIDAK jalan saat offline; aktif lagi hanya saat server pusat hidup |
| **Queue** | QueueController | P2 | ✅ Ya (+ real-time) |
| **Finance/Stock** | Stock, StockOpname, Expense | P2 | 🔴 Stok = shared-mutable (sulit) |
| **Report** | SalesReport, ItemSalesReport | P3 | ➖ Online (agregasi pusat) |
| **Dashboard** | DashboardAdmin | P3 | ➖ Online (stats lokal bila offline) |
| **UserManagement** | User, Role | P3 | 📥 Pull |
| **MyProfile** | Account, Profile, Security, Activity, LoginSession | P3 | ➖ Online |
| **Setting** | SettingController | P3 | 📥 Pull |
| **Help** | LogActivity | P4 | ➖ Online |

---

## 7. Desain Sync Engine

### 7.1 Pelacakan perubahan (outbox / change-log)
- Tiap tabel tersinkron punya kolom: `id (UUID)`, `updated_at`, `version (int)`, `deleted_at (tombstone)`, `device_id`, `dirty (bool)`.
- Tabel `sync_outbox` lokal mencatat setiap mutasi (insert/update/delete) yang belum terkirim: `(entity, entity_id, op, payload, created_at)`.

### 7.2 Push (device → pusat)
1. Kumpulkan entri `sync_outbox` berurutan.
2. Kirim batch ke endpoint pusat `POST /sync/push` (idempoten — pakai `op_id` UUID agar retry tidak dobel).
3. Pusat terapkan, balas status per entri.
4. Hapus entri outbox yang sukses; tandai record `dirty=false`.

### 7.3 Pull (pusat → device)
1. Device kirim `last_pulled_version` / cursor per entitas.
2. Pusat balas record yang berubah sejak cursor (termasuk tombstone).
3. Device merge (lihat aturan konflik §5).

### 7.4 Konflik
- **Local-created:** tak ada konflik (push saja).
- **Central-owned:** pusat menang (device read-only) → overwrite lokal.
- **Shared-mutable (stok):** akumulasi delta di pusat; kuantitas absolut device dihitung ulang dari pull. Stock Opname jadi mekanisme rekonsiliasi resmi.

### 7.5 Idempotency & ketahanan
- Setiap operasi sync punya `op_id` unik → pusat simpan log `applied_ops` untuk tolak duplikat.
- Sync auto: interval + saat koneksi pulih (deteksi via ping endpoint `/health`).
- Sync manual: tombol "Sinkron Sekarang" + indikator status (jumlah item pending, waktu sync terakhir).

---

## 8. Auth & RBAC

- Port konsep `spatie/laravel-permission`: tabel `roles`, `permissions`, `model_has_roles`, `role_has_permissions`.
- Session via `tower-sessions` (store di SQLite lokal).
- **Login offline:** cache hash kredensial + role/permission user yang pernah login di device → boleh login saat offline. Reset password / verifikasi email tetap butuh online.
- Middleware Axum untuk cek auth + permission per-route.

---

## 9. Pengganti Pustaka Pihak Ketiga

| Laravel | Pengganti Rust | Catatan |
|---|---|---|
| `spatie/laravel-permission` | Implementasi RBAC custom | Skema tabel sama |
| `spatie/laravel-activitylog` | Tabel `activity_log` + helper | — |
| `barryvdh/laravel-dompdf` | `printpdf` / `wkhtmltopdf` / render HTML→PDF | Untuk struk & laporan |
| `simplesoftwareio/simple-qrcode` | crate `qrcode` | QR meja |
| Laravel Reverb | `tokio-tungstenite` di server pusat | Real-time hanya saat online |
| `yajra/laravel-datatables` | Endpoint JSON server-side custom | Pagination/search/sort |
| Midtrans PHP SDK | `reqwest` ke REST API Midtrans | **Hanya di server pusat** (butuh internet) |

---

## 10. Packaging (.exe per Device)

- Bundel: binary Rust (Axum) + aset Metronic + file SQLite awal.
- Opsi A — **Tauri**: webview menunjuk ke `localhost`, hasil installer .exe kecil + ikon aplikasi.
- Opsi B — **Service + kiosk**: Rust jalan sebagai Windows service; browser dibuka mode kiosk ke `localhost`.
- Auto-update binary jadi pertimbangan fase akhir.

---

## 11. Roadmap Berfase

> Tiap fase menghasilkan sesuatu yang **jalan & bisa dievaluasi**. Boleh berhenti/ubah arah di antara fase.

### Fase 0 — Fondasi *(online dulu)* — ✅ SELESAI 2026-06-09
- [x] Install Rust toolchain (rustup) di Windows → `stable-x86_64-pc-windows-gnu` + WinLibs MinGW-w64 (dlltool/gcc).
- [x] Scaffold workspace Cargo: crate `central` (device + shared menyusul saat dibutuhkan).
- [x] Axum + koneksi ke PostgreSQL eksisting via SQLx (port 5433, tanpa TLS).
- [x] Render **1 halaman Metronic** via Tera dari data nyata (counts: users/menus/categories/orders).
- **Acceptance:** ✅ `http://127.0.0.1:8088` menampilkan halaman Metronic dgn data dari Postgres; aset Metronic disajikan dari `public/assets`.
- **Lokasi kode:** `rust/crates/central/` (`src/main.rs`, `templates/dashboard.html`, `.env`). Jalankan: `cargo run` dari `rust/crates/central/`.

### Fase 1 — Auth + RBAC — ✅ SELESAI 2026-06-09
- [x] Login/logout + session (tower-sessions MemoryStore); login multi-field (email/no_wa/name) + verifikasi **bcrypt hash Laravel**.
- [x] RBAC: muat role & permission dari tabel spatie, extractor `CurrentUser` + `.can()`, **bypass Superadmin** (mirip Gate::before).
- [x] Hardening: **CSRF** token (session), **rate-limit lockout bertingkat** (3→10s/4→15s/5→20s/6+→60s), **activity_log** (login/logout) + update `last_login`/`last_ip`.
- [x] **Admin shell Metronic** (Step 4): base layout Tera (`layout/app.html`) + **sidebar** (logo, dropdown user, widget target/budget, grid menu, Settings) + **header menu** (Dashboards/Data Master/Finance/Report/Resources/Help) + footer — di-port verbatim dari Blade. Menu **ber-permission**, link ke path Rust `/admin/...`. Dashboard `extends` shell.
- **Acceptance:** ✅ Terverifikasi via HTTP+DB — login bcrypt 200, CSRF tanpa token 419, rate-limit 422/422/429, activity_log +1, role/permission termuat (Superadmin 50 izin), route ber-permission ditegakkan, dashboard render shell penuh (25KB).
- **Kode:** `auth.rs`, `rbac.rs`, `ratelimit.rs`, `view.rs`, `templates/auth/login.html`, `templates/layout/{app,menu,sidebar,footer}.html`, `templates/dashboard.html`.
- **Sisa (ditunda, non-blok):** session store persisten (kini in-memory → reset saat restart), CSRF untuk semua POST mulai Fase 2, parsing detail user-agent di activity log.

### Fase 2 — Slice Kasir/Order *(online)* — 🚧 SEDANG BERJALAN
- [x] **2.1 Shift** — buka/tutup shift (`shift.rs`, `kasir/shift.html`): modal kas awal, target+budget harian (saat shift pertama hari itu), hitung selisih kas saat tutup, riwayat 10 terakhir. CSRF + redirect flash. **Terverifikasi** (buka→DB, tutup→selisih 0 "Pas").
- [x] **Sidebar widget hidup** — `view::base_context` kini async + query data nyata hari ini (target/budget/income/spent), dipakai semua halaman shell.
- [ ] 2.2 Kasir index (peta meja + modal pilih/lihat).
- [ ] 2.3 Order page (grid menu per kategori + keranjang).
- [ ] 2.4 Simpan order (tunai) + cetak struk.
- [ ] 2.5 Bayar susulan + kosongkan meja.
- **Acceptance:** alur order tunai penuh end-to-end di Rust terhadap Postgres.
- **Ditunda:** Midtrans (Fase 5), promo/diskon (opsional), potongan stok/HPP (Fase 4).

### Fase 3 — Local-First untuk slice Kasir *(INTI)*
- [ ] Tambah SQLite lokal + jalankan app di device.
- [ ] Konversi PK ke UUID untuk entitas tersinkron.
- [ ] Bangun sync engine: outbox, push, pull, idempotency, tombstone.
- [ ] Tombol "Sinkron Sekarang" + auto-sync + indikator status.
- [ ] Aturan konflik: order (mudah) → stok delta (sulit).
- **Acceptance:** matikan server pusat → kasir tetap jalan → nyalakan → data tersinkron tanpa dobel/hilang.

### Fase 4 — Lebarkan Modul
- [ ] Kitchen (+ real-time saat online).
- [ ] Master data (pull): Menu, Category, Promo, Table, Supplier, Ingredient.
- [ ] Queue, Frontend customer.
- [ ] Finance/Stock (stok shared-mutable), Stock Opname (rekonsiliasi).

### Fase 5 — Integrasi & Polish
- [ ] Midtrans (server pusat, online-only).
- [ ] PDF, QR, DataTables endpoints, activity log.
- [ ] WebSocket real-time pusat.
- [ ] Packaging installer (.exe) per device.

### Fase 6 — Cutover
- [ ] Jalankan berdampingan dgn Laravel (strangler) per device.
- [ ] Migrasi data, validasi, pensiunkan PHP.

---

## 12. Risiko & Pertanyaan Terbuka

- 🔴 **Konflik stok offline** = over-sell. Mitigasi: delta + peringatan + Stock Opname. Perlu disepakati toleransinya.
- 🟡 **Skema ganda** (SQLite + Postgres) harus selalu sinkron → satu sumber migrasi untuk dua target.
- ✅ **RESOLVED — Frontend customer (scan QR)**: scan QR **online-only**. Tidak perlu jalan saat offline; fitur ini mati saat server pusat down dan aktif lagi saat online. Menyederhanakan desain (device tak perlu jadi host QR untuk HP customer).
- ✅ **RESOLVED — Skala**: hanya **1 outlet**, **tanpa multi-tenant**. Sederhanakan: tidak perlu pemisahan data per-outlet.
- 🟡 **Jam antar-device** bisa skew → pakai versioning/logical clock, bukan murni wall-clock.
- 🟢 **Login offline**: butuh cache kredensial aman di device.
- ❓ Apakah hash password lama (bcrypt Laravel) perlu tetap valid di Rust? (kompatibel via crate `bcrypt`.)

---

## 13. Checklist Progres (update tiap sesi)

- [x] **Fase 0** — Fondasi ✅ 2026-06-09
- [x] **Fase 1** — Auth + RBAC ✅ 2026-06-09
- [ ] **Fase 2** — Slice Kasir (online)
- [ ] **Fase 3** — Local-first Kasir (sync engine)
- [ ] **Fase 4** — Lebarkan modul
- [ ] **Fase 5** — Integrasi & polish
- [ ] **Fase 6** — Cutover

**Log keputusan:**
- 2026-06-09 — Sepakat rewrite Rust local-first, pertahankan Metronic, stack Axum+SQLx+Tera, SQLite lokal + Postgres pusat. Toolchain Rust belum terinstal.
- 2026-06-09 — Skala dikonfirmasi: **1 outlet, tanpa multi-tenant**. Scan QR customer **online-only** (tidak offline). Project di-push ke GitHub: `rendyirawann/dine-sync-pos-rust`. Mulai Fase 0.
- 2026-06-09 — **Fase 0 SELESAI.** Toolchain: GNU + WinLibs MinGW-w64 (perlu `dlltool` utk `windows-sys`, `gcc` utk SQLite nanti). Stack pure-Rust terbukti jalan (Axum 0.8 + SQLx 0.9 postgres tanpa TLS + Tera 1.20). Server `rust/crates/central` render Metronic dari Postgres di :8088.
- 2026-06-09 — **Fase 1 SELESAI menyeluruh.** Login+session, RBAC (CurrentUser extractor, Superadmin bypass, 50 izin termuat), hardening (CSRF, rate-limit lockout bertingkat, activity_log, last_login). Deps baru: bcrypt 0.19, tower-sessions 0.15, uuid v4. Catatan: session masih MemoryStore (reset saat restart) — ganti store persisten saat Fase 3.
- 2026-06-09 — **Admin shell Metronic di-port** (atas permintaan user agar tampilan tuntas di Fase 1): `layout/app.html` (base) + `layout/{menu,sidebar,footer}.html` + `view.rs` (`base_context`). Menu ber-permission, link → path Rust. Avatar pakai default Metronic (`/assets/media/avatars/blank.png`) karena `/storage` belum disajikan. Halaman Fase 2+ tinggal `{% extends "layout/app.html" %}`.
- 2026-06-09 — **Login & Dashboard disamakan penuh dgn Laravel** (permintaan user). Login: restore Manual Book (tombol melayang + viewer PDF.js) + loader sukses (three-dot+progress) + countdown lockout 429. Dashboard analytics di-port (4 kartu omzet/HPP/expense/laba, grafik ApexCharts omzet-vs-target, top 5 menu, tabel menu habis, modal HPP) dgn query data nyata di Rust (`format_rupiah` + `generate_series` chart). Data 0/kosong krn belum ada transaksi, query siap. `{% block scripts %}` ditambah di `app.html`. **Ditunda:** HPP detail DataTable (ajax server-side) → Fase 2.

---

## 14. Referensi
- Axum: https://docs.rs/axum
- SQLx: https://docs.rs/sqlx
- Tera: https://keats.github.io/tera/docs/
- Tauri: https://tauri.app
