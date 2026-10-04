<#
  deploy-sftp.ps1 — Uploads the committed site files to cPanel over SFTP.

  Uses key-based auth (add your public key in cPanel → SSH Access → Manage SSH Keys → Import, then Authorize).
  Server-specific files are never uploaded: config/, admin/setup.php, uploads/ content, logs, docs.

  Usage:
    .\deploy-sftp.ps1 -SshHost example.com -User cpaneluser -RemotePath public_html
    .\deploy-sftp.ps1 ... -Since HEAD~4     # only files changed since that commit
    .\deploy-sftp.ps1 ... -DryRun           # print the SFTP batch without connecting
#>
param(
    [Parameter(Mandatory)] [string] $SshHost,
    [Parameter(Mandatory)] [string] $User,
    [string] $RemotePath = 'public_html',
    [int]    $Port = 22,
    [string] $KeyFile = "$env:USERPROFILE\.ssh\id_rsa",
    [string] $Since,
    [switch] $DryRun
)

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$exclude = '^(config/|admin/setup\.php$|scratch/|\.qodo/|DEPLOYMENT\.md$|schema\.sql$|deploy-sftp\.ps1$|\.gitignore$)'

if ($Since) {
    $files = git diff --name-only --diff-filter=AM $Since HEAD
} else {
    $files = git ls-files
}
$files = @($files | Where-Object { $_ -and $_ -notmatch $exclude })

if ($files.Count -eq 0) { Write-Host 'Nothing to upload.'; exit 0 }

$dirty = git status --porcelain -- $files
if ($dirty) { Write-Warning "Uncommitted changes in files being uploaded (working copy will be sent):`n$dirty" }

# Build the SFTP batch: create each remote directory once ('-' ignores "already exists"), then upload.
$batch = [System.Collections.Generic.List[string]]::new()
$dirs  = $files | ForEach-Object { Split-Path $_ -Parent } | Where-Object { $_ } |
         ForEach-Object { $_ -replace '\\', '/' } | Sort-Object -Unique
foreach ($d in $dirs) {
    $parts = $d -split '/'
    for ($i = 1; $i -le $parts.Count; $i++) {
        $line = "-mkdir `"$RemotePath/$(($parts[0..($i-1)]) -join '/')`""
        if (-not $batch.Contains($line)) { $batch.Add($line) }
    }
}
foreach ($f in $files) { $batch.Add("put `"$f`" `"$RemotePath/$f`"") }

$batchFile = Join-Path $env:TEMP 'portfolio-sftp-batch.txt'
Set-Content -Path $batchFile -Value $batch -Encoding ascii

Write-Host "Uploading $($files.Count) file(s) to $User@${SshHost}:$RemotePath"
if ($DryRun) { Get-Content $batchFile; exit 0 }

sftp -b $batchFile -P $Port -i $KeyFile -o BatchMode=yes "$User@$SshHost"
if ($LASTEXITCODE -ne 0) { throw "sftp exited with code $LASTEXITCODE" }
Write-Host 'Deploy complete.'
