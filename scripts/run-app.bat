@echo off
setlocal

rem ======================================================================
rem  Tab App - aplikasi mobile DineSync di-debug lewat browser Brave.
rem
rem  Pemakaian:
rem    run-app.bat                 :5000, dibuka Flutter di Brave
rem    run-app.bat 5001            ganti port
rem    run-app.bat 5000 server     mode web-server: Flutter hanya melayani,
rem                                browser dibuka sendiri ^(pakai profil
rem                                Brave yang sudah ada beserta ekstensinya^)
rem
rem  Yang BISA diuji di browser: semua modul - login, dashboard, kasir,
rem  dapur, antrian, data master, finance, stok, laporan, user/role.
rem  Aplikasi ini murni memanggil API, tidak ada fitur yang bergantung
rem  pada perangkat keras HP.
rem ======================================================================

set "APP_PORT=%~1"
if "%APP_PORT%"=="" set "APP_PORT=5000"

set "MODE=%~2"
if "%MODE%"=="" set "MODE=brave"

rem 127.0.0.1, bukan localhost. Keduanya menunjuk mesin yang sama, tetapi
rem bagi CORS keduanya origin YANG BERBEDA - halaman di 127.0.0.1:5000 yang
rem memanggil localhost:8090 dihitung lintas origin oleh browser.
set "APP_HOST=127.0.0.1"
set "API_URL=http://127.0.0.1:8090"

title DineSync - App :%APP_PORT%

if not exist "%~dp0..\mobile-dine-rust\dine-rust-fe\pubspec.yaml" goto :norepo
cd /d "%~dp0..\mobile-dine-rust\dine-rust-fe"

rem ----------------------------------------------------------------------
rem  Cari SDK Flutter.
rem
rem  Ada di PATH belum tentu bisa dipakai: bila SDK berada di drive lepasan
rem  yang sedang terputus, perintahnya gagal dengan pesan membingungkan
rem  "The system cannot find the drive specified." Jadi lokasinya dicari
rem  dulu, lalu dipastikan berkasnya benar-benar ada.
rem ----------------------------------------------------------------------
set "SDK="
for /f "delims=" %%F in ('where flutter 2^>nul') do if not defined SDK set "SDK=%%~dpF"
if not defined SDK if exist "D:\src\flutter\bin\flutter.bat" set "SDK=D:\src\flutter\bin\"
if not defined SDK if exist "C:\src\flutter\bin\flutter.bat" set "SDK=C:\src\flutter\bin\"

if not defined SDK goto :nosdk
if not exist "%SDK%flutter.bat" goto :nosdk
set "FLUTTER=%SDK%flutter.bat"

rem ----------------------------------------------------------------------
rem  Browser.
rem
rem  Flutter memakai variabel CHROME_EXECUTABLE untuk device "chrome".
rem  Brave berbasis Chromium, jadi cukup diarahkan ke sana - DevTools dan
rem  hot restart tetap bekerja penuh.
rem
rem  Memakai goto, bukan blok berkurung: path Brave bisa memuat "(x86)" dan
rem  kurung tutupnya akan menutup blok lebih awal saat cmd mem-parse.
rem ----------------------------------------------------------------------
set "BRAVE=%ProgramFiles%\BraveSoftware\Brave-Browser\Application\brave.exe"
if not exist "%BRAVE%" set "BRAVE=%ProgramFiles(x86)%\BraveSoftware\Brave-Browser\Application\brave.exe"
if not exist "%BRAVE%" set "BRAVE=%LOCALAPPDATA%\BraveSoftware\Brave-Browser\Application\brave.exe"

set "DEVICE=chrome"
set "BROWSER=Chrome"

if /i "%MODE%"=="server" goto :as_server
if not exist "%BRAVE%" goto :no_brave

set "CHROME_EXECUTABLE=%BRAVE%"
set "BROWSER=Brave"
goto :ready

:no_brave
echo.
echo   [!] Brave tidak ditemukan - Flutter akan memakai Chrome bawaan.
echo       Bila Chrome juga tidak ada, jalankan: run-app.bat %APP_PORT% server
echo.
goto :ready

:as_server
set "DEVICE=web-server"
set "BROWSER=buka manual di browser"
goto :ready

:ready
echo.
echo   DineSync - App ^(debug web^)
echo   ---------------------------------------------
echo   Aplikasi : http://%APP_HOST%:%APP_PORT%
echo   API      : %API_URL%
echo   Browser  : %BROWSER%
echo.
echo   Alamat API disuntikkan lewat --dart-define, jadi layar login tidak
echo   perlu diisi alamat server. Tetap bisa diganti dari kartu "Server"
echo   di layar login bila ingin menunjuk perangkat lain.
echo.
echo   Skrip ini TIDAK menjalankan API. Jalankan di tab lain:
echo       scripts\run-api.bat
echo   atau pakai dev.bat di akar repo untuk semua sekaligus.
echo.
echo   r = hot restart   q = keluar
echo   ---------------------------------------------
echo.

rem Tunggu API supaya layar login tidak langsung menampilkan
rem "tidak dapat menghubungi server" pada muatan pertama.
call "%~dp0wait-port.bat" 8090 API 180

rem --web-hostname + --web-port dibuat tetap agar origin-nya stabil; tanpa
rem itu Flutter memilih port acak dan CORS gagal setiap kali portnya ganti.
rem
rem SENGAJA TIDAK memakai --disable-web-security. Mematikan CORS di browser
rem membuat masalah konfigurasi tidak terlihat saat debug lalu muncul di
rem produksi, tempat tidak ada flag yang bisa mematikannya. Bila permintaan
rem diblokir, perbaiki CORS_ORIGINS di .env milik API - itu memang yang salah.
"%FLUTTER%" run -d %DEVICE% ^
  --web-hostname=%APP_HOST% ^
  --web-port=%APP_PORT% ^
  --dart-define=API_BASE_URL=%API_URL%

endlocal
exit /b 0

:norepo
echo.
echo   [x] mobile-dine-rust\dine-rust-fe belum ada.
echo.
echo       dine-rust-fe adalah repo tersendiri. Klon dulu:
echo         cd mobile-dine-rust
echo         git clone https://github.com/rendyirawann/dine-rust-fe.git
echo.
pause
endlocal
exit /b 1

:nosdk
echo.
echo   [X] SDK Flutter tidak dapat dijangkau.
echo.
echo   Penyebab paling sering: SDK berada di drive lepasan ^(mis. D:^)
echo   yang sedang terputus. Cek di File Explorer apakah drive-nya muncul.
echo.
echo   Solusi cepat   : sambungkan kembali drive-nya, lalu jalankan ulang.
echo   Solusi permanen: pindahkan folder flutter ke C:\src\flutter
echo                    lalu perbarui PATH ke C:\src\flutter\bin
echo.
pause
endlocal
exit /b 1
