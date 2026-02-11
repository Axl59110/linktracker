# Script de démarrage pour LinkTracker en mode développement
# Usage: .\start-dev.ps1

Write-Host "🚀 Démarrage de LinkTracker..." -ForegroundColor Cyan

# Se placer dans le répertoire du projet
Set-Location $PSScriptRoot

# Nettoyer les caches
Write-Host "🧹 Nettoyage des caches..." -ForegroundColor Yellow
php artisan config:clear
php artisan view:clear

# Lancer Vite en mode développement dans un nouveau terminal
Write-Host "⚡ Lancement de Vite (serveur de développement)..." -ForegroundColor Green
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot'; npm run dev"

# Attendre 3 secondes pour que Vite démarre
Start-Sleep -Seconds 3

# Ouvrir le navigateur
Write-Host "🌐 Ouverture de l'application..." -ForegroundColor Magenta
Start-Process "http://linktracker.test"

Write-Host ""
Write-Host "✅ LinkTracker est prêt !" -ForegroundColor Green
Write-Host ""
Write-Host "URLs disponibles:" -ForegroundColor Cyan
Write-Host "  • Application: http://linktracker.test" -ForegroundColor White
Write-Host "  • Telescope:   http://linktracker.test/telescope" -ForegroundColor White
Write-Host ""
Write-Host "Pour arrêter le serveur Vite, fermez la fenêtre PowerShell correspondante." -ForegroundColor Yellow
