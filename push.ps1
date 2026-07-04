# PowerShell script to push changes to GitHub and trigger Hostinger Git Deployment
Write-Host "Staging all files..." -ForegroundColor Cyan
git add .

Write-Host "Committing changes..." -ForegroundColor Cyan
$commitMsg = $args[0]
if (-not $commitMsg) {
    $commitMsg = "Deploy updates to swisscontact.online"
}
git commit -m $commitMsg

Write-Host "Pushing to GitHub..." -ForegroundColor Cyan
git branch -M main
git push -u origin main

Write-Host "Push complete!" -ForegroundColor Green

# Trigger Hostinger deployment if Webhook URL is configured
$webhookFile = Join-Path $PSScriptRoot "hostinger_webhook.txt"
if (Test-Path $webhookFile) {
    Write-Host "Triggering Hostinger Git Deployment Webhook..." -ForegroundColor Cyan
    $webhookUrl = (Get-Content $webhookFile).Trim()
    if ($webhookUrl) {
        try {
            $response = Invoke-RestMethod -Uri $webhookUrl -Method Get -TimeoutSec 10
            Write-Host "Hostinger Deployment Triggered Successfully!" -ForegroundColor Green
        } catch {
            Write-Host "Warning: Could not trigger Hostinger Git Webhook. Details: $_" -ForegroundColor Yellow
        }
    } else {
        Write-Host "Warning: hostinger_webhook.txt is empty. Skipping auto-deploy." -ForegroundColor Yellow
    }
} else {
    Write-Host "`nTo enable automatic Hostinger deployment on push:" -ForegroundColor Gray
    Write-Host "1. Go to your Hostinger Control Panel -> Website -> Git." -ForegroundColor Gray
    Write-Host "2. Copy the 'Webhook URL' (or Deployment URL)." -ForegroundColor Gray
    Write-Host "3. Create a file named 'hostinger_webhook.txt' in this folder and paste the URL in it." -ForegroundColor Gray
}
