# Script pour utiliser les assets compilés (mode production)
# Usage: .\start-prod.ps1

Write-Host "📦 Configuration pour assets compilés..." -ForegroundColor Cyan

Set-Location $PSScriptRoot

# Compiler les assets
Write-Host "⚙️  Compilation des assets..." -ForegroundColor Yellow
npm run build

# Mettre en cache la configuration
Write-Host "💾 Mise en cache de la configuration..." -ForegroundColor Yellow
php artisan config:cache

# Ouvrir le navigateur
Write-Host "🌐 Ouverture de l'application..." -ForegroundColor Magenta
Start-Process "http://linktracker.test"

Write-Host ""
Write-Host "✅ Application prête avec assets compilés !" -ForegroundColor Green
Write-Host ""
Write-Host "Pour revenir en mode développement:" -ForegroundColor Yellow
Write-Host "  php artisan config:clear" -ForegroundColor White
Write-Host "  .\start-dev.ps1" -ForegroundColor White
