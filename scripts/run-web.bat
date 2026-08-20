@echo off
rem ======================================================================
rem  Tab Web - aplikasi web/desktop DineSync (rust/crates/central) di :8088.
rem
rem  Inilah aplikasi yang menggantikan Laravel: Axum + Tera merender UI
rem  Metronic, dengan SQLite lokal untuk mode local-first.
rem
rem  Dijalankan dari folder workspace `rust` dengan `-p central` supaya
rem  target/ dipakai bersama tab lain - lebih hemat daripada tiap crate
rem  membangun dependensinya sendiri.
rem ======================================================================
title DineSync - Web :8088
cd /d "%~dp0..\rust"

where cargo >nul 2>&1
if errorlevel 1 goto :nocargo

if not exist "crates\central\.env" goto :noenv

echo.
echo   DineSync - Web/Desktop
echo   ---------------------------------------------
echo   Alamat  : http://127.0.0.1:8088
echo   Login   : http://127.0.0.1:8088/admin/login
echo   Kiosk   : http://127.0.0.1:8088/kiosk
echo   Display : http://127.0.0.1:8088/display
echo.
echo   Kompilasi pertama memakan waktu beberapa menit.
echo   Ctrl+C untuk menghentikan.
echo   ---------------------------------------------
echo.

cargo run -p central
goto :eof

:nocargo
echo.
echo   [x] cargo tidak ditemukan di PATH.
echo       Pasang Rust dari https://rustup.rs lalu buka terminal baru.
echo.
pause
exit /b 1

:noenv
echo.
echo   [x] rust\crates\central\.env tidak ada.
echo       Berkas ini memuat DATABASE_URL. Tanpa itu, central berhenti
echo       saat start dengan pesan "DATABASE_URL belum di-set".
echo.
pause
exit /b 1
