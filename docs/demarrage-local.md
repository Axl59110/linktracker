# Démarrage de l'Application LinkTracker

## 🎯 Problème Résolu : Page Blanche

### Cause
L'application affichait une page blanche car :
- Le template Blade chargeait Vite en **mode développement** (`http://[::1]:5173`)
- Le serveur Vite **n'était pas démarré**
- Les assets Vue.js ne pouvaient donc pas se charger

### Solution Appliquée
Mise en cache de la configuration Laravel pour forcer l'utilisation des **assets compilés** (mode production).

---

## 🚀 Démarrage Rapide

### Option 1 : Mode Production (Assets Compilés) - **Recommandé pour tester**

```powershell
cd C:\Users\axel\OneDrive\Desktop\Claude\Linktracker\app-laravel
.\start-prod.ps1
```

**Avantages** :
- ✅ Pas besoin de serveur Vite
- ✅ Plus rapide au chargement
- ✅ Correspond à l'environnement de production

**Inconvénients** :
- ❌ Recompiler à chaque modification : `npm run build`

---

### Option 2 : Mode Développement (Hot Module Replacement) - **Pour développer**

```powershell
cd C:\Users\axel\OneDrive\Desktop\Claude\Linktracker\app-laravel
.\start-dev.ps1
```

**Avantages** :
- ✅ Rechargement automatique des modifications
- ✅ Hot Module Replacement (HMR)
- ✅ Meilleure expérience développeur

**Inconvénients** :
- ❌ Nécessite un terminal PowerShell ouvert pour Vite

---

## 📝 Démarrage Manuel

### Mode Production (Assets Compilés)

```powershell
# 1. Compiler les assets
cd C:\Users\axel\OneDrive\Desktop\Claude\Linktracker\app-laravel
npm run build

# 2. Mettre en cache la config (force l'utilisation des assets buildés)
php artisan config:cache

# 3. Ouvrir l'application
Start-Process http://linktracker.test
```

### Mode Développement (Vite Dev Server)

```powershell
# 1. Nettoyer les caches
cd C:\Users\axel\OneDrive\Desktop\Claude\Linktracker\app-laravel
php artisan config:clear
php artisan view:clear

# 2. Lancer Vite (dans un terminal séparé)
npm run dev

# 3. Ouvrir l'application
Start-Process http://linktracker.test
```

---

## 🔄 Basculer entre les Modes

### De Production → Développement

```powershell
php artisan config:clear
npm run dev  # Dans un terminal séparé
```

### De Développement → Production

```powershell
# Arrêter Vite (Ctrl+C dans son terminal)
npm run build
php artisan config:cache
```

---

## 🛠️ Commandes Utiles

### Vérifier l'État de l'Application

```powershell
# Voir quel mode est actif
Test-Path 'C:\Users\axel\OneDrive\Desktop\Claude\Linktracker\app-laravel\bootstrap\cache\config.php'

# Si True → Mode Production (config en cache)
# Si False → Mode Développement
```

### Recompiler les Assets

```powershell
# Mode production (une fois)
npm run build

# Mode développement (watch)
npm run dev
```

### Nettoyer Tous les Caches

```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## 🌐 URLs Disponibles

| URL | Description | Authentification |
|-----|-------------|------------------|
| http://linktracker.test | Application principale | Non |
| http://linktracker.test/telescope | Débogage Laravel | Non (dev only) |
| http://linktracker.test/api/user | Endpoint API utilisateur | Oui (Sanctum) |

---

## 🐛 Dépannage

### Page Blanche

**Symptôme** : La page s'affiche mais reste blanche

**Solutions** :
1. Vérifier la console navigateur (F12) pour les erreurs JavaScript
2. S'assurer que Vite tourne (mode dev) OU que les assets sont compilés (mode prod)
3. Vérifier que les assets existent :
   ```powershell
   Test-Path 'public\build\assets\app-*.js'
   ```

### Erreur "Vite manifest not found"

**Cause** : Assets pas compilés

**Solution** :
```powershell
npm run build
```

### Modifications non prises en compte

**En mode dev** : Vérifier que Vite tourne (`npm run dev`)

**En mode prod** : Recompiler les assets
```powershell
npm run build
php artisan config:cache
```

### Port 5173 déjà utilisé

**Symptôme** : Vite ne démarre pas, erreur "address already in use"

**Solution** :
```powershell
# Trouver le processus
Get-Process | Where-Object {$_.ProcessName -like "*node*"}

# Tuer le processus
Stop-Process -Id <PID>

# Relancer Vite
npm run dev
```

---

## ⚙️ Configuration Technique

### Comment Laravel Détecte le Mode

Laravel utilise le **plugin Vite** qui :
1. Vérifie si un serveur Vite tourne (port 5173)
2. Si oui → charge depuis Vite (`http://[::1]:5173`)
3. Si non → charge depuis `public/build/`

### Forcer le Mode Production

Mettre la config en cache **force** l'utilisation des assets buildés :
```powershell
php artisan config:cache
```

Cela crée `bootstrap/cache/config.php` qui contient une configuration statique.

---

## 📚 Ressources

- **Vite avec Laravel** : https://laravel.com/docs/10.x/vite
- **Vue.js 3** : https://vuejs.org/
- **Tailwind CSS v4** : https://tailwindcss.com/docs

---

## 🎯 Recommandation

**Pour le développement actif** : Utilisez `.\start-dev.ps1`
**Pour tester rapidement** : Utilisez `.\start-prod.ps1`
**Pour la production** : Assets toujours compilés avec `npm run build`
