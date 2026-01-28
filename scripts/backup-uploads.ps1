$ErrorActionPreference = 'Stop'

$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$backupDir = Join-Path $PSScriptRoot "..\\storage\\backups\\uploads"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

$sourceDir = Join-Path $PSScriptRoot "..\\public\\uploads"
$archive = Join-Path $backupDir ("uploads_" + $timestamp + ".zip")

if (-not (Test-Path $sourceDir)) {
  throw "Uploads directory not found: $sourceDir"
}

Compress-Archive -Path (Join-Path $sourceDir '*') -DestinationPath $archive -Force
Write-Host "Uploads backup written to $archive"
