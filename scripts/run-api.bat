@echo off
rem ======================================================================
rem  Tab API - REST API mobile + Swagger UI (mobile-dine-rust\dine-rust-be)
rem  di :8090.
rem
rem  Memakai database PostgreSQL yang SAMA dengan tab Web, jadi keduanya
rem  boleh menyala bersamaan tanpa migrasi atau sinkronisasi apa pun.
rem
rem  BIND_ADDR bawaannya 0.0.0.0:8090 supaya HP di jaringan yang sama bisa
rem  ikut mengaksesnya - bukan hanya browser di komputer ini.
rem ======================================================================
title DineSync - API :8090

rem dine-rust-be adalah repo TERSENDIRI; pada klon baru dine-sync-pos-rust
rem foldernya belum ada. Diperiksa sebelum `cd` karena `cd` yang gagal hanya
rem meninggalkan skrip ini berjalan di folder yang salah.
if not exist "%~dp0..\mobile-dine-rust\dine-rust-be\Cargo.toml" goto :norepo
cd /d "%~dp0..\mobile-dine-rust\dine-rust-be"

where cargo >nul 2>&1
if errorlevel 1 goto :nocargo

if not exist ".env" goto :noenv

echo.
echo   DineSync - Mobile API
echo   ---------------------------------------------
echo   Swagger : http://127.0.0.1:8090/swagger-ui
echo   OpenAPI : http://127.0.0.1:8090/api-docs/openapi.json
echo   Health  : http://127.0.0.1:8090/health
echo.
echo   Login di Swagger: POST /api/v1/auth/login, lalu tekan Authorize
echo   dan tempel tokennya.
echo.
echo   Kompilasi pertama memakan waktu beberapa menit.
echo   Ctrl+C untuk menghentikan.
echo   ---------------------------------------------
echo.

cargo run
goto :eof

:norepo
echo.
echo   [x] mobile-dine-rust\dine-rust-be belum ada.
echo.
echo       dine-rust-be adalah repo tersendiri. Klon dulu:
echo         cd mobile-dine-rust
echo         git clone https://github.com/rendyirawann/dine-rust-be.git
echo.
pause
exit /b 1

:nocargo
echo.
echo   [x] cargo tidak ditemukan di PATH.
echo       Pasang Rust dari https://rustup.rs lalu buka terminal baru.
echo.
pause
exit /b 1

:noenv
echo.
echo   [x] mobile-dine-rust\dine-rust-be\.env tidak ada.
echo       Salin .env.example menjadi .env lalu isi DATABASE_URL
echo       ^(sama dengan rust\crates\central\.env^) dan JWT_SECRET.
echo.
pause
exit /b 1
