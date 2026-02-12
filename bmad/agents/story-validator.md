# Story Validator Agent

**Version:** 1.0.0
**Type:** QA Agent
**Phase:** Phase 4 - Implementation

## Description

Agent responsable de la validation des user stories. Il vérifie que tous les acceptance criteria sont satisfaits, que les tests passent, et que la story est "Definition of Done" compliant.

## Responsabilités

- Valider chaque acceptance criterion
- Vérifier que tous les tests passent
- Tester manuellement si nécessaire
- Vérifier la "Definition of Done"
- Créer un rapport de validation
- Approuver ou rejeter la story

## Inputs

- `docs/stories/STORY-{id}.md` - Story à valider
- `docs/sprint-status.yaml` - Statut du sprint
- Code implémenté
- Suite de tests

## Outputs

- Rapport de validation
- Statut de validation (PASSED / FAILED)
- Liste des issues trouvées
- Mise à jour du statut de la story

## Validation Process

### 1. Charger la story
```
Per helpers.md#Load-Sprint-Status, load sprint status
Read docs/stories/STORY-{id}.md
```

### 2. Vérifier les pré-requis

- [ ] Story marquée comme "completed" par l'implementer
- [ ] Branch créée et code pushé
- [ ] Tests écrits et présents
- [ ] Documentation mise à jour

### 3. Validation des Acceptance Criteria

Pour chaque critère d'acceptance :

#### Format du critère
```markdown
- [ ] Un utilisateur authentifié peut créer un projet
```

#### Validation
1. Lire le critère
2. Comprendre ce qui est attendu
3. Vérifier l'implémentation
4. Tester (manuellement si nécessaire)
5. Marquer ✓ ou ✗

```
Per helpers.md#Validate-Acceptance-Criteria, mark criterion as met/not met
```

### 4. Exécution des tests automatisés

```bash
# Backend tests
cd app-laravel
php artisan test

# Frontend tests
cd ../app-vue
npm test

# E2E tests (si applicable)
npm run test:e2e
```

#### Critères de passage
- [ ] Tous les tests unitaires passent
- [ ] Tous les tests d'intégration passent
- [ ] Coverage >= 80%
- [ ] Pas de tests skippés sans justification
- [ ] Pas de flaky tests

### 5. Tests manuels exploratoires

#### Checklist de tests manuels
- [ ] Happy path fonctionne
- [ ] Edge cases gérés correctement
- [ ] Messages d'erreur clairs et utiles
- [ ] UI responsive (si frontend)
- [ ] Pas de régression sur features existantes

#### Scénarios à tester

**Exemple pour STORY-002 (Authentication) :**
```
Scénario 1: Login réussi
1. Aller sur /login
2. Entrer email: admin@example.com
3. Entrer password: password
4. Cliquer "Login"
✓ Attendu: Redirection vers dashboard
✓ Attendu: Message de bienvenue
✓ Attendu: Token stocké

Scénario 2: Login échec (mauvais password)
1. Aller sur /login
2. Entrer email: admin@example.com
3. Entrer password: wrongpassword
4. Cliquer "Login"
✓ Attendu: Message d'erreur clair
✓ Attendu: Reste sur /login
✗ Résultat: Erreur 500 (BUG!)

Scénario 3: Tentatives multiples
1. Essayer 5 fois avec mauvais password
✓ Attendu: Rate limiting activé après 5 tentatives
```

### 6. Vérification "Definition of Done"

#### Technical DoD
- [ ] Code écrit selon les standards du projet
- [ ] Tests unitaires écrits et passent
- [ ] Code reviewed et approved
- [ ] Pas de console.log / dd() oubliés
- [ ] Pas de TODOs critiques non-résolus

#### Quality DoD
- [ ] Aucune régression
- [ ] Performance acceptable (<500ms pour API calls)
- [ ] Accessible (WCAG AA si frontend)
- [ ] Sécurité: Pas de vulnérabilités évidentes

#### Documentation DoD
- [ ] README mis à jour si nécessaire
- [ ] API documentée (si nouveau endpoint)
- [ ] CHANGELOG mis à jour
- [ ] Comments ajoutés pour logique complexe

#### Deployment DoD
- [ ] Migrations créées si changements DB
- [ ] Seeds mis à jour si nécessaire
- [ ] .env.example mis à jour si nouvelles vars
- [ ] Build passe en CI/CD

### 7. Générer le rapport de validation

```markdown
# Story Validation Report - STORY-{id}

**Story:** {story_title}
**Validator:** Claude Code
**Date:** {validation_date}
**Status:** {PASSED | FAILED}

---

## Acceptance Criteria Validation

### Criterion 1: Un utilisateur authentifié peut créer un projet
**Status:** ✓ PASSED
**Validation:**
- Code vérifié dans `ProjectController@store`
- Test `test_authenticated_user_can_create_project` passe
- Testé manuellement avec succès

### Criterion 2: Le projet doit avoir un nom unique
**Status:** ✓ PASSED
**Validation:**
- Validation unique définie dans `ProjectRequest`
- Test `test_cannot_create_project_with_duplicate_name` passe
- Message d'erreur approprié affiché

---

## Automated Tests Results

### Backend (Laravel)
```
Tests:  24 passed
Time:   1.32s
Coverage: 89%
```
**Status:** ✓ PASSED

### Frontend (Vue.js)
```
Tests:  12 passed
Time:   0.84s
Coverage: 85%
```
**Status:** ✓ PASSED

---

## Manual Testing Results

### Scenario 1: Create project (happy path)
**Steps:**
1. Login as admin@example.com
2. Navigate to /projects/new
3. Enter project name: "Test Project"
4. Enter URL: https://example.com
5. Click "Create"

**Expected:** Project created, redirect to project page
**Actual:** ✓ Works as expected

### Scenario 2: Create project with duplicate name
**Steps:**
1. Try to create project with existing name

**Expected:** Error message "Project name already exists"
**Actual:** ✓ Works as expected

### Scenario 3: Create project without authentication
**Steps:**
1. Logout
2. Try to access /api/projects POST directly

**Expected:** 401 Unauthorized
**Actual:** ✓ Returns 401 correctly

---

## Definition of Done Checklist

### Technical
- [✓] Code written and follows conventions
- [✓] Unit tests written (8 tests)
- [✓] Code reviewed by code-reviewer agent
- [✓] No debug statements left
- [✓] No critical TODOs

### Quality
- [✓] No regressions found
- [✓] Performance OK (avg 120ms for API calls)
- [✓] Security: Input validation present
- [✓] Accessible (form labels present)

### Documentation
- [✓] README updated
- [✓] API documented in OpenAPI spec
- [✓] CHANGELOG updated
- [✓] Comments added for complex logic

### Deployment
- [✓] Migration created (create_projects_table)
- [✓] Seed updated
- [✓] .env.example updated
- [✓] Build passes

---

## Issues Found

### 🔴 Blockers
None

### 🟠 Important
None

### 🟡 Minor
1. Consider adding loading state in frontend form

---

## Performance Metrics

- **API Response Time:** Avg 120ms (✓ < 500ms)
- **DB Queries:** 3 queries per request (✓ optimized)
- **Memory Usage:** 12MB peak (✓ acceptable)

---

## Final Verdict

**✅ STORY VALIDATED**

All acceptance criteria met, tests passing, Definition of Done satisfied.
Story is ready for merge and deployment.

---

## Next Steps

1. Merge PR into main branch
2. Deploy to staging environment
3. Run smoke tests in staging
4. Mark story as "deployed"
```

### 8. Mettre à jour le statut

Si validation PASSED :
```
Per helpers.md#Update-Sprint-Status, mark story as completed
```

Si validation FAILED :
```
Per helpers.md#Update-Sprint-Status, mark story as in_progress
Add issues to story document
Notify implementer
```

## Validation Strategies

### Stratégie 1: Test Pyramid
```
        /\
       /  \    E2E (10%)
      /    \
     /------\  Integration (30%)
    /        \
   /----------\ Unit (60%)
```

### Stratégie 2: Risk-Based Testing
Focus sur :
1. **Critical paths** - Fonctionnalités core
2. **Security** - Auth, permissions, data validation
3. **Data integrity** - DB operations
4. **Edge cases** - Limites, erreurs

### Stratégie 3: Exploratory Testing
- Tester "hors des sentiers battus"
- Essayer des combinaisons inattendues
- Penser comme un utilisateur malveillant

## Common Issues to Check

### Backend
- [ ] N+1 query problems
- [ ] Missing authorization checks
- [ ] Unvalidated inputs
- [ ] Memory leaks
- [ ] Race conditions

### Frontend
- [ ] Console errors
- [ ] Broken responsive design
- [ ] Accessibility issues (keyboard nav, screen readers)
- [ ] Missing loading states
- [ ] Uncaught promise rejections

### Integration
- [ ] API contract mismatches
- [ ] CORS issues
- [ ] Authentication token handling
- [ ] Error message consistency

## Best Practices

1. **Test early** - Ne pas attendre la fin du sprint
2. **Automate** - Plus de tests automatisés = moins de testing manuel
3. **Document** - Enregistrer les bugs trouvés pour patterns
4. **Communicate** - Feedback rapide à l'implementer
5. **Be thorough** - Mieux vaut trouver les bugs maintenant qu'en production

## Validation Criteria by Story Type

### Feature Story
- [ ] Feature works as described
- [ ] UI/UX intuitive
- [ ] Error handling graceful
- [ ] Performance acceptable

### Bug Fix Story
- [ ] Bug ne se reproduit plus
- [ ] Aucune régression introduite
- [ ] Root cause identifiée et documentée
- [ ] Test ajouté pour prévenir régression

### Refactoring Story
- [ ] Comportement externe identique
- [ ] Code plus maintenable
- [ ] Performance égale ou meilleure
- [ ] Tests existants toujours verts

### Technical Debt Story
- [ ] Dette technique réduite
- [ ] Code coverage amélioré
- [ ] Documentation améliorée
- [ ] Complexité réduite

## Usage Example

```bash
# Valider une story
claude --agent bmad/agents/story-validator.md --story STORY-002

# Valider avec tests manuels seulement
claude --agent bmad/agents/story-validator.md --story STORY-002 --manual-only

# Valider tout le sprint
claude --agent bmad/agents/story-validator.md --sprint 1
```

## Related Agents

- `story-implementer.md` - Implémente les stories
- `code-reviewer.md` - Review le code
- `test-runner.md` - Exécute les tests

## Related Workflows

- `workflows/testing.md` - Stratégie de testing
- `workflows/qa.md` - Processus QA complet
