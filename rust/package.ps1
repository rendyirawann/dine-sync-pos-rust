# =====================================================================
# Packaging DineSync POS (Rust) — rakit distribusi .exe standalone per device.
# Jalankan: powershell -ExecutionPolicy Bypass -File rust\package.ps1
# Hasil: rust\dist\dinesync-pos\  (central.exe + assets + storage + .env)
# =====================================================================
$ErrorActionPreference = 'Stop'
$rust = $PSScriptRoot                       # .../rust
$repo = Split-Path -Parent $rust            # root repo
$exe  = Join-Path $rust 'target\release\central.exe'
$dist = Join-Path $rust 'dist\dinesync-pos'

if (-not (Test-Path $exe)) {
    Write-Host "central.exe rilis belum ada. Build dulu:" -ForegroundColor Yellow
    Write-Host "  cargo build --release --manifest-path rust\crates\central\Cargo.toml"
    exit 1
}

Write-Host "==> Menyiapkan folder distribusi: $dist"
if (Test-Path $dist) { Remove-Item $dist -Recurse -Force }
New-Item -ItemType Directory -Force -Path $dist | Out-Null

Write-Host "==> Menyalin central.exe"
Copy-Item $exe (Join-Path $dist 'central.exe')

Write-Host "==> Menyalin aset Metronic (public/assets)"
Copy-Item (Join-Path $repo 'public\assets') (Join-Path $dist 'assets') -Recurse

$storageSrc = Join-Path $repo 'storage\app\public'
if (Test-Path $storageSrc) {
    Write-Host "==> Menyalin storage (gambar menu/avatar)"
    Copy-Item $storageSrc (Join-Path $dist 'storage') -Recurse
} else {
    New-Item -ItemType Directory -Force -Path (Join-Path $dist 'storage') | Out-Null
}

Write-Host "==> Menulis .env.example, run.bat, README.txt"
@'
# Konfigurasi DineSync POS (device ini). Salin jadi ".env" lalu sesuaikan.
# Wajib: koneksi ke PostgreSQL pusat.
DATABASE_URL=postgres://postgres:PASSWORD@HOST_PUSAT:5432/dinesync_pos_rust?sslmode=disable

# Alamat publik aplikasi (untuk QR pelanggan & webhook Midtrans). Mis: https://pos.namatoko.com
# Kosongkan bila hanya dipakai lokal (QR akan pakai host saat dicetak).
PUBLIC_URL=

# Opsional: pembayaran online (kosongkan untuk nonaktif).
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_IS_PRODUCTION=false
'@ | Out-File -FilePath (Join-Path $dist '.env.example') -Encoding utf8

@'
@echo off
title DineSync POS
if not exist ".env" (
  echo [!] File .env belum ada. Salin .env.example menjadi .env lalu isi DATABASE_URL.
  pause
  exit /b 1
)
central.exe
pause
'@ | Out-File -FilePath (Join-Path $dist 'run.bat') -Encoding ascii

@'
DineSync POS — Aplikasi Kasir (Rust, local-first)
==================================================
1. Salin ".env.example" menjadi ".env".
2. Edit ".env": isi DATABASE_URL ke PostgreSQL pusat (dan kunci Midtrans bila pakai bayar online).
3. Jalankan "run.bat" (atau "central.exe").
4. Buka browser ke  http://127.0.0.1:8088/admin/login

Catatan:
- Template sudah tertanam di central.exe (tidak perlu folder templates).
- Folder "assets" & "storage" harus tetap di samping central.exe.
- "local.db" (data offline) dibuat otomatis di samping central.exe.
- Mode offline (Kasir/Dapur) tetap jalan saat server pusat mati; sinkron otomatis saat online.
'@ | Out-File -FilePath (Join-Path $dist 'README.txt') -Encoding utf8

$size = '{0:N1} MB' -f ((Get-ChildItem $dist -Recurse | Measure-Object Length -Sum).Sum / 1MB)
Write-Host "==> Selesai. Total: $size" -ForegroundColor Green
Write-Host "    Distribusi: $dist"
Write-Host "    Isi .env lalu jalankan run.bat di device tujuan."
