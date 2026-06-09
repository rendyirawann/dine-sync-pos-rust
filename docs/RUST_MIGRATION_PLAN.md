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

### Fase 0 — Fondasi *(online dulu)*
- [ ] Install Rust toolchain (rustup) di Windows.
- [ ] Scaffold workspace Cargo: crate `central` + `device` + `shared`.
- [ ] Axum "hello" + koneksi ke PostgreSQL eksisting (read-only).
- [ ] Render **1 halaman Metronic** (mis. login/dashboard) via Tera dari data nyata.
- **Acceptance:** buka `localhost`, lihat 1 halaman Metronic yang datanya dari Postgres.

### Fase 1 — Auth + RBAC
- [ ] Login/logout + session.
- [ ] Port roles & permissions, middleware proteksi route.
- **Acceptance:** bisa login, route terproteksi sesuai role.

### Fase 2 — Slice Kasir/Order *(online)*
- [ ] Daftar menu, buat order, order detail, bayar tunai, cetak struk.
- [ ] Manajemen shift dasar.
- **Acceptance:** alur order tunai penuh end-to-end di Rust terhadap Postgres.

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

- [ ] **Fase 0** — Fondasi
- [ ] **Fase 1** — Auth + RBAC
- [ ] **Fase 2** — Slice Kasir (online)
- [ ] **Fase 3** — Local-first Kasir (sync engine)
- [ ] **Fase 4** — Lebarkan modul
- [ ] **Fase 5** — Integrasi & polish
- [ ] **Fase 6** — Cutover

**Log keputusan:**
- 2026-06-09 — Sepakat rewrite Rust local-first, pertahankan Metronic, stack Axum+SQLx+Tera, SQLite lokal + Postgres pusat. Toolchain Rust belum terinstal.
- 2026-06-09 — Skala dikonfirmasi: **1 outlet, tanpa multi-tenant**. Scan QR customer **online-only** (tidak offline). Project di-push ke GitHub: `rendyirawann/dine-sync-pos-rust`. Mulai Fase 0.

---

## 14. Referensi
- Axum: https://docs.rs/axum
- SQLx: https://docs.rs/sqlx
- Tera: https://keats.github.io/tera/docs/
- Tauri: https://tauri.app
