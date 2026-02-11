# Guide d'Installation Complet - Link Tracker

Ce guide vous accompagne pas à pas pour installer LinkTracker sur une nouvelle machine.

---

## 📋 Checklist Pré-Installation

Avant de commencer, assurez-vous d'avoir :
- [ ] Une connexion Internet
- [ ] Windows 10/11 (ou MacOS/Linux avec adaptations)
- [ ] Droits administrateur sur votre machine
- [ ] ~2 GB d'espace disque libre

---

## Étape 1 : Installation de Laravel Herd

### Option Recommandée : Laravel Herd (Windows/Mac)

1. **Télécharger Herd**
   - Visitez : https://herd.laravel.com/
   - Cliquez sur "Download for Windows" (ou Mac)
   - Exécutez l'installateur

2. **Installer Herd**
   - Acceptez les paramètres par défaut
   - Herd installe automatiquement :
     - ✅ PHP 8.2, 8.3, 8.4
     - ✅ Composer
     - ✅ Node.js et npm
     - ✅ Nginx
     - ✅ Gestion des domaines `.test`

3. **Vérifier l'Installation**
   ```powershell
   herd --version
   php --version
   composer --version
   node --version
   npm --version
   ```

**Durée estimée** : 5-10 minutes

---

## Étape 2 : Installation de Git

1. **Télécharger Git**
   - Visitez : https://git-scm.com/downloads
   - Téléchargez la version Windows

2. **Installer Git**
   - Paramètres recommandés :
     - ✅ Use Git from the Windows Command Prompt
     - ✅ Checkout Windows-style, commit Unix-style
     - ✅ Use MinTTY

3. **Vérifier**
   ```powershell
   git --version
   ```

**Durée estimée** : 5 minutes

---

## Étape 3 : Cloner le Projet

```powershell
# Créer un dossier pour vos projets (exemple)
cd C:\Users\VotreNom\Desktop
mkdir Projets
cd Projets

# Cloner le repository
git clone https://github.com/votre-username/linktracker.git
cd linktracker
```

**Structure attendue** :
```
linktracker/
├── app-laravel/     ← Application Laravel
├── docs/            ← Documentation
├── bmad/            ← Configuration BMAD
└── README.md
```

**Durée estimée** : 2-5 minutes (selon connexion)

---

## Étape 4 : Installation des Dépendances

### 4.1 Dépendances PHP (Composer)

```powershell
cd app-laravel
composer install
```

**Ce qui se passe** :
- Téléchargement des packages Laravel
- Installation de Sanctum, Telescope, etc.
- Création du dossier `vendor/`

**Si erreur** : Vérifier que Composer est bien installé (`composer --version`)

**Durée estimée** : 2-5 minutes

### 4.2 Dépendances JavaScript (npm)

```powershell
npm install
```

**Ce qui se passe** :
- Téléchargement de Vue.js, Vite, Tailwind
- Création du dossier `node_modules/`

**Durée estimée** : 3-7 minutes

---

## Étape 5 : Configuration de l'Environnement

### 5.1 Créer le Fichier `.env`

```powershell
Copy-Item .env.example .env
```

### 5.2 Générer la Clé d'Application

```powershell
php artisan key:generate
```

**Résultat attendu** :
```
INFO  Application key set successfully.
```

### 5.3 ⚠️ IMPORTANT : Vérifier le `.env`

Ouvrir `.env` dans un éditeur de texte et **s'assurer que ces lignes sont commentées ou absentes** :

```env
# ❌ Si vous voyez ça, commentez-les :
# APP_SERVICES_CACHE=
# APP_PACKAGES_CACHE=
# APP_CONFIG_CACHE=
```

**Pourquoi ?** Ces variables vides causent l'erreur "Permission denied" avec OneDrive.

**Durée estimée** : 2 minutes

---

## Étape 6 : Configuration de la Base de Données

### Option A : SQLite (Recommandé pour Débuter)

```powershell
# Créer le fichier de base de données
New-Item -ItemType File -Path database\database.sqlite -Force
```

**Vérifier dans `.env`** :
```env
DB_CONNECTION=sqlite
```

### Option B : PostgreSQL (Pour Production ou Si Déjà Installé)

1. **Installer PostgreSQL**
   - Télécharger : https://www.postgresql.org/download/windows/
   - Installer avec le mot de passe `postgres` (retenir le mot de passe !)

2. **Créer la Base de Données**
   ```powershell
   # Ouvrir psql (outil PostgreSQL)
   psql -U postgres
   ```

   ```sql
   CREATE DATABASE linktracker;
   CREATE USER linktracker_user WITH ENCRYPTED PASSWORD 'votre_mot_de_passe';
   GRANT ALL PRIVILEGES ON DATABASE linktracker TO linktracker_user;
   \q
   ```

3. **Configurer `.env`**
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=linktracker
   DB_USERNAME=linktracker_user
   DB_PASSWORD=votre_mot_de_passe
   ```

**Durée estimée** :
- SQLite : 1 minute
- PostgreSQL : 15-30 minutes (avec installation)

---

## Étape 7 : Créer les Tables (Migrations)

```powershell
php artisan migrate
```

**Résultat attendu** :
```
INFO  Preparing database.
INFO  Running migrations.

2014_10_12_000000_create_users_table .............. DONE
2014_10_12_100000_create_password_reset_tokens_table . DONE
2019_08_19_000000_create_failed_jobs_table ........ DONE
2019_12_14_000001_create_personal_access_tokens_table . DONE
```

**Si erreur** : Vérifier la configuration de la base de données dans `.env`

**Durée estimée** : 1 minute

---

## Étape 8 : Compiler les Assets Frontend

### 8.1 ⚠️ IMPORTANT : Vérifier Tailwind CSS

**Ouvrir `resources/css/app.css` et s'assurer qu'il contient** :

```css
@import "tailwindcss";
```

**PAS** :
```css
/* ❌ Ancienne syntaxe - ne fonctionne pas avec Tailwind v4 */
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### 8.2 Compiler

```powershell
npm run build
```

**Résultat attendu** :
```
vite v5.4.21 building for production...
✓ 77 modules transformed.
public/build/manifest.json            0.33 kB
public/build/assets/app-C9V_xLIm.css  17.31 kB │ gzip: 4.11 kB
public/build/assets/app-DN_trdhU.js   124.77 kB │ gzip: 48.74 kB
✓ built in 5.02s
```

**Durée estimée** : 5-10 secondes

---

## Étape 9 : Configurer Herd

### 9.1 Ajouter le Projet à Herd

**Option A : Park (Automatique)**
```powershell
# Depuis le répertoire racine du projet
cd C:\Users\VotreNom\Desktop\Projets\linktracker
herd park
```

Cela rend **tous les sous-dossiers** accessibles via `.test`

**Option B : Link (Manuel)**
```powershell
# Depuis le dossier app-laravel
cd app-laravel
herd link linktracker
```

### 9.2 Vérifier

```powershell
herd links
```

**Résultat attendu** :
```
+-------------+-----+---------------------------+
| Site        | SSL | URL                       |
+-------------+-----+---------------------------+
| linktracker |     | http://linktracker.test   |
+-------------+-----+---------------------------+
```

**Durée estimée** : 1 minute

---

## Étape 10 : Lancer l'Application

### Option A : Mode Production (Assets Compilés)

```powershell
.\start-prod.ps1
```

**Ou manuellement** :
```powershell
php artisan config:cache
Start-Process http://linktracker.test
```

### Option B : Mode Développement (Hot Reload)

```powershell
.\start-dev.ps1
```

**Ou manuellement** :
```powershell
# Terminal 1
npm run dev

# Terminal 2 (ou navigateur)
Start-Process http://linktracker.test
```

**Durée estimée** : 10 secondes

---

## ✅ Vérification Finale

### Test 1 : Page d'Accueil

Ouvrir http://linktracker.test dans votre navigateur.

**Vous devriez voir** :
- ✅ Titre "Link Tracker" en gros et gras
- ✅ Description "Application de monitoring de backlinks pour SEO"
- ✅ Bouton bleu "Connexion"
- ✅ Bouton gris "En savoir plus"
- ✅ 3 cartes blanches avec icônes (🔍 🚨 📊)
- ✅ Footer "Sprint 1 - Foundation & Infrastructure 🚀"

**Si page blanche** : Voir section Dépannage ci-dessous

### Test 2 : Telescope (Debugging)

Ouvrir http://linktracker.test/telescope

**Vous devriez voir** :
- ✅ Interface Telescope avec menu latéral
- ✅ Dashboard avec statistiques

### Test 3 : Console Navigateur (F12)

Ouvrir la console navigateur (F12 → Console)

**Aucune erreur** ne devrait apparaître.

---

## 🐛 Dépannage

### Problème 1 : Page Blanche

**Cause** : Assets non compilés ou Vite non démarré

**Solution** :
```powershell
npm run build
php artisan config:cache
```

Rafraîchir la page (Ctrl+F5)

### Problème 2 : CSS Ne Se Charge Pas (Contenu Brut)

**Cause** : Mauvaise syntaxe Tailwind CSS

**Solution** :
1. Ouvrir `resources/css/app.css`
2. Remplacer le contenu par : `@import "tailwindcss";`
3. Recompiler : `npm run build`
4. Mettre en cache : `php artisan config:cache`

### Problème 3 : Erreur "Permission denied"

**Cause** : Variables `.env` vides

**Solution** :
1. Ouvrir `.env`
2. Commenter ou supprimer :
   ```env
   # APP_SERVICES_CACHE=
   # APP_PACKAGES_CACHE=
   # APP_CONFIG_CACHE=
   ```
3. Nettoyer le cache : `php artisan config:clear`

### Problème 4 : `herd` Command Not Found

**Cause** : Herd pas dans le PATH ou pas installé

**Solution** :
1. Redémarrer PowerShell
2. Vérifier l'installation : `herd --version`
3. Réinstaller Herd si nécessaire

### Problème 5 : Port 5173 Déjà Utilisé

**Cause** : Vite déjà en cours d'exécution

**Solution** :
```powershell
Get-Process | Where-Object {$_.ProcessName -like "*node*"} | Stop-Process
npm run dev
```

---

## 📊 Temps Total Estimé

| Étape | Durée |
|-------|-------|
| Installation Herd | 5-10 min |
| Installation Git | 5 min |
| Clone du projet | 2-5 min |
| Dépendances PHP | 2-5 min |
| Dépendances JS | 3-7 min |
| Configuration | 3-5 min |
| Base de données | 1-30 min (selon choix) |
| Migrations | 1 min |
| Compilation assets | 1 min |
| Configuration Herd | 1 min |
| **TOTAL** | **24-70 minutes** |

**Pour un développeur expérimenté avec Herd déjà installé** : ~10-15 minutes

---

## 🎉 Félicitations !

Votre application LinkTracker est maintenant installée et fonctionnelle !

**Prochaines étapes** :
1. Explorer l'application : http://linktracker.test
2. Lire la documentation : `docs/`
3. Consulter le PRD : `docs/prd-link-tracker-2026-02-09.md`
4. Voir le sprint actuel : `docs/sprint-status.yaml`

**Pour contribuer** :
- Lire `README.md`
- Suivre la BMAD Method (voir `bmad/config.yaml`)

---

## 📞 Besoin d'Aide ?

- **Documentation** : Voir `docs/demarrage-local.md`
- **Issues** : Ouvrir une issue GitHub
- **Discord/Slack** : Rejoindre la communauté (si applicable)
