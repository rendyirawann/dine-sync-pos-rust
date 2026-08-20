@echo off
setlocal enabledelayedexpansion

rem ======================================================================
rem  DineSync POS - jalankan SEMUANYA dengan satu perintah.
rem
rem  Membuka satu jendela Windows Terminal berisi tab-tab:
rem
rem    Web    Aplikasi web/desktop (rust/crates/central)      :8088
rem    API    REST API mobile + Swagger UI (dine-rust-be)      :8090
rem    App    Aplikasi mobile debug di Brave (dine-rust-fe)    :5000
rem    Buka   membuka web + Swagger di Brave, lalu tutup sendiri
rem
rem  Ketiganya TIDAK bisa disatukan dalam satu tab: masing-masing adalah
rem  proses yang terus berjalan dan menampilkan lognya sendiri. Menjejalkan
rem  semuanya ke satu jendela berarti log yang saling menimpa dan tidak ada
rem  cara menghentikan satu service tanpa mematikan yang lain.
rem
rem  Tab App menunggu API siap lebih dulu, jadi urutan start tidak perlu
rem  diatur sendiri.
rem
rem  Pemakaian (boleh digabung):
rem    dev.bat                  semua
rem    dev.bat noapp            tanpa tab App (sedang pakai HP/emulator)
rem    dev.bat noweb            tanpa tab Web (:8088 tidak dipakai)
rem    dev.bat app              HANYA tab App
rem    dev.bat api              HANYA tab API + Buka
rem    dev.bat server           App pakai mode web-server (buka Brave sendiri)
rem    dev.bat stop             hentikan semua yang sedang berjalan
rem    dev.bat dryrun           cetak baris perintah wt, jangan jalankan
rem ======================================================================

set "ROOT=%~dp0"
if "%ROOT:~-1%"=="\" set "ROOT=%ROOT:~0,-1%"

rem ----------------------------------------------------------------------
rem  Port dibuat TETAP, bukan acak.
rem
rem  8088 di-hardcode di rust/crates/central/src/main.rs, 8090 berasal dari
rem  BIND_ADDR pada mobile-dine-rust/dine-rust-be/.env, dan 5000 muncul
rem  sebagai origin pada permintaan CORS ke API. Port yang berganti tiap
rem  run berarti alamat yang harus diketik ulang setiap kali.
rem ----------------------------------------------------------------------
set "WEB_PORT=8088"
set "API_PORT=8090"
set "APP_PORT=5000"
set "DB_PORT=5433"

rem ----------------------------------------------------------------------
rem  Deteksi klik-ganda dari Explorer.
rem
rem  Saat diklik-ganda, cmd dijalankan dengan /c sehingga jendelanya tertutup
rem  begitu skrip selesai - beserta seluruh pesan yang baru saja tercetak.
rem  Itulah sebabnya kegagalan apa pun terlihat seperti "jendela langsung
rem  tertutup, tidak terjadi apa-apa". Bila terdeteksi, jendela ditahan di
rem  akhir supaya pesannya sempat dibaca.
rem ----------------------------------------------------------------------
set "HOLD="
echo %cmdcmdline% | find /i "%~nx0" >nul 2>&1
if not errorlevel 1 set "HOLD=1"

rem ----------------------------------------------------------------------
rem  Argumen dibaca sebagai daftar, bukan hanya %1, supaya `noweb server`
rem  bekerja. Menerima satu argumen saja akan membuat kombinasi yang wajar
rem  gagal tanpa pesan apa pun.
rem ----------------------------------------------------------------------
set "OPT_NOAPP="
set "OPT_NOWEB="
set "OPT_APPONLY="
set "OPT_APIONLY="
set "OPT_SERVER="
set "OPT_STOP="
set "OPT_DRYRUN="
set "OPT_BAD="

:parse
if "%~1"=="" goto :parsed
if /i "%~1"=="noapp"  set "OPT_NOAPP=1"   & shift & goto :parse
if /i "%~1"=="noweb"  set "OPT_NOWEB=1"   & shift & goto :parse
if /i "%~1"=="app"    set "OPT_APPONLY=1" & shift & goto :parse
if /i "%~1"=="api"    set "OPT_APIONLY=1" & shift & goto :parse
if /i "%~1"=="server" set "OPT_SERVER=1"  & shift & goto :parse
if /i "%~1"=="stop"   set "OPT_STOP=1"    & shift & goto :parse
if /i "%~1"=="dryrun" set "OPT_DRYRUN=1"  & shift & goto :parse
set "OPT_BAD=%~1"
goto :parsed

:parsed
if defined OPT_BAD (
    echo   [x] Argumen tidak dikenal: %OPT_BAD%
    echo       Pilihan: noapp, noweb, app, api, server, stop, dryrun
    echo.
    goto :end
)

if defined OPT_STOP (
    powershell -NoProfile -ExecutionPolicy Bypass -File "%ROOT%\scripts\stop.ps1"
    goto :end
)

echo.
echo   DineSync POS - lingkungan pengembangan
echo   =====================================
echo.

rem ----------------------------------------------------------------------
rem  Pemeriksaan awal. Yang hilang dilaporkan SEKARANG, bukan sebagai tab
rem  yang mati beberapa detik kemudian di balik tab lain.
rem ----------------------------------------------------------------------
set "MISSING="
call :need cargo   "Rust (https://rustup.rs)"
call :need flutter "Flutter (tambahkan <flutter>\bin ke PATH)"

if defined MISSING (
    echo.
    echo   Perkakas di atas belum ada di PATH. Tab yang membutuhkannya akan
    echo   berhenti dengan pesan yang menjelaskan, jadi sisanya tetap bisa
    echo   dipakai.
    echo.
)

rem ----------------------------------------------------------------------
rem  PostgreSQL hanya DIPERIKSA, tidak dijalankan - servicenya di luar
rem  kendali repo ini. Tanpa peringatan ini, tab Web dan API akan menyala
rem  lalu setiap halaman gagal dengan galat basis data yang tidak jelas
rem  sebabnya.
rem ----------------------------------------------------------------------
call :probe %DB_PORT%
if errorlevel 1 (
    echo   [^^!] PostgreSQL tidak terdeteksi di 127.0.0.1:%DB_PORT%.
    echo       Nyalakan dulu servicenya - Web dan API butuh database ini.
    echo.
) else (
    echo   [ok] PostgreSQL :%DB_PORT%
    echo.
)

rem ----------------------------------------------------------------------
rem  dine-rust-be dan dine-rust-fe adalah repo TERSENDIRI. Pada klon baru
rem  dine-sync-pos-rust, foldernya belum ada - dilaporkan di sini supaya
rem  tidak muncul sebagai tab yang mati beberapa detik kemudian.
rem ----------------------------------------------------------------------
set "NO_BE="
set "NO_FE="
if not exist "%ROOT%\mobile-dine-rust\dine-rust-be\Cargo.toml" set "NO_BE=1"
if not exist "%ROOT%\mobile-dine-rust\dine-rust-fe\pubspec.yaml" set "NO_FE=1"

if defined NO_BE echo   [x] dine-rust-be belum diklon - tab API dilewati.
if defined NO_FE echo   [x] dine-rust-fe belum diklon - tab App dilewati.

if defined NO_BE goto :clonehint
if defined NO_FE goto :clonehint
goto :cloned

:clonehint
echo.
echo       Keduanya repo tersendiri. Klon ke mobile-dine-rust\:
echo         git clone https://github.com/rendyirawann/dine-rust-be.git
echo         git clone https://github.com/rendyirawann/dine-rust-fe.git
echo.

:cloned
where wt.exe >nul 2>&1
if errorlevel 1 goto :manual

rem ----------------------------------------------------------------------
rem  Tentukan tab mana yang dibuka.
rem ----------------------------------------------------------------------
set "WEB_TAB=1"
set "API_TAB=1"
set "APP_TAB=1"
set "OPEN_TAB=1"

if defined OPT_NOWEB set "WEB_TAB="
if defined OPT_NOAPP set "APP_TAB="

rem Folder yang belum diklon tidak bisa dijalankan sama sekali - ini menang
rem atas pilihan tab apa pun, termasuk `dev.bat app`.
if defined NO_BE set "API_TAB="
if defined NO_FE set "APP_TAB="

if defined OPT_APPONLY (
    set "WEB_TAB="
    set "API_TAB="
    set "APP_TAB=1"
    set "OPEN_TAB="
)
if defined OPT_APIONLY (
    set "WEB_TAB="
    set "APP_TAB="
    set "API_TAB=1"
    set "OPEN_TAB=1"
)

rem ----------------------------------------------------------------------
rem  Lewati tab yang portnya sudah dipakai.
rem
rem  Menjalankan service kedua di atas yang pertama hanya menghasilkan
rem  galat "address already in use" yang menyesatkan - terlihat seperti
rem  skripnya rusak padahal servicenya memang sudah jalan.
rem ----------------------------------------------------------------------
if defined WEB_TAB call :skip_if_used %WEB_PORT% WEB_TAB Web http://127.0.0.1:%WEB_PORT%/admin/login
if defined API_TAB call :skip_if_used %API_PORT% API_TAB API http://127.0.0.1:%API_PORT%/health
if defined APP_TAB call :skip_if_used %APP_PORT% APP_TAB App http://127.0.0.1:%APP_PORT%/

if not defined WEB_TAB if not defined API_TAB if not defined APP_TAB (
    echo   Semua service sudah berjalan - tidak ada tab yang perlu dibuka.
    echo.
    goto :info
)

rem ----------------------------------------------------------------------
rem  Susun baris perintah wt.
rem
rem  Direktori kerja diatur lewat -d sehingga path skripnya relatif dan
rem  tidak butuh tanda kutip bersarang - sumber kerusakan paling sering
rem  pada baris perintah wt. Judul tab juga dibuat tanpa spasi karena
rem  parser wt lebih aman begitu.
rem ----------------------------------------------------------------------
set "APP_MODE=brave"
if defined OPT_SERVER set "APP_MODE=server"

set "CMD="
if defined WEB_TAB set "CMD=new-tab --title Web-%WEB_PORT% -d "%ROOT%" cmd /k scripts\run-web.bat ;"
if defined API_TAB set "CMD=!CMD! new-tab --title API-%API_PORT% -d "%ROOT%" cmd /k scripts\run-api.bat ;"
if defined APP_TAB set "CMD=!CMD! new-tab --title App-%APP_PORT% -d "%ROOT%" cmd /k scripts\run-app.bat %APP_PORT% %APP_MODE% ;"
if defined OPEN_TAB set "CMD=!CMD! new-tab --title Buka -d "%ROOT%" cmd /c scripts\open-urls.bat %APP_MODE% %APP_PORT%"

rem Buang tanda `;` menggantung bila tab Buka tidak dipakai.
if not defined OPEN_TAB if "!CMD:~-1!"==";" set "CMD=!CMD:~0,-1!"

rem Rapikan spasi di depan (muncul bila tab pertama dilewati). wt sendiri
rem memaafkannya, tetapi keluaran `dryrun` jadi lebih mudah dibaca.
:trim
if "!CMD:~0,1!"==" " set "CMD=!CMD:~1!" & goto :trim

if defined OPT_DRYRUN (
    echo.
    echo   wt.exe !CMD!
    echo.
    goto :end
)

echo   Membuka Windows Terminal...
start "" wt.exe -w new !CMD!

rem `start` tidak melaporkan kegagalan wt. Tanpa pemeriksaan ini, baris
rem perintah yang salah akan terlihat persis seperti "tidak terjadi
rem apa-apa" - jendela ini tertutup dan tidak ada tab yang muncul.
if defined API_TAB     call :verify run-api.bat
if not defined API_TAB call :verify run-app.bat
if errorlevel 1 goto :wtfail
goto :info

:info
echo.
echo   Web/desktop  http://127.0.0.1:%WEB_PORT%/admin/login
echo   Swagger UI   http://127.0.0.1:%API_PORT%/swagger-ui
echo   Aplikasi     http://127.0.0.1:%APP_PORT%
echo.
echo   Tab App membuka Brave sendiri dan sudah diarahkan ke API di atas
echo   ^(lewat --dart-define^), jadi layar login tidak perlu diisi alamat.
echo.
echo   r = hot restart   q = keluar   ^(tekan di tab App^)
echo.
echo   Menghentikan semuanya:  dev.bat stop
echo.
goto :end

:wtfail
echo.
echo   [x] Windows Terminal terbuka, tetapi tabnya tidak jalan.
echo.
echo       Coba jalankan satu per satu untuk melihat pesan galatnya:
echo         scripts\run-web.bat
echo         scripts\run-api.bat
echo         scripts\run-app.bat
echo.
goto :end

:manual
echo   [x] Windows Terminal ^(wt.exe^) tidak ditemukan.
echo       Pasang dari Microsoft Store agar semuanya muat dalam satu jendela.
echo.
echo       Sementara itu, jalankan tiga perintah ini di tiga jendela:
echo.
echo         1^)  scripts\run-web.bat
echo         2^)  scripts\run-api.bat
echo         3^)  scripts\run-app.bat
echo.
echo       Semuanya relatif terhadap "%ROOT%".
echo.
goto :end

rem ----------------------------------------------------------------------
rem  Sengaja memakai goto, bukan `if ... ( ... ) else ( ... )`.
rem
rem  Isi %~2 memuat tanda kurung ("Rust (https://rustup.rs)"). Di dalam blok
rem  berkurung, cmd mengganti %~2 pada saat PARSE - kurung tutup di dalamnya
rem  menutup blok lebih awal, sehingga baris sesudahnya ikut dijalankan tanpa
rem  syarat. Akibatnya MISSING selalu terisi dan peringatan muncul walau
rem  semua perkakas sebenarnya ada.
rem ----------------------------------------------------------------------
:need
where %~1 >nul 2>&1
if not errorlevel 1 goto :need_ok
echo   [x] %~1 tidak ada di PATH - %~2
set "MISSING=1"
exit /b 0

:need_ok
echo   [ok] %~1
exit /b 0

rem ----------------------------------------------------------------------
rem  skip_if_used <port> <nama-variabel-tab> <label> [url-kesehatan]
rem
rem  Port terpakai belum tentu berarti service KITA yang jalan. Docker,
rem  IIS, atau sisa proses lain bisa memegangnya. Membedakannya penting:
rem  "sudah berjalan - dilewati" pada port milik Docker membuat aplikasi
rem  mobile memanggil Docker dan gagal dengan galat yang tak masuk akal.
rem  Yang membedakan hanya balasan HTTP-nya.
rem ----------------------------------------------------------------------
:skip_if_used
call :probe %~1
if errorlevel 1 exit /b 0
set "%~2="
if "%~4"=="" goto :skip_plain
call :http_ok "%~4"
if errorlevel 1 goto :skip_foreign

:skip_plain
echo   %~3 sudah berjalan di :%~1 - tabnya dilewati.
exit /b 0

:skip_foreign
echo   [^^!] :%~1 dipakai proses LAIN, bukan %~3 DineSync:
call :port_owner %~1
echo       Hentikan proses itu dulu ^(atau ubah portnya^), lalu jalankan ulang.
exit /b 0

rem ----------------------------------------------------------------------
rem  http_ok <url> - benar bila URL menjawab status HTTP apa pun.
rem
rem  Status galat pun dihitung "menjawab": 401/404 dari service kita tetap
rem  membuktikan itu memang HTTP server kita, bukan port yang kebetulan
rem  terbuka. Yang gagal adalah koneksi yang ditutup atau tidak berbalas.
rem ----------------------------------------------------------------------
:http_ok
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "try { Invoke-WebRequest -Uri %1 -UseBasicParsing -TimeoutSec 5 ^| Out-Null; exit 0 }" ^
  "catch { if ($_.Exception.Response) { exit 0 } else { exit 1 } }" >nul 2>&1
exit /b %errorlevel%

rem ----------------------------------------------------------------------
rem  port_owner <port> - sebutkan proses pemegang port supaya operator
rem  tidak perlu menebak lewat netstat.
rem ----------------------------------------------------------------------
:port_owner
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "Get-NetTCPConnection -LocalPort %1 -State Listen -EA SilentlyContinue |" ^
  "Select-Object -Expand OwningProcess -Unique | ForEach-Object {" ^
  "  $p = Get-Process -Id $_ -EA SilentlyContinue;" ^
  "  '      {0} (pid {1})' -f $p.ProcessName, $_ }" 2>nul
exit /b 0

:probe
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$c=New-Object Net.Sockets.TcpClient; try{$c.Connect('127.0.0.1',%~1);$c.Close();exit 0}catch{exit 1}" >nul 2>&1
exit /b %errorlevel%

rem ----------------------------------------------------------------------
rem  Tunggu sampai tab yang diminta benar-benar punya proses berjalan.
rem ----------------------------------------------------------------------
:verify
powershell -NoProfile -ExecutionPolicy Bypass -Command ^
  "$d=(Get-Date).AddSeconds(12);" ^
  "while((Get-Date) -lt $d){" ^
  "  $p = Get-CimInstance Win32_Process -Filter \"Name='cmd.exe'\" -EA SilentlyContinue |" ^
  "       Where-Object { $_.CommandLine -like '*%~1*' };" ^
  "  if ($p) { exit 0 }; Start-Sleep -Milliseconds 500" ^
  "}; exit 1" >nul 2>&1
exit /b %errorlevel%

:end
rem Bila skrip diklik-ganda dari Explorer, jendela ini tertutup begitu
rem skrip selesai - beserta seluruh pesan di atas. Tahan supaya terbaca.
if defined HOLD (
    echo.
    pause
)
endlocal
