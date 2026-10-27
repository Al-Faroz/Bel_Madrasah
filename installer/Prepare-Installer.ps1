[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$RuntimeSource
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repoRoot = Split-Path -Parent $PSScriptRoot
$appSource = Join-Path $repoRoot 'prototype\phpdesktop\www'
$buildRoot = Join-Path $repoRoot 'build'
$stageDir = Join-Path $buildRoot 'installer-stage'

function Ensure-JsonObjectProperty {
    param(
        [Parameter(Mandatory = $true)]$Object,
        [Parameter(Mandatory = $true)][string]$Name
    )

    $property = $Object.PSObject.Properties[$Name]
    if ($null -eq $property -or $null -eq $property.Value -or $property.Value -is [string]) {
        $newObject = [pscustomobject]@{}
        if ($null -eq $property) {
            $Object | Add-Member -MemberType NoteProperty -Name $Name -Value $newObject
        } else {
            $property.Value = $newObject
        }
    }

    return $Object.PSObject.Properties[$Name].Value
}

function Set-JsonProperty {
    param(
        [Parameter(Mandatory = $true)]$Object,
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)]$Value
    )

    $property = $Object.PSObject.Properties[$Name]
    if ($null -eq $property) {
        $Object | Add-Member -MemberType NoteProperty -Name $Name -Value $Value
    } else {
        $property.Value = $Value
    }
}

try {
    $runtimePath = (Resolve-Path -LiteralPath $RuntimeSource).Path
} catch {
    throw "Folder runtime PHP Desktop tidak ditemukan: $RuntimeSource"
}

$runtimeExe = Join-Path $runtimePath 'phpdesktop-chrome.exe'
$runtimeSettings = Join-Path $runtimePath 'settings.json'
if (-not (Test-Path -LiteralPath $runtimeExe -PathType Leaf)) {
    throw "phpdesktop-chrome.exe tidak ditemukan di folder runtime: $runtimePath"
}
if (-not (Test-Path -LiteralPath $runtimeSettings -PathType Leaf)) {
    throw "settings.json tidak ditemukan di folder runtime: $runtimePath"
}
if (-not (Test-Path -LiteralPath (Join-Path $appSource 'index.php') -PathType Leaf)) {
    throw "Source aplikasi tidak lengkap; index.php tidak ditemukan: $appSource"
}

$runtimeFull = [System.IO.Path]::GetFullPath($runtimePath).TrimEnd('\')
$stageFull = [System.IO.Path]::GetFullPath($stageDir).TrimEnd('\')
if ($runtimeFull.Equals($stageFull, [System.StringComparison]::OrdinalIgnoreCase) -or
    $runtimeFull.StartsWith($stageFull + '\', [System.StringComparison]::OrdinalIgnoreCase) -or
    $stageFull.StartsWith($runtimeFull + '\', [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'Folder runtime sumber dan folder staging tidak boleh saling berada di dalam satu sama lain.'
}

New-Item -ItemType Directory -Path $buildRoot -Force | Out-Null
if (Test-Path -LiteralPath $stageDir) {
    Remove-Item -LiteralPath $stageDir -Recurse -Force
}
New-Item -ItemType Directory -Path $stageDir -Force | Out-Null

Write-Host 'Menyalin runtime PHP Desktop ke staging...'
Get-ChildItem -LiteralPath $runtimePath -Force | ForEach-Object {
    Copy-Item -LiteralPath $_.FullName -Destination $stageDir -Recurse -Force
}

# Replace the runtime's sample web root with the repository's current app.
$stageWww = Join-Path $stageDir 'www'
if (Test-Path -LiteralPath $stageWww) {
    Remove-Item -LiteralPath $stageWww -Recurse -Force
}
Copy-Item -LiteralPath $appSource -Destination $stageWww -Recurse -Force

# Do not ship logs copied from a developer's runtime folder.
Get-ChildItem -LiteralPath $stageDir -File -Recurse -Filter '*.log' -ErrorAction SilentlyContinue |
    Remove-Item -Force

# Patch only the settings needed for a packaged, unattended desktop app.
# Keep all other upstream runtime settings intact.
$stagedSettings = Join-Path $stageDir 'settings.json'
try {
    $settings = Get-Content -LiteralPath $stagedSettings -Raw | ConvertFrom-Json
} catch {
    throw "settings.json runtime tidak valid JSON: $($_.Exception.Message)"
}
if ($null -eq $settings -or $settings -is [string]) {
    throw 'settings.json harus berisi objek JSON di level teratas.'
}

$debugging = Ensure-JsonObjectProperty -Object $settings -Name 'debugging'
Set-JsonProperty -Object $debugging -Name 'show_console' -Value $false
Set-JsonProperty -Object $debugging -Name 'subprocess_show_console' -Value $false

$chrome = Ensure-JsonObjectProperty -Object $settings -Name 'chrome'
$switches = Ensure-JsonObjectProperty -Object $chrome -Name 'command_line_switches'
Set-JsonProperty -Object $switches -Name 'autoplay-policy' -Value 'no-user-gesture-required'

$settings | ConvertTo-Json -Depth 100 | Set-Content -LiteralPath $stagedSettings -Encoding UTF8

# Read the file back so a malformed staged config fails before packaging.
$verified = Get-Content -LiteralPath $stagedSettings -Raw | ConvertFrom-Json
if ($verified.debugging.show_console -ne $false -or
    $verified.debugging.subprocess_show_console -ne $false -or
    $verified.chrome.command_line_switches.'autoplay-policy' -ne 'no-user-gesture-required') {
    throw 'Verifikasi settings.json staging gagal.'
}
if (-not (Test-Path -LiteralPath (Join-Path $stageWww 'public.php') -PathType Leaf)) {
    throw 'Source aplikasi tidak lengkap: public.php tidak ditemukan pada staging.'
}

Write-Host ''
Write-Host 'STAGING SIAP' -ForegroundColor Green
Write-Host "Runtime sumber : $runtimePath"
Write-Host "Source aplikasi: $appSource"
Write-Host "Folder staging : $stageDir"
Write-Host 'Console        : disembunyikan'
Write-Host 'Autoplay       : dikonfigurasi untuk scheduler'
Write-Host ''
Write-Host 'Langkah berikutnya: kompilasi installer\BelMadrasah.iss dengan Inno Setup 6.'
