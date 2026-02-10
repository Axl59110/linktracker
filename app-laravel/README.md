# Link Tracker

Application de monitoring de backlinks pour SEO - Suivi automatique et alertes en temps réel.

## 🎯 Description

Link Tracker est une application web permettant de surveiller automatiquement vos backlinks et de détecter les changements (liens perdus, passage en nofollow, modifications d'anchor text, etc.).

**Stack Technique :**
- Backend : Laravel 10+ (PHP 8.2+)
- Frontend : Vue.js 3 + Vite 5 + Vue Router 4
- Styling : Tailwind CSS 4
- Base de données : PostgreSQL 15+
- Cache/Queue : Redis 7+
- Queue Management : Laravel Horizon
- Monitoring : Laravel Telescope

## 🚀 Fonctionnalités

### Sprint 1 (En cours)
- ✅ Gestion de projets de monitoring
- 🔄 Authentification sécurisée (Sanctum)
- 🔄 CRUD complet des backlinks
- 🔄 Protection SSRF
- 🔄 Infrastructure Docker

### Roadmap
- Sprint 2 : Monitoring automatique des backlinks
- Sprint 3 : Système d'alertes et notifications
- Sprint 4 : Métriques SEO (DA, PA, Trust Flow)
- Sprint 5 : Dashboard et visualisations
- Sprint 6 : Tests, sécurité et optimisations

## 📋 Prérequis

- PHP 8.2+
- Composer 2.x
- Node.js 18+
- PostgreSQL 15+
- Redis 7+
- Docker & Docker Compose (optionnel mais recommandé)

## 🛠️ Installation

### Avec Laravel Herd (Windows/Mac)

1. **Cloner le repository**
   ```bash
   git clone https://github.com/votre-username/linktracker.git
   cd linktracker/app-laravel
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances JavaScript**
   ```bash
   npm install
   ```

4. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configurer la base de données**
   - Créer une base PostgreSQL `linktracker`
   - Mettre à jour `.env` avec vos credentials
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=linktracker
   DB_USERNAME=votre_user
   DB_PASSWORD=votre_password
   ```

6. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

7. **Lancer l'application**
   ```bash
   # Terminal 1 - Backend
   php artisan serve

   # Terminal 2 - Frontend (Vite)
   npm run dev
   ```

8. **Accéder à l'application**
   - Frontend : http://localhost:5173
   - Backend API : http://localhost:8000

### Avec Docker

```bash
docker-compose up -d
docker-compose exec app php artisan migrate
```

## 🧪 Tests

```bash
# Tests unitaires et feature
php artisan test

# Tests avec coverage
php artisan test --coverage
```

## 📚 Documentation

- [PRD (Product Requirements)](../docs/prd-link-tracker-2026-02-09.md)
- [Architecture](../docs/architecture-link-tracker-2026-02-09.md)
- [Sprint 1 Plan](../docs/sprint-01-plan.md)

## 🔒 Sécurité

- Protection SSRF contre les URLs malveillantes
- Authentification session-based avec Sanctum
- CSRF protection
- Rate limiting sur les APIs
- Validation stricte des entrées

## 📊 Métriques

- **Stories :** 72 stories planifiées
- **Points :** 236 story points
- **Sprints :** 6 sprints (2 semaines chacun)
- **Durée estimée :** 3 mois

## 🤝 Contribution

Ce projet est géré avec la méthode BMAD (Build, Measure, Adapt, Deploy).

Pour contribuer :
1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 License

MIT License

## 👥 Auteurs

- **Développement initial** - Projet BMAD avec Claude Code

---

**Statut actuel :** 🔄 Sprint 1 en cours - Foundation & Infrastructure
