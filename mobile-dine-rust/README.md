# mobile-dine-rust — DineSync POS Mobile

Dua project yang membentuk aplikasi mobile DineSync POS. Keduanya punya
**repo sendiri** dan tidak ikut di dalam repo `dine-sync-pos-rust` — folder ini
hanya tempat keduanya bersebelahan supaya `dev.bat` bisa menyalakan semuanya.

| Folder | Isi | Repo |
|---|---|---|
| [`dine-rust-be`](dine-rust-be) | REST API Rust (Axum) + dokumentasi **Swagger UI** | [rendyirawann/dine-rust-be](https://github.com/rendyirawann/dine-rust-be) |
| [`dine-rust-fe`](dine-rust-fe) | Aplikasi **Flutter** yang hanya memanggil API tersebut | [rendyirawann/dine-rust-fe](https://github.com/rendyirawann/dine-rust-fe) |

Setelah mengklon `dine-sync-pos-rust`, klon keduanya ke sini:

```bash
cd mobile-dine-rust
git clone https://github.com/rendyirawann/dine-rust-be.git
git clone https://github.com/rendyirawann/dine-rust-fe.git
```

Tanpa itu, `dev.bat` hanya bisa menjalankan tab Web — tab API dan App akan
melaporkan foldernya tidak ada.

```
┌──────────────────┐   HTTPS/JSON    ┌──────────────────┐
│  dine-rust-fe    │ ──────────────► │  dine-rust-be    │
│  (Flutter, HP)   │ ◄────────────── │  (Axum, :8090)   │
└──────────────────┘   Bearer JWT    └────────┬─────────┘
                                              │ SQL
┌──────────────────┐                          ▼
│ dine-sync-pos-   │  SQL      ┌────────────────────────────┐
│ rust (web/desktop)├─────────►│ PostgreSQL dinesync_pos_rust│
└──────────────────┘           └────────────────────────────┘
```

Web dan mobile membaca-menulis **database yang sama**. Tidak ada duplikasi tabel
dan tidak ada sinkronisasi yang perlu dijaga: transaksi dari HP langsung muncul
di layar web, dan sebaliknya. Role/permission juga satu sumber (tabel `spatie`),
jadi mengubah izin di salah satu sisi langsung berlaku di keduanya.

---

## Menjalankan — satu perintah

Dari **akar repo** (`dine-sync-pos-rust`), `dev.bat` membuka satu jendela
Windows Terminal berisi tab Web + API + App + Buka:

```bat
dev.bat              :: Web :8088, API :8090, App :5000 (Brave), buka Swagger
dev.bat noapp        :: tanpa tab App - sedang pakai HP/emulator
dev.bat api          :: hanya API + Swagger
dev.bat app          :: hanya aplikasi Flutter
dev.bat server       :: App mode web-server (buka Brave dengan profil sendiri)
dev.bat stop         :: hentikan semuanya
dev.bat dryrun       :: lihat perintah wt-nya tanpa menjalankan
```

Tab App diarahkan ke API lokal lewat `--dart-define`, jadi layar login tidak
perlu diisi alamat server. Port dibuat tetap (8088/8090/5000) supaya origin
CORS dan alamat yang diketik tidak berubah tiap run. Tab yang portnya sudah
dipakai otomatis dilewati, jadi `dev.bat` aman dijalankan berulang.

Prasyarat: `cargo`, `flutter`, PostgreSQL di `:5433`, dan Windows Terminal.
`dev.bat` memeriksa semuanya di awal dan melaporkan yang kurang.

Detail tiap tab ada di [`scripts/`](../scripts/): `run-web.bat`, `run-api.bat`,
`run-app.bat`, `open-urls.bat`, `wait-port.bat`, `stop.ps1`.

## Menjalankan manual

```bash
# 1. Backend API
cd mobile-dine-rust/dine-rust-be
cp .env.example .env         # isi DATABASE_URL (sama dengan rust/crates/central/.env) & JWT_SECRET
cargo run
#    → Swagger UI: http://localhost:8090/swagger-ui

# 2. Aplikasi mobile
cd ../dine-rust-fe
flutter pub get
flutter run
#    → di layar login, tekan kartu "Server" bila perlu mengganti alamat:
#      web/desktop       : http://127.0.0.1:8090  (bawaan)
#      emulator Android  : http://10.0.2.2:8090   (bawaan)
#      HP fisik (Wi-Fi)  : http://<IP-komputer>:8090
```

Login memakai akun yang sama dengan aplikasi web (email, nomor WhatsApp, atau
username + password).

> Agar HP fisik bisa terhubung, `BIND_ADDR` harus `0.0.0.0:8090` (default) dan
> port 8090 diizinkan di firewall Windows.

---

## Cakupan

API menyediakan **106 operasi** pada **81 path**, mencakup seluruh modul web:
Dashboard, Kasir (peta meja → pesanan → pembayaran → struk), Shift, Dapur,
Antrian, Data Master (kategori, menu, meja+QR, promo, supplier, bahan baku,
resep, pengaturan toko), Finance (pengeluaran, budget & target), Stok (stok
terkini, stok masuk, opname, kartu stok), Laporan (penjualan & per item),
Resources (user & role), Log Aktivitas, pembayaran Midtrans, realtime, serta
endpoint publik untuk self-order pelanggan dan kiosk antrian.

Tabel pemetaan lengkap "route web → endpoint mobile" ada di
[`dine-rust-be/README.md`](dine-rust-be/README.md#4-peta-endpoint--modul-web).

Aplikasi Flutter mengimplementasikan semua modul di atas, dengan tab bawah untuk
Dashboard/Kasir/Dapur/Antrian dan halaman **Lainnya** untuk modul sisanya —
susunannya mengikuti menu web, dan tab yang tampil menyesuaikan izin user.

---

## Catatan penting

1. **Harga selalu dihitung server.** Aplikasi mobile hanya mengirim
   `menu_id`, `qty`, dan `notes`; harga, diskon promo, pajak, dan grand total
   dibaca ulang dari database oleh backend. Angka di keranjang hanya pratinjau.
2. **Autentikasi JWT Bearer.** Web memakai session cookie yang tidak bisa
   dipakai klien mobile. Password bcrypt dan aturan penguncian login tetap sama.
3. **Tanpa mode offline.** Kemampuan local-first (SQLite + outbox) adalah milik
   aplikasi desktop per-perangkat di `rust/crates/central`. Aplikasi mobile
   mensyaratkan koneksi ke backend.
4. **Logika bisnis tidak diduplikasi di klien.** Gerbang shift, pemotongan stok
   FEFO beserta HPP, aturan mengosongkan meja, dan penyesuaian stock opname
   semuanya tetap di server, dengan implementasi yang sama seperti web.
