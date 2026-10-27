#define MyAppName "Bel Madrasah"
#define MyAppVersion "0.1.0"
#define MyAppPublisher "Bel Madrasah Project"
#define MyAppExeName "phpdesktop-chrome.exe"

[Setup]
AppId={{7C4E8A19-2B5F-4E62-9D3A-6F1B0C8E74A2}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={localappdata}\Programs\Bel Madrasah
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
UsePreviousAppDir=yes
PrivilegesRequired=lowest
ArchitecturesAllowed=x64
ArchitecturesInstallIn64BitMode=x64
OutputDir=..\build\installer-output
OutputBaseFilename=BelMadrasah-Setup-{#MyAppVersion}
SetupLogging=yes
UninstallDisplayName={#MyAppName}
UninstallDisplayIcon={app}\{#MyAppExeName}
WizardStyle=modern
Compression=lzma2
SolidCompression=yes
CloseApplications=yes
RestartApplications=no

[Tasks]
Name: "desktopicon"; Description: "Buat shortcut di Desktop"; GroupDescription: "Shortcut tambahan:"; Flags: unchecked

[Files]
; The staging directory must be prepared with Prepare-Installer.ps1 first.
; Runtime and app files are installed together; user data is stored outside {app}.
Source: "..\build\installer-stage\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{autoprograms}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; WorkingDir: "{app}"; Comment: "Aplikasi bel otomatis madrasah/sekolah"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; WorkingDir: "{app}"; Tasks: desktopicon; Comment: "Aplikasi bel otomatis madrasah/sekolah"

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "Jalankan Bel Madrasah"; Flags: postinstall nowait skipifsilent
