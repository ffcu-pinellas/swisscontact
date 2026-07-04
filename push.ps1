# PowerShell script to push changes to GitHub (triggers Hostinger Auto-Deployment)
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

Write-Host "`nPush complete! Hostinger will automatically pull and deploy the changes via Git integration." -ForegroundColor Green
