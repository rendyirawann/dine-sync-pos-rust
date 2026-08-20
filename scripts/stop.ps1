# Hentikan seluruh service DineSync yang dijalankan dev.bat.
#
# Sasarannya DIBATASI pada proses cmd yang perintahnya menyebut skrip di
# folder ini, beserta seluruh keturunannya. Pembatasan itu penting:
# `taskkill /im cargo.exe` atau `/im dart.exe` akan ikut mematikan pekerjaan
# lain yang kebetulan sedang berjalan.
#
# Sebagai jaring pengaman, port yang masih tertahan setelah itu ditutup
# lewat pemiliknya - proses yang lolos penelusuran (mis. cargo yang sudah
# kehilangan induknya karena tabnya ditutup manual) tetap memegang port dan
# membuat `dev.bat` berikutnya gagal dengan "address already in use".

$ErrorActionPreference = 'Continue'

Write-Host ''
Write-Host '  Menghentikan DineSync' -ForegroundColor Cyan
Write-Host '  ---------------------'

$all = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue

$roots = $all | Where-Object {
    $_.Name -eq 'cmd.exe' -and $_.CommandLine -match 'scripts\\run-(web|api|app)\.bat'
}

if (-not $roots) {
    Write-Host '  Tidak ada tab yang sedang berjalan.'
} else {
    # Kumpulkan pohon proses lebih dulu, baru dimatikan dari daun ke akar.
    # Mematikan induk lebih dulu membuat anaknya kehilangan orang tua dan
    # lolos dari penelusuran - cargo/flutter akan tetap hidup memegang port.
    $targets = New-Object System.Collections.Generic.List[object]

    function Add-Tree($processId) {
        foreach ($child in $all | Where-Object { $_.ParentProcessId -eq $processId }) {
            Add-Tree $child.ProcessId
            $targets.Add($child) | Out-Null
        }
    }

    foreach ($root in $roots) {
        Add-Tree $root.ProcessId
        $targets.Add($root) | Out-Null
    }

    foreach ($t in $targets) {
        try {
            Stop-Process -Id $t.ProcessId -Force -ErrorAction Stop
            Write-Host ("  dihentikan: {0} (pid {1})" -f $t.Name, $t.ProcessId)
        } catch {
            # Sudah mati bersama induknya - bukan masalah.
        }
    }
}

# --- Jaring pengaman: port yang masih tertahan ------------------------------
#
# PostgreSQL (:5433) SENGAJA tidak disentuh - servicenya di luar kendali repo
# ini dan dipakai juga oleh aplikasi lain.
$ports = @(
    @{ Port = 8088; Label = 'Web' },
    @{ Port = 8090; Label = 'API' },
    @{ Port = 5000; Label = 'App' }
)

foreach ($entry in $ports) {
    $owners = @()
    try {
        $owners = Get-NetTCPConnection -LocalPort $entry.Port -State Listen -ErrorAction Stop |
                  Select-Object -ExpandProperty OwningProcess -Unique
    } catch {
        # Tidak ada yang mendengarkan di port ini - beres.
        continue
    }

    foreach ($owningPid in $owners) {
        if ($owningPid -le 4) { continue }   # System / Idle
        $name = (Get-Process -Id $owningPid -ErrorAction SilentlyContinue).ProcessName
        try {
            Stop-Process -Id $owningPid -Force -ErrorAction Stop
            Write-Host ("  port {0} ({1}) dibebaskan dari {2} (pid {3})" -f $entry.Port, $entry.Label, $name, $owningPid)
        } catch {
            Write-Host ("  [!] port {0} masih dipegang {1} (pid {2}) - hentikan manual" -f $entry.Port, $name, $owningPid) -ForegroundColor Yellow
        }
    }
}

Write-Host ''
Write-Host '  Selesai. Jalankan `dev.bat` untuk menyalakan kembali.'
Write-Host ''
