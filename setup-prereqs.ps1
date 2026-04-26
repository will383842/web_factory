# WebFactory - Prerequisites installer (Windows)
# Run this script as Administrator (right-click -> "Run with PowerShell" then accept UAC,
# OR open an elevated PowerShell and run: .\setup-prereqs.ps1)

#Requires -RunAsAdministrator

$ErrorActionPreference = 'Stop'

Write-Host ""
Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host "  WebFactory - Prerequisites installer" -ForegroundColor Cyan
Write-Host "  Will install: WSL2 + Ubuntu, Docker Desktop" -ForegroundColor Cyan
Write-Host "==================================================================" -ForegroundColor Cyan
Write-Host ""

# --- 1. WSL2 + Ubuntu ----------------------------------------------------------

Write-Host "[1/2] Installing WSL2 + Ubuntu..." -ForegroundColor Yellow
try {
    & wsl --install --no-launch
    Write-Host "  WSL install command completed (exit code: $LASTEXITCODE)" -ForegroundColor Green
} catch {
    Write-Host "  WSL install error: $_" -ForegroundColor Red
    Write-Host "  Continuing anyway..." -ForegroundColor Yellow
}

# --- 2. Docker Desktop via winget ---------------------------------------------

Write-Host ""
Write-Host "[2/2] Installing Docker Desktop via winget..." -ForegroundColor Yellow
try {
    & winget install -e --id Docker.DockerDesktop --accept-source-agreements --accept-package-agreements
    Write-Host "  Docker Desktop install command completed (exit code: $LASTEXITCODE)" -ForegroundColor Green
} catch {
    Write-Host "  Docker install error: $_" -ForegroundColor Red
}

# --- 3. Done ------------------------------------------------------------------

Write-Host ""
Write-Host "==================================================================" -ForegroundColor Green
Write-Host "  Done. Manual steps remaining:" -ForegroundColor Green
Write-Host "==================================================================" -ForegroundColor Green
Write-Host ""
Write-Host "  1. REBOOT Windows now (required for WSL2 + Docker to take effect)." -ForegroundColor White
Write-Host "  2. After reboot: launch Docker Desktop from the Start menu." -ForegroundColor White
Write-Host "     - Accept the CGU." -ForegroundColor White
Write-Host "     - Accept the WSL2 backend setup." -ForegroundColor White
Write-Host "     - Wait until the whale icon is steady (green) in the system tray." -ForegroundColor White
Write-Host "  3. Validate: open any PowerShell and run:" -ForegroundColor White
Write-Host "       docker run --rm hello-world" -ForegroundColor Gray
Write-Host "  4. Tell Claude Code 'ok pret' and Sprint 0 will resume autonomously." -ForegroundColor White
Write-Host ""
Write-Host "Press any key to reboot now, or Ctrl+C to reboot manually later..." -ForegroundColor Yellow
$null = $Host.UI.RawUI.ReadKey('NoEcho,IncludeKeyDown')

Write-Host ""
Write-Host "Rebooting in 10 seconds..." -ForegroundColor Cyan
Start-Sleep -Seconds 10
Restart-Computer -Force
