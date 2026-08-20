@echo off
rem ======================================================================
rem  wait-port.bat <port> [label] [detik]
rem
rem  Tunggu sampai ada yang mendengarkan di 127.0.0.1:<port>.
rem
rem  Dipakai supaya tab App tidak memuat aplikasi sebelum API siap - tanpa
rem  ini, layar login yang pertama muncul selalu menampilkan "tidak dapat
rem  menghubungi server" dan operator harus menebak bahwa cukup ditunggu.
rem
rem  Penantian dibatasi waktu, lalu tetap lanjut. Menunggu selamanya akan
rem  menyembunyikan penyebab sebenarnya (mis. PostgreSQL tidak berjalan);
rem  lebih baik perintahnya jalan dan mencetak galatnya sendiri.
rem ======================================================================
setlocal

set "PORT=%~1"
set "LABEL=%~2"
if "%LABEL%"=="" set "LABEL=port %PORT%"
set "LIMIT=%~3"
if "%LIMIT%"=="" set "LIMIT=90"

rem Cek cepat sekali dulu: kalau sudah siap, tidak perlu mencetak apa pun.
call :probe
if not errorlevel 1 goto :ready

echo   Menunggu %LABEL% di 127.0.0.1:%PORT% ^(maksimal %LIMIT% detik^)...
echo   ^(kompilasi Rust pertama kali bisa selama ini^)

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$deadline = (Get-Date).AddSeconds(%LIMIT%);" ^
  "while ((Get-Date) -lt $deadline) {" ^
  "  $c = New-Object Net.Sockets.TcpClient;" ^
  "  try { $c.Connect('127.0.0.1', %PORT%); $c.Close(); exit 0 }" ^
  "  catch { Start-Sleep -Milliseconds 800 }" ^
  "}; exit 1"

if errorlevel 1 (
    echo   [!] %LABEL% belum siap setelah %LIMIT% detik - dilanjutkan saja.
    echo       Bila aplikasi gagal memanggil API, periksa tab API lebih dulu.
    echo.
    endlocal & exit /b 1
)

:ready
echo   %LABEL% siap.
endlocal & exit /b 0

:probe
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$c = New-Object Net.Sockets.TcpClient;" ^
  "try { $c.Connect('127.0.0.1', %PORT%); $c.Close(); exit 0 } catch { exit 1 }" >nul 2>&1
exit /b %errorlevel%
