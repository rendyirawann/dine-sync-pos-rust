# =====================================================================
# Smoke test DineSync POS (Rust) — verifikasi semua halaman utama hidup
# sebelum cutover. Jalankan saat central.exe sedang berjalan:
#   powershell -ExecutionPolicy Bypass -File rust\smoke-test.ps1
# Param opsional: -BaseUrl, -Email, -Password
# =====================================================================
param(
    [string]$BaseUrl = "http://127.0.0.1:8088",
    [string]$Email = "superadmin@gmail.com",
    [string]$Password = "12qwaszx123!!@@##"
)
$ErrorActionPreference = 'Continue'
$pass = 0; $fail = 0
function Check($label, $url, $session) {
    try {
        $r = Invoke-WebRequest $url -UseBasicParsing -WebSession $session -TimeoutSec 10 -MaximumRedirection 0
        if ($r.StatusCode -eq 200) { Write-Host ("  [OK ] {0}" -f $label) -ForegroundColor Green; $script:pass++ }
        else { Write-Host ("  [!! ] {0} -> {1}" -f $label, $r.StatusCode) -ForegroundColor Yellow; $script:fail++ }
    } catch {
        $code = if ($_.Exception.Response) { $_.Exception.Response.StatusCode.value__ } else { 'DOWN' }
        Write-Host ("  [FAIL] {0} -> {1}" -f $label, $code) -ForegroundColor Red; $script:fail++
    }
}

Write-Host "== DineSync POS smoke test @ $BaseUrl ==" -ForegroundColor Cyan

# Publik (tanpa login)
$pub = New-Object Microsoft.PowerShell.Commands.WebRequestSession
Write-Host "-- Publik --"
Check "health"        "$BaseUrl/health"        $pub
Check "login page"    "$BaseUrl/admin/login"   $pub
Check "kiosk antrian" "$BaseUrl/kiosk"          $pub
Check "display TV"    "$BaseUrl/display"        $pub

# Login admin
$sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$lp = Invoke-WebRequest "$BaseUrl/admin/login" -UseBasicParsing -WebSession $sess
$tok = if ($lp.Content -match 'name="_token" value="([^"]+)"') { $matches[1] } else { '' }
$null = Invoke-WebRequest "$BaseUrl/admin/login" -Method POST -Body @{email=$Email;password=$Password;_token=$tok} -UseBasicParsing -WebSession $sess
$check = Invoke-WebRequest "$BaseUrl/admin/dashboard" -UseBasicParsing -WebSession $sess -MaximumRedirection 0 -ErrorAction SilentlyContinue
if ($check.StatusCode -ne 200) { Write-Host "Login GAGAL - cek kredensial / server." -ForegroundColor Red; exit 1 }

Write-Host "-- Halaman admin (perlu login) --"
$routes = @(
    "Dashboard|/admin/dashboard", "Kasir|/admin/kasir", "Shift|/admin/shifts", "Dapur|/admin/kitchen",
    "Antrian|/admin/queues", "Kategori|/admin/categories", "Menu|/admin/menus", "Meja|/admin/tables",
    "Promo|/admin/promos", "Supplier|/admin/suppliers", "Bahan|/admin/ingredients", "Resep|/admin/recipes",
    "Pengaturan|/admin/settings", "Expenses|/admin/expenses", "Stok Masuk|/admin/stocks",
    "Stock Opname|/admin/stock-opname", "Kartu Stok|/admin/stock-movements",
    "Laporan Sales|/admin/reports/sales", "Laporan Items|/admin/reports/items",
    "User Mgmt|/admin/users", "Role Mgmt|/admin/roles", "Log Activity|/admin/log-activity", "Sinkron|/admin/sync"
)
foreach ($r in $routes) { $p = $r.Split('|'); Check $p[0] "$BaseUrl$($p[1])" $sess }

Write-Host ""
Write-Host ("== HASIL: {0} OK, {1} GAGAL ==" -f $pass, $fail) -ForegroundColor $(if ($fail -eq 0) { 'Green' } else { 'Red' })
if ($fail -gt 0) { exit 1 }
