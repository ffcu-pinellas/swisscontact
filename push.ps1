# PowerShell script to push changes to GitHub
Write-Host "Staging all files..." -ForegroundColor Cyan
git add .

Write-Host "Committing changes..." -ForegroundColor Cyan
git commit -m "Migrate Swisscontact website to database-driven PHP structure on swisscontact.online"

Write-Host "Pushing to GitHub..." -ForegroundColor Cyan
git branch -M main
git push -u origin main

Write-Host "Push complete!" -ForegroundColor Green
