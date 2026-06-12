; =====================================================================
; Installer DineSync POS (Inno Setup) — OPSIONAL, bungkus dist jadi setup 1-klik.
; Prasyarat: jalankan dulu  rust\package.ps1  (menghasilkan dist\dinesync-pos),
; lalu buka file ini dengan Inno Setup Compiler (gratis: jrsoftware.org/isdl.php) → Compile.
; Hasil: rust\dist\DineSyncPOS-Setup.exe
; Catatan: .env TIDAK dibundel (Excludes) — pengguna isi sendiri setelah install.
; =====================================================================
[Setup]
AppName=DineSync POS
AppVersion=1.0
AppPublisher=DineSync
DefaultDirName={autopf}\DineSync POS
DefaultGroupName=DineSync POS
OutputDir=dist
OutputBaseFilename=DineSyncPOS-Setup
Compression=lzma2
SolidCompression=yes
ArchitecturesAllowed=x64compatible
ArchitecturesInstallIn64BitMode=x64compatible
PrivilegesRequired=lowest

[Languages]
Name: "id"; MessagesFile: "compiler:Default.isl"

[Files]
Source: "dist\dinesync-pos\*"; DestDir: "{app}"; Excludes: ".env,local.db,local.db-*"; Flags: recursesubdirs createallsubdirs ignoreversion

[Icons]
Name: "{group}\DineSync POS"; Filename: "{app}\central.exe"; WorkingDir: "{app}"
Name: "{group}\Folder Instalasi"; Filename: "{app}"
Name: "{autodesktop}\DineSync POS"; Filename: "{app}\central.exe"; WorkingDir: "{app}"

[Run]
; Salin .env.example → .env bila belum ada, agar siap diisi.
Filename: "{cmd}"; Parameters: "/c if not exist ""{app}\.env"" copy ""{app}\.env.example"" ""{app}\.env"""; Flags: runhidden
Filename: "{app}\central.exe"; Description: "Jalankan DineSync POS sekarang"; WorkingDir: "{app}"; Flags: nowait postinstall skipifsilent
