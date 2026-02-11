# Guide de Contribution - Link Tracker

Merci de votre intérêt pour contribuer à LinkTracker ! Ce guide vous aide à comprendre notre processus de développement.

---

## 📋 Avant de Commencer

1. **Installer l'application** : Suivre `INSTALLATION.md`
2. **Lire le README** : Comprendre l'architecture
3. **Consulter le PRD** : `docs/prd-link-tracker-2026-02-09.md`
4. **Vérifier les issues** : Voir les tâches en cours

---

## 🔄 Processus de Développement (BMAD Method)

Ce projet suit la **BMAD Method** (Build-Measure-Adapt-Deploy).

### Structure des Sprints

Les tâches sont organisées en **sprints de 2 semaines** avec des **stories** (user stories).

**Consulter** :
- `docs/sprint-status.yaml` - État du sprint actuel
- `docs/sprint-01-plan.md` - Plan du sprint

### Workflow Git

```bash
# 1. Créer une branche depuis master
git checkout master
git pull origin master
git checkout -b feature/STORY-XXX-description

# 2. Développer et commiter régulièrement
git add .
git commit -m "feat(STORY-XXX): Description courte

Description détaillée si nécessaire

Co-Authored-By: Votre Nom <email@example.com>"

# 3. Pousser et créer une Pull Request
git push origin feature/STORY-XXX-description
gh pr create --title "STORY-XXX: Titre" --body "Description"

# 4. Après review et merge
git checkout master
git pull origin master
git branch -d feature/STORY-XXX-description
```

---

## 📝 Conventions de Code

### PHP (Laravel)

- **PSR-12** pour le style de code
- **Namespaces** : Suivre la structure Laravel
- **Eloquent** : Préférer Eloquent aux requêtes SQL brutes
- **Services** : Logique métier dans `app/Services/`
- **Resources** : API Resources pour les réponses JSON

**Exemple** :
```php
<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Collection;

class ProjectService
{
    public function getAllProjects(): Collection
    {
        return Project::with('backlinks')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

### JavaScript (Vue.js)

- **Composition API** (préféré au Options API)
- **TypeScript** : Optionnel mais encouragé
- **Script setup** : Utiliser `<script setup>` dans les composants
- **Nommage** : PascalCase pour les composants

**Exemple** :
```vue
<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';

const projects = ref([]);
const router = useRouter();

onMounted(async () => {
  const response = await fetch('/api/projects');
  projects.value = await response.json();
});
</script>

<template>
  <div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold">Projets</h1>
    <!-- ... -->
  </div>
</template>
```

### CSS (Tailwind)

- **Utility-first** : Utiliser les classes Tailwind
- **Composants** : Extraire les patterns répétitifs
- **Responsive** : Mobile-first avec les breakpoints Tailwind

---

## 🧪 Tests

### Exécuter les Tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter ProjectTest

# Avec couverture
php artisan test --coverage
```

### Écrire des Tests

**Feature Test** :
```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class ProjectTest extends TestCase
{
    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/projects', [
                'name' => 'Mon Projet',
                'url' => 'https://example.com',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('projects', ['name' => 'Mon Projet']);
    }
}
```

---

## 📦 Commits

### Format des Messages

```
type(scope): Sujet court (max 72 caractères)

Description détaillée (optionnelle)

BREAKING CHANGE: Description si applicable
Refs: #123, #456
```

### Types de Commit

- `feat`: Nouvelle fonctionnalité
- `fix`: Correction de bug
- `docs`: Documentation uniquement
- `style`: Formatage, pas de changement de code
- `refactor`: Refactoring sans changement fonctionnel
- `test`: Ajout/modification de tests
- `chore`: Tâches de maintenance (build, config, etc.)

### Exemples

```bash
# Feature
git commit -m "feat(auth): Add Sanctum authentication endpoints"

# Fix
git commit -m "fix(projects): Correct validation rules for URL field"

# Docs
git commit -m "docs: Update installation guide with PostgreSQL steps"

# Refactor
git commit -m "refactor(services): Extract backlink logic to service layer"
```

---

## 🔀 Pull Requests

### Avant de Créer une PR

- [ ] Code lint (PSR-12, ESLint)
- [ ] Tests passent (`php artisan test`)
- [ ] Assets compilés (`npm run build`)
- [ ] Documentation mise à jour si nécessaire
- [ ] Pas de fichiers sensibles (.env, database.sqlite)

### Titre de la PR

```
STORY-XXX: Description courte et claire
```

### Description de la PR

```markdown
## Summary
Brève description de ce qui a été fait

## Changes
- Ajout de X
- Modification de Y
- Suppression de Z

## Test Plan
- [ ] Testé manuellement : http://linktracker.test/...
- [ ] Tests automatisés ajoutés
- [ ] Vérifié sur Chrome et Firefox

## Screenshots (si applicable)
![Screenshot](url)

## Notes
Informations supplémentaires pour les reviewers
```

---

## 🔍 Code Review

### Pour les Reviewers

- **Fonctionnel** : Le code fait-il ce qu'il doit faire ?
- **Lisible** : Le code est-il clair et bien structuré ?
- **Testé** : Y a-t-il des tests suffisants ?
- **Performance** : Y a-t-il des problèmes de performance ?
- **Sécurité** : Y a-t-il des vulnérabilités (XSS, SQL injection, etc.) ?

### Pour les Contributeurs

- **Répondre rapidement** aux commentaires
- **Expliquer** les choix techniques si demandé
- **Accepter** les suggestions constructives
- **Améliorer** le code suite aux reviews

---

## 🚀 Déploiement

Le déploiement est géré automatiquement via CI/CD.

### Environnements

- **Development** : http://linktracker.test (local)
- **Staging** : TBD
- **Production** : TBD

### Process

1. **Merge** dans `master`
2. **CI/CD** exécute tests et build
3. **Deploy** automatique vers staging
4. **Review** manuel
5. **Promote** vers production si OK

---

## 🐛 Signaler un Bug

### Template d'Issue

```markdown
## Description
Brève description du bug

## Étapes pour Reproduire
1. Aller sur...
2. Cliquer sur...
3. Constater...

## Comportement Attendu
Ce qui devrait se passer

## Comportement Actuel
Ce qui se passe réellement

## Environnement
- OS: Windows 11
- Navigateur: Chrome 120
- Laravel: 10.50.0
- PHP: 8.4.16

## Screenshots
![Screenshot](url)

## Logs
```
Copier les logs ici
```
```

---

## 💡 Proposer une Fonctionnalité

### Template d'Issue

```markdown
## Problème à Résoudre
Quel problème cette fonctionnalité résout-elle ?

## Solution Proposée
Description de la fonctionnalité

## Alternatives Considérées
Autres approches possibles

## Mockups/Wireframes
![Mockup](url)

## Impact
- Utilisateurs concernés
- Complexité estimée
- Dépendances
```

---

## 📚 Ressources

### Documentation Technique

- **Laravel** : https://laravel.com/docs/10.x
- **Vue.js** : https://vuejs.org/
- **Tailwind** : https://tailwindcss.com/

### Documentation Projet

- `README.md` - Vue d'ensemble
- `INSTALLATION.md` - Installation détaillée
- `docs/prd-link-tracker-2026-02-09.md` - Product Requirements
- `docs/architecture-link-tracker-2026-02-09.md` - Architecture

### BMAD Method

- `bmad/config.yaml` - Configuration
- `docs/sprint-status.yaml` - État du sprint

---

## ❓ Questions ?

- **Discord/Slack** : Rejoindre la communauté
- **Email** : contact@linktracker.example
- **GitHub Issues** : Ouvrir une issue

---

Merci de contribuer à LinkTracker ! 🚀
