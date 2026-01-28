$ErrorActionPreference = 'Stop'

$timestamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$backupDir = Join-Path $PSScriptRoot "..\\storage\\backups\\db"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

$dbConnection = $env:DB_CONNECTION
if (-not $dbConnection) { $dbConnection = 'mysql' }

$filename = Join-Path $backupDir ("roomgate_" + $dbConnection + "_" + $timestamp + ".sql")

if ($dbConnection -eq 'pgsql') {
  if (-not $env:DB_DATABASE) { throw 'DB_DATABASE is required' }
  $env:PGPASSWORD = $env:DB_PASSWORD
  & pg_dump -h $env:DB_HOST -p $env:DB_PORT -U $env:DB_USERNAME $env:DB_DATABASE > $filename
} elseif ($dbConnection -eq 'mysql') {
  if (-not $env:DB_DATABASE) { throw 'DB_DATABASE is required' }
  & mysqldump -h $env:DB_HOST -P $env:DB_PORT -u $env:DB_USERNAME --password=$env:DB_PASSWORD $env:DB_DATABASE > $filename
} else {
  throw "Unsupported DB_CONNECTION: $dbConnection"
}

Write-Host "Backup written to $filename"
