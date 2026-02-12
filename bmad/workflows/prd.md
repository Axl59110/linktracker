# PRD Workflow (Product Requirements Document)

**Version:** 1.0.0
**Phase:** Phase 2 - Planning
**Required For:** Project Level 2+

## Description

Workflow pour créer un Product Requirements Document (PRD) complet. Le PRD définit QUOI construire, POURQUOI le construire, et POUR QUI.

## When to Use

- **Project Level 2+** - Projets avec 5+ stories
- **Nouveaux features majeurs** - Fonctionnalités complexes
- **Stakeholder alignment** - Besoin d'alignement équipe/client
- **Documentation** - Besoin de référence claire

## Inputs

- Vision du produit (description initiale)
- Informations sur les utilisateurs cibles
- Business requirements (si disponibles)
- Contraintes techniques connues
- Budget et timeline (si applicable)

## Outputs

- `docs/prd-{project-name}-{date}.md` - PRD complet
- `docs/bmm-workflow-status.yaml` - Mis à jour (prd: completed)

## Workflow Steps

### 1. Préparer l'environnement
```
Per helpers.md#Combined-Config-Load, load project config
```

### 2. Comprendre le contexte

#### Questions à répondre :
- **Problème** : Quel problème résout-on ?
- **Utilisateurs** : Qui sont les utilisateurs ?
- **Valeur** : Quelle valeur apporte la solution ?
- **Success** : Comment mesure-t-on le succès ?
- **Contraintes** : Quelles sont les limites ?

### 3. Définir le scope

#### In Scope
- Fonctionnalités core (MVP)
- Utilisateurs principaux
- Plateformes supportées

#### Out of Scope
- Fonctionnalités futures
- Edge cases complexes
- Intégrations non-essentielles

### 4. Identifier les User Personas

```markdown
### Persona 1: Développeur Solo

**Background:**
- Nom: Marc, 32 ans
- Job: Développeur fullstack freelance
- Contexte: Gère 5-10 clients simultanément

**Goals:**
- Suivre les backlinks de ses clients facilement
- Détecter les liens morts rapidement
- Gagner du temps sur le reporting

**Pain Points:**
- Outils existants trop complexes
- Coût élevé des solutions SaaS
- Pas de contrôle sur les données

**Tech Savviness:** Élevé
```

### 5. Définir les Features (High-Level)

```markdown
## Core Features

### 1. Project Management
**Description:** Gérer plusieurs projets de suivi de liens

**User Story:**
En tant qu'utilisateur
Je veux créer et gérer plusieurs projets
Afin de séparer les clients/sites que je surveille

**Priority:** Must Have
**Complexity:** Medium

---

### 2. Backlink Discovery
**Description:** Découvrir automatiquement les backlinks

**User Story:**
En tant qu'utilisateur
Je veux que l'application découvre automatiquement mes backlinks
Afin de ne pas avoir à les ajouter manuellement

**Priority:** Must Have
**Complexity:** High
```

### 6. Définir les Requirements Fonctionnels

```markdown
## Functional Requirements

### FR-1: User Authentication
**Description:** Les utilisateurs doivent pouvoir créer un compte et se connecter
**Acceptance Criteria:**
- [ ] Un utilisateur peut s'inscrire avec email/password
- [ ] Un utilisateur peut se connecter
- [ ] Un utilisateur peut réinitialiser son mot de passe
- [ ] Les sessions expirent après 24h d'inactivité

### FR-2: Project Creation
**Description:** Les utilisateurs peuvent créer des projets
**Acceptance Criteria:**
- [ ] Un utilisateur peut créer un projet avec nom + URL
- [ ] Le nom du projet doit être unique par utilisateur
- [ ] L'URL doit être valide (format http/https)
- [ ] Un utilisateur ne peut créer que 10 projets (limite free tier)
```

### 7. Définir les Requirements Non-Fonctionnels

```markdown
## Non-Functional Requirements

### NFR-1: Performance
- Page load < 2 secondes
- API response time < 500ms (P95)
- Support 100 utilisateurs concurrents
- Background jobs: max 5 minutes par projet

### NFR-2: Security
- Données chiffrées au repos (DB)
- HTTPS obligatoire
- Protection CSRF, XSS, SQL Injection
- Rate limiting: 100 req/minute par user

### NFR-3: Scalability
- Architecture horizontalement scalable
- Support jusqu'à 10,000 utilisateurs
- Queue system pour jobs background

### NFR-4: Reliability
- Uptime: 99.5%
- Automated backups (daily)
- Graceful error handling

### NFR-5: Usability
- Interface intuitive (no training needed)
- Responsive design (mobile-friendly)
- Accessibility WCAG AA
```

### 8. Définir les User Flows

```markdown
## User Flow 1: Onboarding

1. User lands on homepage
2. Click "Sign Up"
3. Enter email, password, name
4. Verify email (click link)
5. Login
6. See "Create Your First Project" wizard
7. Enter project name + URL
8. See dashboard with first project

**Success Metrics:**
- 80% of new users complete flow
- Average time: < 5 minutes
```

### 9. Prioriser avec MoSCoW

```markdown
## Prioritization (MoSCoW)

### Must Have (MVP)
- User authentication
- Project CRUD
- Manual backlink add/edit/delete
- Basic monitoring (check if link exists)
- Email alerts on broken links

### Should Have (Post-MVP)
- Automatic backlink discovery
- Detailed analytics
- Multi-user projects
- API access

### Could Have (Future)
- White-label reports
- Slack/Discord integrations
- Chrome extension
- Mobile app

### Won't Have (Out of Scope)
- SEO ranking tracking
- Competitor analysis
- Content suggestions
```

### 10. Estimer l'effort

```markdown
## Effort Estimation

### Epic 1: Infrastructure & Setup
**Stories:** 2
**Points:** 8
**Duration:** 1 week

### Epic 2: Authentication
**Stories:** 3
**Points:** 13
**Duration:** 1.5 weeks

### Epic 3: Project Management
**Stories:** 4
**Points:** 20
**Duration:** 2 weeks

### Epic 4: Backlink Management
**Stories:** 6
**Points:** 34
**Duration:** 3 weeks

### Epic 5: Monitoring & Alerts
**Stories:** 5
**Points:** 25
**Duration:** 2.5 weeks

**Total:** 20 stories, 100 points, ~10 weeks
```

### 11. Sauvegarder le PRD

```
Per helpers.md#Save-Output-Document, workflow_name="prd"
Per helpers.md#Update-Workflow-Status, workflow_name="prd", status="completed"
```

## PRD Template

```markdown
# Product Requirements Document (PRD)
## {Project Name}

**Version:** 1.0
**Date:** {date}
**Author:** Claude Code
**Status:** Draft / Review / Approved

---

## Executive Summary

{2-3 paragraphs describing the product, problem, and solution}

---

## Problem Statement

### Current Situation
{What's the current state?}

### Problems
1. {Problem 1}
2. {Problem 2}

### Impact
{Who is affected and how?}

---

## Goals & Objectives

### Business Goals
- {Goal 1 with metric}
- {Goal 2 with metric}

### User Goals
- {Goal 1}
- {Goal 2}

### Success Metrics
| Metric | Target | Measurement |
|--------|--------|-------------|
| User Acquisition | 1,000 users in 3 months | Analytics |
| User Retention | 60% 30-day retention | Cohort analysis |
| Performance | <500ms API response | Monitoring |

---

## Target Users

### Primary Persona: {Name}
**Background:** {description}
**Goals:** {goals}
**Pain Points:** {pain points}
**Tech Savviness:** {level}

### Secondary Persona: {Name}
{...}

---

## Scope

### In Scope
- {Feature 1}
- {Feature 2}

### Out of Scope
- {Feature that won't be built}
- {Future consideration}

---

## Features & Requirements

### Feature 1: {Name}
**Description:** {what it does}

**User Story:**
En tant que {user}
Je veux {action}
Afin de {benefit}

**Priority:** Must Have / Should Have / Could Have
**Complexity:** Low / Medium / High

**Functional Requirements:**
- FR-1.1: {requirement}
- FR-1.2: {requirement}

---

## Non-Functional Requirements

### Performance
{requirements}

### Security
{requirements}

### Scalability
{requirements}

### Reliability
{requirements}

### Usability
{requirements}

---

## User Flows

### Flow 1: {Name}
{Step-by-step description or diagram}

---

## Prioritization (MoSCoW)

### Must Have (MVP)
- {Feature}

### Should Have
- {Feature}

### Could Have
- {Feature}

### Won't Have
- {Feature}

---

## Technical Considerations

### Architecture
{High-level architecture notes}

### Technology Stack
- **Backend:** {stack}
- **Frontend:** {stack}
- **Database:** {database}
- **Infrastructure:** {hosting}

### Third-Party Services
- {Service 1}: {purpose}
- {Service 2}: {purpose}

### Constraints
- {Constraint 1}
- {Constraint 2}

---

## Timeline & Milestones

### Phase 1: Foundation (Weeks 1-2)
- {Milestone}

### Phase 2: Core Features (Weeks 3-6)
- {Milestone}

### Phase 3: Polish & Launch (Weeks 7-8)
- {Milestone}

---

## Risks & Mitigations

| Risk | Probability | Impact | Mitigation |
|------|------------|--------|------------|
| {Risk 1} | {Low/Med/High} | {Low/Med/High} | {Strategy} |

---

## Open Questions

- [ ] {Question 1}
- [ ] {Question 2}

---

## Appendix

### References
- {Reference 1}
- {Reference 2}

### Change Log
| Date | Version | Changes | Author |
|------|---------|---------|--------|
| 2026-02-09 | 1.0 | Initial draft | Claude |
```

## Best Practices

1. **Be specific** - Avoid vague requirements
2. **Be measurable** - Define clear success criteria
3. **Be realistic** - Don't over-promise
4. **Be user-focused** - Start with user needs
5. **Be collaborative** - Get feedback early

## Common Pitfalls

❌ **Avoid:**
- Writing technical implementation details (that's for tech spec)
- Including every possible feature (focus on MVP)
- Vague requirements ("should be fast", "user-friendly")
- Forgetting non-functional requirements
- Not defining success metrics

✅ **Do:**
- Focus on WHAT and WHY, not HOW
- Prioritize ruthlessly
- Define measurable criteria
- Consider non-functional aspects
- Validate with stakeholders

## Usage Example

```bash
# Lancer le workflow PRD
claude --workflow bmad/workflows/prd.md

# Avec projet spécifique
claude --workflow bmad/workflows/prd.md --project "Link Tracker"

# Mode interactif
claude --workflow bmad/workflows/prd.md --interactive
```

## Next Steps After PRD

1. **Review** - Faire valider par stakeholders
2. **Architecture** - Lancer workflow architecture
3. **Sprint Planning** - Découper en stories et sprints

## Related Workflows

- `workflows/architecture.md` - Définir l'architecture technique
- `workflows/sprint-planning.md` - Planifier l'implémentation
