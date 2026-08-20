@echo off
rem ======================================================================
rem  Tab Buka - buka halaman yang perlu dilihat di Brave setelah servicenya
rem  siap, lalu tab ini menutup dirinya sendiri (dipanggil dengan cmd /c
rem  dari dev.bat), jadi tidak menyisakan tab kosong.
rem
rem  Pemakaian: open-urls.bat [mode-app] [port-app]
rem
rem  Aplikasi mobile TIDAK dibuka di sini bila mode-nya `brave` - Flutter
rem  membuka tabnya sendiri. Membukanya dua kali hanya menghasilkan satu
rem  tab mati yang memuat sebelum bundelnya jadi.
rem ======================================================================
setlocal

set "MODE=%~1"
if "%MODE%"=="" set "MODE=brave"

set "APP_PORT=%~2"
if "%APP_PORT%"=="" set "APP_PORT=5000"

set "BRAVE=%ProgramFiles%\BraveSoftware\Brave-Browser\Application\brave.exe"
if not exist "%BRAVE%" set "BRAVE=%ProgramFiles(x86)%\BraveSoftware\Brave-Browser\Application\brave.exe"
if not exist "%BRAVE%" set "BRAVE=%LOCALAPPDATA%\BraveSoftware\Brave-Browser\Application\brave.exe"

set "URL_API=http://127.0.0.1:8090/swagger-ui"
set "URL_WEB=http://127.0.0.1:8088/admin/login"
set "URL_APP=http://127.0.0.1:%APP_PORT%"

rem API didahulukan: Swagger yang paling sering dibuka, dan aplikasi mobile
rem tidak berguna tanpanya. Batas 300 detik karena kompilasi Rust pertama
rem kali di mesin dingin bisa selama itu.
call "%~dp0wait-port.bat" 8090 API 300
if errorlevel 1 goto :skip

rem Web/desktop opsional - bila tabnya tidak dibuka (dev.bat noweb / api),
rem penantian ini habis waktu lalu dilewati tanpa menggagalkan yang lain.
set "OPEN_WEB=1"
call "%~dp0wait-port.bat" 8088 Web 30
if errorlevel 1 set "OPEN_WEB="

set "TARGETS="%URL_API%""
if defined OPEN_WEB set "TARGETS=%TARGETS% "%URL_WEB%""
if /i "%MODE%"=="server" set "TARGETS=%TARGETS% "%URL_APP%""

echo.
echo   Membuka di browser:
echo     %URL_API%
if defined OPEN_WEB echo     %URL_WEB%
if /i "%MODE%"=="server" echo     %URL_APP%
echo.

if not exist "%BRAVE%" goto :default_browser

start "" "%BRAVE%" %TARGETS%
goto :done

:default_browser
rem Tanda kutip kosong pertama adalah judul jendela - tanpa itu, `start`
rem menganggap URL sebagai judul dan tidak membuka apa pun.
echo   [!] Brave tidak ditemukan - memakai browser bawaan.
start "" "%URL_API%"
if defined OPEN_WEB start "" "%URL_WEB%"
if /i "%MODE%"=="server" start "" "%URL_APP%"

:done
timeout /t 2 >nul
endlocal
exit /b 0

:skip
echo.
echo   [!] API tidak kunjung siap - browser tidak dibuka.
echo       Periksa tab API: mungkin kompilasi gagal atau PostgreSQL mati.
echo.
timeout /t 10 >nul
endlocal
exit /b 1
