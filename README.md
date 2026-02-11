# Link Tracker

Application de monitoring de backlinks pour SEO construite avec Laravel 10 + Vue.js 3 + Tailwind CSS v4.

## 📋 Prérequis

### Windows avec Laravel Herd

- **Laravel Herd** : https://herd.laravel.com/
  - Inclut PHP 8.2+, Composer, Node.js, npm
  - Gère automatiquement Nginx et les domaines `.test`
- **Git** : https://git-scm.com/downloads
- **PostgreSQL** (optionnel, SQLite utilisé par défaut)

### Ou Installation Manuelle

- **PHP** 8.1 ou supérieur
- **Composer** : https://getcomposer.org/
- **Node.js** 18+ et npm : https://nodejs.org/
- **SQLite** ou **PostgreSQL**

---

## 🚀 Installation Rapide (avec Herd)

### 1. Cloner le Projet

```powershell
cd C:\Users\VotreNom\Desktop
git clone https://github.com/votre-username/linktracker.git
cd linktracker\app-laravel
```

### 2. Installer les Dépendances

```powershell
# Dépendances PHP
composer install

# Dépendances JavaScript
npm install
```

### 3. Configuration de l'Environnement

```powershell
# Copier le fichier .env d'exemple
Copy-Item .env.example .env

# Générer la clé d'application
php artisan key:generate
```

### 4. Configurer le Fichier `.env`

Ouvrir `.env` et **IMPORTANT** : **Commenter ou supprimer** ces lignes si elles existent :
```env
# APP_SERVICES_CACHE=
# APP_PACKAGES_CACHE=
# APP_CONFIG_CACHE=
```

Configuration de base recommandée :
```env
APP_NAME="Link Tracker"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://linktracker.test

DB_CONNECTION=sqlite
# Pour PostgreSQL, décommenter et configurer :
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=linktracker
# DB_USERNAME=linktracker_user
# DB_PASSWORD=votre_mot_de_passe
```

### 5. Créer la Base de Données

**Option A : SQLite (Recommandé pour le développement)**
```powershell
New-Item -ItemType File -Path database\database.sqlite -Force
```

**Option B : PostgreSQL**
```sql
-- Se connecter à PostgreSQL et exécuter :
CREATE DATABASE linktracker;
CREATE USER linktracker_user WITH ENCRYPTED PASSWORD 'votre_mot_de_passe';
GRANT ALL PRIVILEGES ON DATABASE linktracker TO linktracker_user;
```

### 6. Exécuter les Migrations

```powershell
php artisan migrate
```

### 7. Compiler les Assets

```powershell
npm run build
```

### 8. Configurer Herd

```powershell
# Ajouter le projet à Herd (depuis le répertoire racine)
cd ..
herd park

# Ou créer un lien direct
cd app-laravel
herd link linktracker
```

### 9. Lancer l'Application

```powershell
# Mode production (assets compilés)
.\start-prod.ps1

# Ou mode développement (HMR)
.\start-dev.ps1
```

**Accéder à l'application** : http://linktracker.test

---

## 🛠️ Installation Manuelle (Sans Herd)

### 1. Installation des Prérequis

- Installer PHP 8.1+ avec les extensions : `pdo`, `pdo_sqlite`, `mbstring`, `openssl`
- Installer Composer
- Installer Node.js 18+

### 2. Cloner et Installer

```bash
git clone https://github.com/votre-username/linktracker.git
cd linktracker/app-laravel
composer install
npm install
```

### 3. Configuration

```bash
cp .env.example .env
php artisan key:generate

# Créer la base SQLite
touch database/database.sqlite

# Exécuter les migrations
php artisan migrate
```

### 4. Compiler les Assets

```bash
npm run build
```

### 5. Lancer le Serveur

```bash
# Terminal 1 : Laravel
php artisan serve

# Terminal 2 : Vite (optionnel pour dev)
npm run dev
```

**Accéder à l'application** : http://localhost:8000

---

## 📁 Structure du Projet

```
linktracker/
├── app-laravel/              # Application Laravel
│   ├── app/                  # Code métier (Models, Controllers, Services)
│   ├── database/
│   │   ├── database.sqlite   # Base de données SQLite
│   │   └── migrations/       # Migrations de base de données
│   ├── resources/
│   │   ├── css/
│   │   │   └── app.css       # Tailwind CSS (IMPORTANT : @import "tailwindcss")
│   │   ├── js/
│   │   │   ├── App.vue       # Composant Vue principal
│   │   │   ├── app.js        # Point d'entrée Vue
│   │   │   ├── pages/        # Pages Vue Router
│   │   │   └── router/       # Configuration Vue Router
│   │   └── views/
│   │       └── app.blade.php # Template Laravel principal
│   ├── routes/
│   │   ├── web.php           # Routes web (SPA catch-all)
│   │   └── api.php           # Routes API
│   ├── .env                  # Configuration environnement (IMPORTANT : voir notes)
│   ├── package.json          # Dépendances npm
│   ├── composer.json         # Dépendances PHP
│   ├── vite.config.js        # Configuration Vite
│   ├── postcss.config.js     # Configuration PostCSS (Tailwind v4)
│   ├── tailwind.config.js    # Configuration Tailwind
│   ├── start-prod.ps1        # Script démarrage production
│   └── start-dev.ps1         # Script démarrage développement
├── docs/                     # Documentation BMAD Method
│   ├── prd-link-tracker-2026-02-09.md
│   ├── architecture-link-tracker-2026-02-09.md
│   ├── sprint-01-plan.md
│   ├── demarrage-local.md
│   └── migration-postgresql.md
├── bmad/                     # Configuration BMAD Method
└── README.md                 # Ce fichier
```

---

## ⚙️ Configuration Importante

### ⚠️ Variables `.env` Problématiques

**NE JAMAIS** définir ces variables vides dans `.env` :
```env
# ❌ MAUVAIS - Cause des erreurs "Permission denied"
APP_SERVICES_CACHE=
APP_PACKAGES_CACHE=
APP_CONFIG_CACHE=

# ✅ BON - Les commenter ou les supprimer
# APP_SERVICES_CACHE=
# APP_PACKAGES_CACHE=
# APP_CONFIG_CACHE=
```

### 📝 Syntaxe Tailwind CSS v4

Le fichier `resources/css/app.css` **DOIT** utiliser la syntaxe Tailwind v4 :

```css
/* ✅ CORRECT pour Tailwind v4 */
@import "tailwindcss";

/* ❌ INCORRECT (ancienne syntaxe v3) */
@tailwind base;
@tailwind components;
@tailwind utilities;
```

---

## 🚀 Commandes Utiles

### Développement

```powershell
# Lancer le mode développement (HMR)
npm run dev

# Compiler les assets pour production
npm run build

# Vider les caches Laravel
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Créer une migration
php artisan make:migration create_table_name

# Créer un modèle avec migration
php artisan make:model ModelName -m

# Créer un contrôleur API
php artisan make:controller ControllerName --api

# Exécuter les tests
php artisan test
```

### Herd

```powershell
# Voir tous les sites
herd links

# Ouvrir l'application
herd open linktracker

# Redémarrer les services
herd restart

# Voir les services disponibles
herd services
```

---

## 🐛 Dépannage

### Page Blanche

**Symptôme** : La page s'affiche mais reste blanche

**Solutions** :
1. Vérifier la console navigateur (F12) pour les erreurs
2. S'assurer que les assets sont compilés :
   ```powershell
   npm run build
   php artisan config:cache
   ```
3. Vérifier que `public/build/manifest.json` existe

### CSS Ne Se Charge Pas

**Symptôme** : Contenu brut sans mise en forme

**Solution** :
1. Vérifier `resources/css/app.css` utilise `@import "tailwindcss";`
2. Recompiler :
   ```powershell
   npm run build
   ```

### Erreur "Permission denied" avec Laravel

**Cause** : Variables `.env` définies mais vides

**Solution** : Commenter `APP_SERVICES_CACHE`, `APP_PACKAGES_CACHE`, `APP_CONFIG_CACHE` dans `.env`

### Commandes PHP/Artisan ne Fonctionnent Pas (Git Bash)

**Cause** : Git Bash n'a pas accès aux binaires Herd

**Solution** : Utiliser **PowerShell** au lieu de Git Bash :
```powershell
powershell.exe -Command "cd 'chemin'; php artisan migrate"
```

### Port 5173 Déjà Utilisé (Vite)

**Solution** :
```powershell
# Trouver et tuer le processus Node
Get-Process | Where-Object {$_.ProcessName -like "*node*"} | Stop-Process
npm run dev
```

---

## 📚 Technologies Utilisées

| Technologie | Version | Documentation |
|-------------|---------|---------------|
| Laravel | 10.50.0 | https://laravel.com/docs/10.x |
| Vue.js | 3.5.28 | https://vuejs.org/ |
| Vue Router | 4.6.4 | https://router.vuejs.org/ |
| Tailwind CSS | 4.1.18 | https://tailwindcss.com/ |
| Vite | 5.4.21 | https://vitejs.dev/ |
| Laravel Sanctum | - | https://laravel.com/docs/10.x/sanctum |
| Laravel Telescope | - | https://laravel.com/docs/10.x/telescope |
| PHP | 8.4.16 | https://www.php.net/ |
| Composer | 2.8.10 | https://getcomposer.org/ |

---

## 🔐 Sécurité

### Avant de Commiter

**Ne JAMAIS commiter** :
- `.env` (contient les secrets)
- `database/database.sqlite` (données sensibles)
- `node_modules/`
- `vendor/`
- `public/build/` (généré automatiquement)

Le `.gitignore` est déjà configuré pour exclure ces fichiers.

### En Production

1. Définir `APP_ENV=production` dans `.env`
2. Définir `APP_DEBUG=false`
3. Utiliser PostgreSQL au lieu de SQLite
4. Configurer HTTPS (Nginx/TLS)
5. Mettre en cache la configuration :
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 🔄 Migration SQLite → PostgreSQL

Voir la documentation détaillée : `docs/migration-postgresql.md`

**Résumé rapide** :
1. Installer PostgreSQL
2. Créer la base de données
3. Modifier `.env` avec les credentials PostgreSQL
4. Exécuter `php artisan migrate:fresh`

**Note** : Aucune modification du code n'est nécessaire grâce à l'abstraction Eloquent.

---

## 📖 Documentation Supplémentaire

- **Démarrage Local** : `docs/demarrage-local.md`
- **Migration PostgreSQL** : `docs/migration-postgresql.md`
- **PRD** : `docs/prd-link-tracker-2026-02-09.md`
- **Architecture** : `docs/architecture-link-tracker-2026-02-09.md`
- **Sprint Plan** : `docs/sprint-01-plan.md`

---

## 🤝 Contribution

Ce projet suit la **BMAD Method** (Build-Measure-Adapt-Deploy) pour la gestion de projet.

Voir `bmad/config.yaml` et la documentation dans `docs/` pour plus de détails.

---

## 📝 License

Ce projet est privé et propriétaire.

---

## 📞 Support

Pour toute question ou problème :
1. Consulter la documentation dans `docs/`
2. Vérifier les issues GitHub
3. Contacter l'équipe de développement

---

## 🎯 Prochaines Étapes

Sprint 1 en cours :
- ✅ STORY-001 : Setup Laravel + Vue.js Project
- 🔄 STORY-002 : Implement User Authentication with Sanctum
- ⏳ STORY-003 : Create Project CRUD API
- ⏳ STORY-004 : Build Projects List Vue Component

Voir `docs/sprint-status.yaml` pour l'état complet du sprint.
