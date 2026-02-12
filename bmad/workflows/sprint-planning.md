# Sprint Planning Workflow

**Version:** 1.0.0
**Phase:** Phase 4 - Implementation
**Required For:** All Project Levels

## Description

Workflow pour planifier un sprint. Transforme le backlog en un sprint plan actionnable avec stories priorisées, estimées, et assignées.

## When to Use

- **Début de sprint** - Avant de commencer l'implémentation
- **Après architecture** - Une fois l'architecture validée
- **Régulièrement** - À chaque itération (toutes les 1-2 semaines)

## Inputs

- `docs/prd-{project}-{date}.md` - Requirements
- `docs/architecture-{project}-{date}.md` - Architecture technique
- Backlog de stories (si existant)
- Capacité de l'équipe (points par sprint)

## Outputs

- `docs/sprint-{number}-plan.md` - Plan du sprint
- `docs/sprint-status.yaml` - Fichier de suivi
- `docs/stories/STORY-{id}.md` - Fichiers individuels par story

## Workflow Steps

### 1. Préparer le Sprint

```
Per helpers.md#Combined-Config-Load, load project config
```

#### Définir les paramètres du sprint
```yaml
sprint_number: 1
duration_weeks: 2
capacity_points: 40  # Points que l'équipe peut livrer
team_size: 1  # Nombre de développeurs
velocity: null  # Sera calculé après le sprint
```

### 2. Extraire les Features du PRD

Lire le PRD et identifier toutes les features :

```markdown
### Features from PRD:

1. **User Authentication**
   - Epic: Authentication
   - Priority: Must Have
   - Complexity: Medium

2. **Project Management**
   - Epic: Core Features
   - Priority: Must Have
   - Complexity: Medium

3. **Backlink Tracking**
   - Epic: Core Features
   - Priority: Must Have
   - Complexity: High
```

### 3. Décomposer en User Stories

Pour chaque feature, créer des stories granulaires :

#### Example: User Authentication → Stories

```markdown
### STORY-001: Setup Laravel + Vue.js Project
**Epic:** Infrastructure
**Priority:** Must Have
**Points:** 5

**User Story:**
En tant que développeur
Je veux initialiser le projet Laravel avec Vue.js
Afin d'avoir une base technique solide

**Acceptance Criteria:**
- [ ] Laravel 10.48+ installé
- [ ] Vue.js 3.4+ configuré
- [ ] PostgreSQL connectée
- [ ] Redis configuré
- [ ] README.md avec instructions

**Technical Tasks:**
- Créer projet Laravel
- Installer dépendances (Sanctum, Horizon, Telescope)
- Configurer Vue.js + Vite
- Setup Docker Compose
- Configurer .env

**Dependencies:** None (critical path)
```

#### Guidelines pour les Stories

**INVEST Criteria:**
- **I**ndependent - Peut être développée indépendamment
- **N**egotiable - Les détails peuvent être discutés
- **V**aluable - Apporte de la valeur à l'utilisateur
- **E**stimable - Peut être estimée
- **S**mall - Peut être complétée en 1 sprint
- **T**estable - Critères d'acceptance clairs

**Taille idéale:**
- **1-2 points:** Tâche simple (quelques heures)
- **3 points:** Tâche standard (1 jour)
- **5 points:** Feature moyenne (2-3 jours)
- **8 points:** Feature complexe (toute la semaine)
- **13+ points:** Trop gros, à découper

### 4. Estimer les Stories

#### Méthode: Planning Poker (Fibonacci)

```
1, 2, 3, 5, 8, 13, 21
```

#### Facteurs d'estimation:
1. **Complexité technique** - Difficulté d'implémentation
2. **Incertitude** - Combien d'unknowns
3. **Effort** - Temps de développement
4. **Risque** - Probabilité de problèmes

#### Exemples d'estimation:

```yaml
STORY-001: Setup Project
  Complexity: Low (connu)
  Uncertainty: Low (déjà fait)
  Effort: Medium (plusieurs configs)
  Risk: Low
  Estimate: 5 points

STORY-005: Implement Backlink Discovery
  Complexity: High (algorithme complexe)
  Uncertainty: High (unknowns techniques)
  Effort: High (beaucoup de code)
  Risk: High (peut ne pas marcher)
  Estimate: 13 points
```

### 5. Identifier les Dépendances

```yaml
STORY-001: Setup Project
  Dependencies: []  # Aucune, à faire en premier

STORY-002: User Authentication
  Dependencies: [STORY-001]  # Nécessite le setup

STORY-003: User Profile
  Dependencies: [STORY-002]  # Nécessite l'auth

STORY-004: Create Projects
  Dependencies: [STORY-002]  # Nécessite l'auth

STORY-005: Add Backlinks
  Dependencies: [STORY-004]  # Nécessite les projets
```

### 6. Prioriser les Stories (MoSCoW + Dependencies)

#### Ordre de priorité:
1. **Stories bloquantes** (dependencies d'autres stories)
2. **Must Have** + High Risk (pour échouer vite si ça ne marche pas)
3. **Must Have** + Low Risk
4. **Should Have**
5. **Could Have**

#### Exemple de priorisation:

```yaml
Priority 1 (Critical Path):
  - STORY-001: Setup Project (5 pts)
  - STORY-002: User Authentication (5 pts)

Priority 2 (Core Features):
  - STORY-004: Project CRUD (8 pts)
  - STORY-005: Backlink CRUD (5 pts)

Priority 3 (Supporting Features):
  - STORY-006: Background Jobs Infrastructure (5 pts)
  - STORY-007: Basic Monitoring (3 pts)

Priority 4 (Nice to Have):
  - STORY-008: Email Notifications (3 pts)
  - STORY-009: User Profile (3 pts)
```

### 7. Sélectionner les Stories pour le Sprint

#### Règles de sélection:
1. **Capacité:** Ne pas dépasser la capacité (buffer 10%)
2. **Dépendances:** Inclure toutes les dépendances des stories sélectionnées
3. **Cohérence:** Grouper par épic/thème si possible
4. **Risque:** Mix de stories à risque et sûres

#### Exemple Sprint 1:

```yaml
Sprint 1: Foundation & Projects
Capacity: 40 points
Buffer: 10% (36 points committés)

Selected Stories:
  - STORY-001: Setup Project (5 pts)
  - STORY-002: User Authentication (5 pts)
  - STORY-003: Password Reset (3 pts)
  - STORY-004: Project CRUD (8 pts)
  - STORY-005: Backlink CRUD (5 pts)
  - STORY-006: Background Jobs (5 pts)
  - STORY-007: Monitoring (3 pts)
  - STORY-008: SSRF Protection (2 pts)

Total: 36 points
Stories: 8
Average: 4.5 points per story
```

### 8. Définir le Sprint Goal

Le sprint goal est un objectif court et clair :

```markdown
## Sprint Goal

Livrer une application Laravel + Vue.js fonctionnelle avec :
- Authentification sécurisée (login/logout/reset password)
- CRUD complet des projets
- CRUD complet des backlinks
- Infrastructure de background jobs
- Protection SSRF

**Success Criteria:**
- Un utilisateur peut créer un compte, se connecter, et gérer ses projets
- Les backlinks peuvent être ajoutés manuellement
- L'infrastructure de base est prête pour le Sprint 2
```

### 9. Créer les Story Documents

Pour chaque story sélectionnée :

```
Per helpers.md#Create-Story-Document, create docs/stories/STORY-{id}.md
```

### 10. Créer le Sprint Plan

```
Per helpers.md#Save-Output-Document, workflow_name="sprint-{number}-plan"
```

### 11. Créer le Sprint Status File

```yaml
# docs/sprint-status.yaml

sprint_number: 1
sprint_goal: "Foundation & Projects"
start_date: "2026-02-09"
end_date: "2026-02-23"
duration_weeks: 2
capacity_points: 40
committed_points: 36
completed_points: 0
velocity: null

stories:
  - id: "STORY-001"
    title: "Setup Laravel + Vue.js Project"
    status: "not_started"  # not_started | in_progress | completed | blocked
    assignee: "Claude"
    points: 5
    priority: "must_have"
    epic: "Infrastructure"
    dependencies: []
    started_at: null
    completed_at: null
    actual_points: null

  - id: "STORY-002"
    title: "Implement User Authentication"
    status: "not_started"
    assignee: "Claude"
    points: 5
    priority: "must_have"
    epic: "Authentication"
    dependencies: ["STORY-001"]
    started_at: null
    completed_at: null
    actual_points: null

  # ... autres stories
```

### 12. Mettre à jour le Workflow Status

```
Per helpers.md#Update-Workflow-Status, workflow_name="sprint-planning", status="completed"
```

## Sprint Plan Template

```markdown
# Sprint {number} Plan: {Project Name} - {Theme}

**Sprint:** {number}/{total_sprints}
**Duration:** {weeks} semaines
**Start Date:** {YYYY-MM-DD}
**End Date:** {YYYY-MM-DD}
**Goal:** {Sprint goal}

---

## 🎯 Sprint Goal

{Detailed description of what will be delivered this sprint}

**Success Criteria:**
- {Criterion 1}
- {Criterion 2}

---

## 📊 Sprint Metrics

- **Committed Points:** {committed} points
- **Capacity:** {capacity} points (buffer: {buffer}%)
- **Stories:** {count} stories
- **Team:** {team_composition}
- **Velocity (previous sprint):** {velocity} points

---

## 📋 Sprint Backlog

### STORY-{id}: {Title} ⭐ **{PRIORITY}**

**Points:** {points}
**Priority:** {Must Have | Should Have | Could Have}
**Epic:** {epic_name}
**Assignee:** {name}

**User Story:**
En tant que {user}
Je veux {action}
Afin de {benefit}

**Acceptance Criteria:**
- [ ] {criterion_1}
- [ ] {criterion_2}
- [ ] {criterion_3}

**Technical Tasks:**
- {task_1}
- {task_2}

**Dependencies:**
- {STORY-xxx} - {reason}

**Risks:**
- {risk_1} - {mitigation}

---

[Repeat for all stories]

---

## 📈 Sprint Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| {Risk 1} | {Low/Med/High} | {Low/Med/High} | {Strategy} |
| {Risk 2} | {Low/Med/High} | {Low/Med/High} | {Strategy} |

---

## 📅 Sprint Schedule (Tentative)

### Week 1
**Days 1-2:** STORY-001 (Setup Project)
**Days 3-4:** STORY-002 (Authentication)
**Day 5:** STORY-003 (Password Reset) + Testing

### Week 2
**Days 6-7:** STORY-004 (Project CRUD)
**Days 8-9:** STORY-005 (Backlink CRUD) + STORY-006 (Background Jobs)
**Day 10:** STORY-007, STORY-008 + Final Testing + Retrospective

---

## ✅ Definition of Done

Une story est "Done" quand :

**Code:**
- [ ] Code écrit et fonctionne
- [ ] Code reviewed (self or peer)
- [ ] Conventions respectées

**Tests:**
- [ ] Tests unitaires écrits
- [ ] Tests d'intégration écrits
- [ ] Coverage >= 80%
- [ ] Tous les tests passent

**Documentation:**
- [ ] Code commenté (logique complexe)
- [ ] README mis à jour
- [ ] API documentée (si applicable)

**Quality:**
- [ ] Pas de bugs connus
- [ ] Performance acceptable
- [ ] Sécurité vérifiée

**Acceptance:**
- [ ] Tous les acceptance criteria validés
- [ ] Story validée par story-validator agent

---

## 📝 Notes & Assumptions

{Additional notes, assumptions, or context}

---

## 📚 References

- [PRD]({link_to_prd})
- [Architecture]({link_to_architecture})
- [Sprint Status]({link_to_sprint_status})
```

## Best Practices

### Planning
1. **Small stories** - Prefer 3-5 points stories
2. **Buffer** - Always leave 10% buffer for unknowns
3. **Dependencies first** - Start with blocking stories
4. **Risk balance** - Mix risky and safe stories

### Estimation
1. **Relative sizing** - Compare to previous stories
2. **Team consensus** - Discuss divergent estimates
3. **Break large stories** - Stories >8 points should be split
4. **Re-estimate** - Adjust if actual effort differs significantly

### Sprint Goal
1. **Concise** - One clear sentence
2. **Valuable** - Delivers user value
3. **Achievable** - Realistic given capacity
4. **Verifiable** - Clear success criteria

## Common Pitfalls

❌ **Avoid:**
- Over-committing (no buffer)
- Ignoring dependencies
- Stories too large (>8 points)
- Vague acceptance criteria
- Not defining Definition of Done

✅ **Do:**
- Keep capacity realistic
- Map dependencies clearly
- Write testable acceptance criteria
- Include technical debt stories occasionally
- Review and adapt sprint planning process

## Metrics to Track

### During Sprint
- **Burndown:** Points remaining per day
- **Velocity:** Points completed per day
- **Blocked stories:** Stories waiting on dependencies

### End of Sprint
- **Committed vs Completed:** Did we deliver what we committed?
- **Actual vs Estimated:** How accurate were estimates?
- **Velocity:** Total points completed (for next sprint planning)

## Usage Example

```bash
# Lancer sprint planning
claude --workflow bmad/workflows/sprint-planning.md

# Pour un sprint spécifique
claude --workflow bmad/workflows/sprint-planning.md --sprint 2 --capacity 45

# Mode interactif
claude --workflow bmad/workflows/sprint-planning.md --interactive
```

## Next Steps After Sprint Planning

1. **Kickoff** - Communiquer le sprint goal à l'équipe
2. **Implementation** - Commencer avec la première story
3. **Daily tracking** - Mettre à jour sprint-status.yaml quotidiennement

## Related Workflows

- `workflows/prd.md` - Product requirements
- `workflows/architecture.md` - Technical architecture
- `workflows/implementation.md` - Story implementation

## Related Agents

- `agents/sprint-planner.md` - Automatise ce workflow
- `agents/story-implementer.md` - Implémente les stories
- `agents/story-validator.md` - Valide les stories

## Sprint Retrospective

À la fin du sprint, documenter :
```markdown
## Sprint {number} Retrospective

**Completed:** {completed_points} / {committed_points} points
**Velocity:** {completed_points} points

### What Went Well ✅
- {Item 1}
- {Item 2}

### What Could Be Improved 📈
- {Item 1}
- {Item 2}

### Action Items for Next Sprint
- [ ] {Action 1}
- [ ] {Action 2}
```
