# BMAD Method v6 - Documentation

**Project:** Link Tracker
**Created:** 2026-02-09
**Version:** 6.0

---

## 📚 Table des Matières

1. [Introduction](#introduction)
2. [Structure](#structure)
3. [Agents](#agents)
4. [Workflows](#workflows)
5. [Helpers](#helpers)
6. [Templates](#templates)
7. [Quick Start](#quick-start)
8. [Usage Examples](#usage-examples)

---

## Introduction

BMAD (Build, Measure, Analyze, Deploy) Method est une méthodologie de développement agile structurée pour gérer des projets de logiciels de niveau 2+ (5+ user stories).

### Principes

1. **Structuré mais flexible** - Framework clair, adaptable aux besoins
2. **Documentation vivante** - Documentation qui évolue avec le projet
3. **Automatisable** - Agents et workflows peuvent être automatisés
4. **Traçable** - Suivi complet du projet de l'idée au déploiement

### Phases

```
Phase 1: Analysis (Optional)
├── Product Brief
├── Brainstorming
└── Research

Phase 2: Planning (Required)
├── PRD (Product Requirements Document)
├── Tech Spec
└── UX Design

Phase 3: Solutioning (Required for Level 2+)
├── Architecture
└── Gate Check

Phase 4: Implementation (Required)
├── Sprint Planning
├── Story Implementation
└── Validation
```

---

## Structure

```
bmad/
├── README.md                 # Ce fichier
├── config.yaml               # Configuration du projet
├── helpers.md                # Fonctions utilitaires réutilisables
│
├── agents/                   # Agents spécialisés
│   ├── sprint-planner.md     # Planification de sprint
│   ├── story-implementer.md  # Implémentation de stories
│   ├── code-reviewer.md      # Review de code
│   └── story-validator.md    # Validation de stories
│
├── workflows/                # Workflows de développement
│   ├── prd.md                # Créer un PRD
│   ├── architecture.md       # Concevoir l'architecture
│   ├── sprint-planning.md    # Planifier un sprint
│   └── implementation.md     # Implémenter une story
│
└── templates/                # Templates de documents
    └── story.md              # Template de user story
```

---

## Agents

Les agents sont des "assistants spécialisés" qui automatisent des tâches spécifiques.

### 🗓️ Sprint Planner
**Fichier:** `agents/sprint-planner.md`

**Responsabilités:**
- Analyser le backlog
- Prioriser les stories
- Estimer la capacité
- Créer le sprint plan

**Usage:**
```bash
claude --agent bmad/agents/sprint-planner.md --sprint 1 --capacity 40
```

---

### 💻 Story Implementer
**Fichier:** `agents/story-implementer.md`

**Responsabilités:**
- Sélectionner la prochaine story
- Implémenter le code
- Écrire les tests
- Valider les acceptance criteria

**Usage:**
```bash
claude --agent bmad/agents/story-implementer.md --story STORY-002
```

---

### 🔍 Code Reviewer
**Fichier:** `agents/code-reviewer.md`

**Responsabilités:**
- Review du code implémenté
- Détection de bugs
- Analyse de sécurité (OWASP)
- Suggestions d'amélioration

**Usage:**
```bash
claude --agent bmad/agents/code-reviewer.md --story STORY-002
```

---

### ✅ Story Validator
**Fichier:** `agents/story-validator.md`

**Responsabilités:**
- Valider les acceptance criteria
- Exécuter les tests
- Vérifier la "Definition of Done"
- Approuver ou rejeter la story

**Usage:**
```bash
claude --agent bmad/agents/story-validator.md --story STORY-002
```

---

## Workflows

Les workflows sont des processus étape par étape pour accomplir des tâches complexes.

### 📄 PRD Workflow
**Fichier:** `workflows/prd.md`
**Phase:** 2 - Planning
**Durée:** 4-8 heures

Crée un Product Requirements Document complet définissant QUOI construire.

**Outputs:**
- `docs/prd-{project}-{date}.md`

---

### 🏗️ Architecture Workflow
**Fichier:** `workflows/architecture.md`
**Phase:** 3 - Solutioning
**Durée:** 4-8 heures

Conçoit l'architecture technique définissant COMMENT construire.

**Outputs:**
- `docs/architecture-{project}-{date}.md`

---

### 📅 Sprint Planning Workflow
**Fichier:** `workflows/sprint-planning.md`
**Phase:** 4 - Implementation
**Durée:** 2-4 heures

Planifie un sprint avec stories priorisées et estimées.

**Outputs:**
- `docs/sprint-{number}-plan.md`
- `docs/sprint-status.yaml`

---

### 🚀 Implementation Workflow
**Fichier:** `workflows/implementation.md`
**Phase:** 4 - Implementation
**Durée:** Variable (par story)

Guide l'implémentation d'une user story du code aux tests.

**Outputs:**
- Code + tests
- `docs/stories/STORY-{id}.md` (updated)

---

## Helpers

**Fichier:** `helpers.md`

Fonctions utilitaires réutilisables appelées par les agents et workflows.

### Principales fonctions:

| Helper | Usage | Description |
|--------|-------|-------------|
| `Combined-Config-Load` | `Per helpers.md#Combined-Config-Load` | Charger config complète |
| `Load-Sprint-Status` | `Per helpers.md#Load-Sprint-Status` | Charger statut sprint |
| `Update-Sprint-Status` | `Per helpers.md#Update-Sprint-Status` | Mettre à jour story |
| `Get-Next-Story` | `Per helpers.md#Get-Next-Story` | Obtenir prochaine story |
| `Check-Dependencies-Met` | `Per helpers.md#Check-Dependencies-Met` | Vérifier dépendances |
| `Validate-Acceptance-Criteria` | `Per helpers.md#Validate-Acceptance-Criteria` | Valider critère |
| `Save-Output-Document` | `Per helpers.md#Save-Output-Document` | Sauvegarder document |

---

## Templates

### Story Template
**Fichier:** `templates/story.md`

Template complet pour créer une user story avec :
- User story format
- Acceptance criteria
- Technical specification
- Test scenarios
- Definition of Done
- Implementation notes

---

## Quick Start

### 1. Nouveau Projet

```bash
# 1. Initialiser la config BMAD
cat bmad/config.yaml

# 2. Créer le PRD
claude --workflow bmad/workflows/prd.md

# 3. Concevoir l'architecture
claude --workflow bmad/workflows/architecture.md

# 4. Planifier le premier sprint
claude --workflow bmad/workflows/sprint-planning.md --sprint 1 --capacity 40

# 5. Implémenter les stories
claude --agent bmad/agents/story-implementer.md
```

### 2. Sprint en Cours

```bash
# Voir le statut actuel
cat docs/sprint-status.yaml

# Implémenter la prochaine story
claude --agent bmad/agents/story-implementer.md

# Review du code
claude --agent bmad/agents/code-reviewer.md --story STORY-002

# Valider la story
claude --agent bmad/agents/story-validator.md --story STORY-002

# Générer un rapport
claude Per helpers.md#Generate-Sprint-Report
```

### 3. Nouveau Sprint

```bash
# Rétrospective du sprint précédent
cat docs/sprint-status.yaml  # Review completed vs committed

# Planifier le prochain sprint
claude --workflow bmad/workflows/sprint-planning.md --sprint 2
```

---

## Usage Examples

### Example 1: Créer et implémenter une story

```bash
# 1. Créer la story à partir du template
cp bmad/templates/story.md docs/stories/STORY-005.md

# 2. Éditer la story
# (remplir les détails manuellement ou via agent)

# 3. Ajouter au sprint
# (éditer docs/sprint-status.yaml)

# 4. Implémenter
claude --agent bmad/agents/story-implementer.md --story STORY-005

# 5. Review
claude --agent bmad/agents/code-reviewer.md --story STORY-005

# 6. Valider
claude --agent bmad/agents/story-validator.md --story STORY-005
```

### Example 2: Workflow complet PRD → Implementation

```bash
# Phase 2: Planning
claude --workflow bmad/workflows/prd.md
# Output: docs/prd-link-tracker-2026-02-09.md

# Phase 3: Solutioning
claude --workflow bmad/workflows/architecture.md --prd docs/prd-link-tracker-2026-02-09.md
# Output: docs/architecture-link-tracker-2026-02-09.md

# Phase 4: Implementation
claude --workflow bmad/workflows/sprint-planning.md --sprint 1 --capacity 40
# Output: docs/sprint-01-plan.md, docs/sprint-status.yaml

# Implement stories
for story in STORY-001 STORY-002 STORY-003; do
  claude --agent bmad/agents/story-implementer.md --story $story
  claude --agent bmad/agents/code-reviewer.md --story $story
  claude --agent bmad/agents/story-validator.md --story $story
done

# Sprint retrospective
claude Per helpers.md#Generate-Sprint-Report > docs/sprint-01-retrospective.md
```

### Example 3: Utiliser les helpers directement

```bash
# Charger la config
claude Per helpers.md#Combined-Config-Load

# Obtenir la prochaine story
claude Per helpers.md#Get-Next-Story

# Vérifier les dépendances d'une story
claude Per helpers.md#Check-Dependencies-Met --story STORY-005

# Marquer une story comme complétée
claude Per helpers.md#Update-Sprint-Status --story STORY-003 --status completed --actual-points 5

# Générer un rapport de sprint
claude Per helpers.md#Generate-Sprint-Report
```

---

## Fichiers de Suivi

### `docs/bmm-workflow-status.yaml`
Suivi des workflows complétés (PRD, Architecture, etc.)

```yaml
workflow_status:
  - name: prd
    status: "docs/prd-link-tracker-2026-02-09.md"
    completed_at: "2026-02-09T22:11:35Z"

  - name: architecture
    status: "docs/architecture-link-tracker-2026-02-09.md"
    completed_at: "2026-02-09T23:45:00Z"
```

### `docs/sprint-status.yaml`
Suivi du sprint en cours

```yaml
sprint_number: 1
sprint_goal: "Foundation & Projects"
capacity_points: 40
committed_points: 36
completed_points: 15

stories:
  - id: "STORY-001"
    status: "completed"
    points: 5
    actual_points: 5
    completed_at: "2026-02-10"

  - id: "STORY-002"
    status: "in_progress"
    points: 5
```

---

## Best Practices

### 1. Documentation
- ✅ Garder les documents à jour
- ✅ Documenter les décisions importantes (ADRs)
- ✅ Mettre à jour les statuts régulièrement
- ❌ Ne pas créer de documentation "morte"

### 2. Sprints
- ✅ Garder les sprints courts (1-2 semaines)
- ✅ Prévoir un buffer de 10%
- ✅ Faire une rétrospective à chaque fin de sprint
- ❌ Ne pas sur-engager

### 3. Stories
- ✅ Garder les stories petites (3-5 points idéal)
- ✅ Définir des acceptance criteria clairs et testables
- ✅ Identifier les dépendances
- ❌ Ne pas créer de stories > 8 points

### 4. Code
- ✅ Faire des reviews systématiques
- ✅ Écrire les tests d'abord (TDD)
- ✅ Commiter fréquemment
- ❌ Ne pas skipper les tests "pour gagner du temps"

---

## Troubleshooting

### Problème: Story bloquée par dépendances

**Solution:**
```bash
# Vérifier les dépendances
claude Per helpers.md#Check-Dependencies-Met --story STORY-005

# Si bloquée, implémenter les dépendances d'abord
# ou retirer la dépendance si elle n'est plus nécessaire
```

### Problème: Sprint velocity instable

**Solution:**
- Revoir les estimations (trop optimistes?)
- Vérifier si des stories sont trop grosses
- Considérer des imprévus (prévoir buffer)
- Utiliser la velocity moyenne des 3 derniers sprints

### Problème: Tests qui échouent

**Solution:**
```bash
# Isoler le problème
php artisan test --filter=ProjectTest

# Vérifier les logs
tail -f storage/logs/laravel.log

# Debug un test spécifique
php artisan test --filter=test_can_create_project

# Ne JAMAIS marquer une story completed si tests échouent
```

---

## Contributeurs

- **Claude Code** - Agent principal d'implémentation
- **BMAD Framework** - Méthodologie v6

---

## Changelog

### v1.0.0 (2026-02-09)
- ✨ Structure initiale BMAD
- ✨ 4 agents créés (sprint-planner, story-implementer, code-reviewer, story-validator)
- ✨ 4 workflows créés (prd, architecture, sprint-planning, implementation)
- ✨ Helpers et templates
- 📚 Documentation complète

---

## License

Ce framework BMAD est utilisé dans le cadre du projet Link Tracker.

---

## Support

Pour questions ou support :
1. Consulter cette documentation
2. Lire les fichiers individuels des agents/workflows
3. Vérifier `helpers.md` pour les fonctions utilitaires
