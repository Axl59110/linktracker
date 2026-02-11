# ✅ Checklist Avant Push sur GitHub

Utilisez cette checklist avant de pousser le projet sur GitHub pour vous assurer que tout est en ordre.

---

## 📝 Fichiers de Documentation

- [x] `README.md` - Vue d'ensemble et installation rapide
- [x] `INSTALLATION.md` - Guide d'installation détaillé pas à pas
- [x] `CONTRIBUTING.md` - Guide de contribution
- [x] `CHECKLIST-GITHUB.md` - Ce fichier
- [x] `app-laravel/.env.example` - Exemple de configuration avec notes importantes
- [x] `docs/demarrage-local.md` - Guide de démarrage local
- [x] `docs/migration-postgresql.md` - Guide migration SQLite → PostgreSQL

---

## 🔒 Fichiers Sensibles à NE PAS Commiter

### Vérifications Critiques

```powershell
# Vérifier que .env n'est PAS tracké
git status | Select-String ".env"
# Résultat attendu : rien (ou .env.example seulement)

# Vérifier que database.sqlite n'est PAS tracké
git status | Select-String "database.sqlite"
# Résultat attendu : rien

# Vérifier que node_modules n'est PAS tracké
git status | Select-String "node_modules"
# Résultat attendu : rien
```

### Liste des Fichiers à Exclure

- [ ] `.env` (contient secrets)
- [ ] `database/database.sqlite` (données sensibles)
- [ ] `node_modules/` (trop gros, régénérable)
- [ ] `vendor/` (trop gros, régénérable)
- [ ] `public/build/` (généré automatiquement)
- [ ] `public/hot` (fichier temporaire Vite)
- [ ] `storage/*.key` (clés privées)
- [ ] `.phpunit.result.cache`
- [ ] `npm-debug.log`
- [ ] `.vscode/` (config personnelle)

**Vérifier** : Ces fichiers doivent être dans `.gitignore`

---

## 📋 Configuration du Projet

### .env.example

- [x] Contient toutes les variables nécessaires
- [x] APP_KEY est vide (sera généré lors de l'installation)
- [x] Commentaires explicatifs sur les variables problématiques
- [x] Configuration SQLite par défaut
- [x] Configuration PostgreSQL en commentaire

### .gitignore

- [x] Inclut `.env`
- [x] Inclut `database/*.sqlite`
- [x] Inclut `node_modules/`
- [x] Inclut `vendor/`
- [x] Inclut `public/build/`

### Tailwind CSS

- [x] `resources/css/app.css` utilise `@import "tailwindcss";`
- [x] `postcss.config.js` utilise `@tailwindcss/postcss`
- [x] `tailwind.config.js` configuré correctement

---

## 🧪 Tests Fonctionnels

### Tests Locaux

```powershell
# 1. Nettoyer l'environnement
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# 2. Recompiler les assets
npm run build

# 3. Tester l'application
Start-Process http://linktracker.test
```

### Vérifications Visuelles

- [ ] Page d'accueil s'affiche correctement
- [ ] CSS Tailwind est chargé (texte stylé, couleurs)
- [ ] Pas d'erreurs dans la console (F12)
- [ ] Telescope accessible (/telescope)

### Tests Automatisés

```powershell
# Exécuter les tests
php artisan test

# Résultat attendu : tous les tests passent
```

---

## 📦 Dépendances et Versions

### Fichiers de Lock

- [x] `composer.lock` est présent (tracké)
- [x] `package-lock.json` est présent (tracké)

**Pourquoi ?** Ces fichiers garantissent des versions identiques pour tous les développeurs.

### Versions Critiques

Vérifier dans les fichiers :

**composer.json** :
- Laravel : ^10.10 ✅
- Sanctum : inclus
- Telescope : inclus

**package.json** :
- Vue : ^3.5.28 ✅
- Vue Router : ^4.6.4 ✅
- Tailwind : ^4.1.18 ✅
- Vite : ^5.0.0 ✅

---

## 🚀 Scripts et Outils

### Scripts PowerShell

- [x] `app-laravel/start-prod.ps1` existe et fonctionne
- [x] `app-laravel/start-dev.ps1` existe et fonctionne

**Tester** :
```powershell
cd app-laravel
.\start-prod.ps1
# Application doit s'ouvrir et fonctionner
```

---

## 📖 Documentation BMAD

### Fichiers BMAD

- [x] `bmad/config.yaml` - Configuration du projet
- [x] `docs/bmm-workflow-status.yaml` - Statut des workflows
- [x] `docs/sprint-status.yaml` - Statut du sprint
- [x] `docs/prd-link-tracker-2026-02-09.md` - Product Requirements
- [x] `docs/architecture-link-tracker-2026-02-09.md` - Architecture
- [x] `docs/sprint-01-plan.md` - Plan du Sprint 1

### Cohérence

- [ ] Les stories dans `sprint-status.yaml` correspondent au plan
- [ ] STORY-001 marquée comme "completed"
- [ ] Documentation à jour avec l'état actuel

---

## 🔐 Sécurité

### Vérifications

- [ ] Aucun mot de passe en clair dans le code
- [ ] Aucune clé API committée
- [ ] `.env.example` ne contient pas de secrets
- [ ] Pas de données personnelles dans les migrations

### Commandes de Vérification

```powershell
# Chercher des mots de passe potentiels
git grep -i "password.*=" -- ':(exclude).env.example'

# Chercher des clés API
git grep -i "api.*key.*=" -- ':(exclude).env.example'
```

---

## 📊 Structure des Commits

### Historique Git

```powershell
# Vérifier l'historique
git log --oneline -10

# Résultat attendu : commits clairs et descriptifs
```

### Premier Commit Recommandé

```bash
git add .
git commit -m "Initial commit: LinkTracker application with BMAD documentation

Complete Laravel 10 + Vue.js 3 + Tailwind CSS v4 setup including:
- Backend: Laravel with Sanctum, Telescope
- Frontend: Vue.js 3 with Vue Router
- Styling: Tailwind CSS v4
- Database: SQLite (development) / PostgreSQL (production ready)
- BMAD Method documentation and sprint planning
- Complete installation and contribution guides

STORY-001: Setup Laravel + Vue.js Project - COMPLETED

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

## 🌐 Repository GitHub

### Settings Recommandés

**Après création du repository** :

#### About Section
- Description : "Application de monitoring de backlinks pour SEO - Laravel + Vue.js + Tailwind"
- Website : (URL de production si applicable)
- Topics : `laravel`, `vuejs`, `tailwindcss`, `seo`, `backlink-monitoring`

#### Branch Protection (master)
- ✅ Require pull request reviews before merging
- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging

#### .github/ (À créer)
```
.github/
├── workflows/
│   └── ci.yml          # GitHub Actions CI/CD
├── ISSUE_TEMPLATE/
│   ├── bug_report.md
│   └── feature_request.md
└── pull_request_template.md
```

---

## ✅ Checklist Finale Avant Push

### Commandes de Vérification Finale

```powershell
# 1. Status Git propre
git status

# 2. Vérifier les fichiers à committer
git add .
git status

# 3. Vérifier qu'aucun fichier sensible n'est tracké
git ls-files | Select-String -Pattern ".env$|database.sqlite"
# Résultat attendu : rien (ou juste .env.example)

# 4. Tests passent
cd app-laravel
php artisan test

# 5. Assets compilés
npm run build

# 6. Application fonctionne
.\start-prod.ps1
```

### Liste de Contrôle Finale

- [ ] Tous les fichiers sensibles sont dans `.gitignore`
- [ ] `.env.example` est à jour et ne contient pas de secrets
- [ ] Documentation complète (README, INSTALLATION, CONTRIBUTING)
- [ ] Tests automatisés passent
- [ ] Application testée manuellement et fonctionne
- [ ] Pas de console errors (F12)
- [ ] CSS Tailwind se charge correctement
- [ ] Commits ont des messages clairs
- [ ] Pas de code commenté/debug inutile

---

## 🚀 Commandes Push

Une fois toutes les vérifications passées :

```bash
# 1. Créer le repository sur GitHub (via web interface)

# 2. Ajouter le remote
git remote add origin https://github.com/votre-username/linktracker.git

# 3. Push initial
git push -u origin master

# 4. Vérifier sur GitHub que tout est correct
```

---

## 📋 Après le Push

### Vérifications sur GitHub

- [ ] README.md s'affiche correctement sur la page d'accueil
- [ ] Tous les fichiers sont présents
- [ ] Pas de fichiers sensibles (.env, database.sqlite)
- [ ] Les liens dans le README fonctionnent
- [ ] Le `.gitignore` fonctionne (node_modules, vendor absents)

### Actions à Faire

1. **Ajouter une description** au repository
2. **Ajouter des topics** (tags)
3. **Créer un Release** v1.0.0 (optionnel)
4. **Configurer GitHub Actions** pour CI/CD (optionnel)
5. **Inviter des collaborateurs** si applicable

---

## 🎉 C'est Fait !

Votre projet LinkTracker est maintenant sur GitHub et prêt à être cloné/installé sur n'importe quelle machine !

**Test final** : Demander à quelqu'un d'autre (ou sur une autre machine) de :
1. Cloner le repository
2. Suivre `INSTALLATION.md`
3. Vérifier que tout fonctionne

**Si ça marche → Documentation complète ! ✅**
