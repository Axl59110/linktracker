# Architecture Workflow

**Version:** 1.0.0
**Phase:** Phase 3 - Solutioning
**Required For:** Project Level 2+

## Description

Workflow pour concevoir l'architecture technique du système. Définit COMMENT le système sera construit, quelles technologies, patterns, et structure de code.

## When to Use

- **Après PRD** - Une fois les requirements définis
- **Nouveaux projets** - Pour établir les fondations
- **Refactoring majeur** - Changement d'architecture
- **Scaling** - Préparation à la croissance

## Inputs

- `docs/prd-{project}-{date}.md` - Product Requirements Document
- Requirements fonctionnels et non-fonctionnels
- Contraintes techniques connues
- Stack technique préférée

## Outputs

- `docs/architecture-{project}-{date}.md` - Document d'architecture complet
- Diagrammes d'architecture (texte ou liens)
- `docs/bmm-workflow-status.yaml` - Mis à jour

## Workflow Steps

### 1. Analyser les Requirements

```
Per helpers.md#Combined-Config-Load, load project config
Read docs/prd-{project}-{date}.md
```

#### Extraire :
- **Functional requirements** - Features à implémenter
- **Non-functional requirements** - Performance, sécurité, scalabilité
- **Contraintes** - Budget, timeline, tech stack imposée
- **Scale** - Nombre d'utilisateurs, data volume

### 2. Choisir l'Architecture Pattern

#### Options courantes :

**Monolith**
- ✅ Simple, rapide à développer
- ✅ Bon pour MVP, petites apps
- ❌ Difficile à scale horizontalement
- 📦 Use case: <10k users, single team

**Modular Monolith**
- ✅ Organisation claire par modules
- ✅ Peut évoluer vers microservices
- ✅ Bon équilibre complexité/scalabilité
- 📦 Use case: 10k-100k users, growing team

**Microservices**
- ✅ Scale indépendamment
- ✅ Technologies multiples
- ❌ Complexité élevée
- ❌ Overhead opérationnel
- 📦 Use case: 100k+ users, multiple teams

**Serverless**
- ✅ Pas de gestion infrastructure
- ✅ Scale automatique
- ❌ Vendor lock-in
- ❌ Cold start latency
- 📦 Use case: Workloads intermittents

### 3. Définir les Layers/Modules

#### Architecture en Layers (Recommandé pour la plupart des projets)

```
┌─────────────────────────────────┐
│     Presentation Layer          │  Vue.js, UI Components
├─────────────────────────────────┤
│     Application Layer           │  Controllers, Use Cases
├─────────────────────────────────┤
│     Domain Layer                │  Business Logic, Entities
├─────────────────────────────────┤
│     Infrastructure Layer        │  DB, APIs, Queue, Cache
└─────────────────────────────────┘
```

#### Modules par Domaine (pour projets complexes)

```
app/
├── Modules/
│   ├── User/              # Authentication, Profile
│   ├── Project/           # Project Management
│   ├── Backlink/          # Backlink Tracking
│   └── Monitoring/        # Health Checks
```

### 4. Choisir le Tech Stack

#### Backend
```yaml
language: PHP 8.2+
framework: Laravel 10.48+
database: PostgreSQL 15+
cache: Redis 7+
queue: Redis + Horizon
api: RESTful (Laravel API Resources)
```

#### Frontend
```yaml
framework: Vue.js 3.4+
build_tool: Vite 5+
state: Pinia (Vuex alternative)
styling: Tailwind CSS 4+
http_client: Axios
router: Vue Router 4+
```

#### Infrastructure
```yaml
hosting: Docker + Docker Compose (dev/prod)
web_server: Nginx
process_manager: Supervisor
ci_cd: GitHub Actions
monitoring: Laravel Telescope (dev), Sentry (prod)
```

### 5. Concevoir la Database Schema

```sql
-- Projects table
CREATE TABLE projects (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(2048) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    deleted_at TIMESTAMP NULL,
    UNIQUE(user_id, name)
);

CREATE INDEX idx_projects_user_id ON projects(user_id);
CREATE INDEX idx_projects_status ON projects(status);

-- Backlinks table
CREATE TABLE backlinks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    source_url VARCHAR(2048) NOT NULL,
    target_url VARCHAR(2048) NOT NULL,
    anchor_text TEXT,
    discovered_at TIMESTAMP DEFAULT NOW(),
    last_checked_at TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending', -- pending, active, broken, removed
    http_status_code INT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_backlinks_project_id ON backlinks(project_id);
CREATE INDEX idx_backlinks_status ON backlinks(status);
CREATE INDEX idx_backlinks_last_checked ON backlinks(last_checked_at);
```

### 6. Définir les API Endpoints

```markdown
## API Endpoints

### Authentication
- POST /api/auth/register - Register new user
- POST /api/auth/login - Login
- POST /api/auth/logout - Logout
- POST /api/auth/refresh - Refresh token
- POST /api/auth/forgot-password - Request password reset
- POST /api/auth/reset-password - Reset password

### Projects
- GET    /api/projects - List user's projects
- POST   /api/projects - Create project
- GET    /api/projects/{id} - Get project details
- PUT    /api/projects/{id} - Update project
- DELETE /api/projects/{id} - Delete project (soft delete)

### Backlinks
- GET    /api/projects/{projectId}/backlinks - List project backlinks
- POST   /api/projects/{projectId}/backlinks - Add backlink manually
- GET    /api/backlinks/{id} - Get backlink details
- PUT    /api/backlinks/{id} - Update backlink
- DELETE /api/backlinks/{id} - Delete backlink
- POST   /api/backlinks/{id}/check - Manually trigger status check

### Monitoring (Background Jobs)
- POST /api/projects/{projectId}/scan - Trigger backlink scan
- GET  /api/projects/{projectId}/reports - Get monitoring reports
```

### 7. Concevoir les Background Jobs

```yaml
# Queue: default (high priority)
Jobs:
  - CheckBacklinkStatus
    Description: Vérifier si un backlink existe toujours
    Frequency: Hourly per project
    Timeout: 30 seconds
    Retry: 3 times

  - DiscoverBacklinks
    Description: Découvrir nouveaux backlinks pour un projet
    Frequency: Daily per project
    Timeout: 5 minutes
    Retry: 2 times

# Queue: notifications (low priority)
Jobs:
  - SendBrokenLinkAlert
    Description: Notifier l'utilisateur d'un lien mort
    Frequency: On-demand
    Timeout: 10 seconds
    Retry: 5 times
```

### 8. Définir la Sécurité

```markdown
## Security Architecture

### Authentication
- **Method:** Laravel Sanctum (SPA + API tokens)
- **Token Storage:** httpOnly cookies (SPA), Bearer tokens (API)
- **Token Expiry:** 24 hours
- **Refresh:** Automatic via middleware

### Authorization
- **Method:** Laravel Policies
- **Rules:**
  - Users can only access their own projects
  - Users can only manage backlinks in their projects
  - Admin users have full access

### Input Validation
- **All inputs validated** via Form Requests
- **URL validation** with custom rules (no private IPs)
- **Rate limiting:** 100 req/min per user, 10 req/min for sensitive endpoints

### SSRF Protection
```php
// Allowed domains for backlink checking
$allowedDomains = ['*.com', '*.org', '*.net'];
$blockedIPs = ['127.0.0.1', '10.0.0.0/8', '192.168.0.0/16'];

// Validate before HTTP request
if (isPrivateIP($url) || !isAllowedDomain($url)) {
    throw new InvalidUrlException();
}
```

### Headers
```
X-Frame-Options: DENY
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
```
```

### 9. Planifier la Scalabilité

```markdown
## Scalability Strategy

### Phase 1: Single Server (0-1k users)
- Single Docker host
- PostgreSQL on same server
- Redis on same server
- Vertical scaling only

### Phase 2: Horizontal Scaling (1k-10k users)
- Load balancer (Nginx)
- Multiple app servers (Docker Swarm/K8s)
- Dedicated DB server (managed PostgreSQL)
- Dedicated Redis server
- CDN for static assets

### Phase 3: Distributed (10k+ users)
- Database replication (read replicas)
- Redis Cluster
- Separate queue workers
- Microservices for heavy workloads (backlink discovery)
- Object storage (S3) for files

### Caching Strategy
- **Application cache (Redis):** User sessions, frequently accessed data
- **Database query cache:** Enabled for read-heavy queries
- **HTTP cache:** API responses cached for 5 minutes (where appropriate)
- **CDN cache:** Static assets cached for 1 year
```

### 10. Sauvegarder l'Architecture

```
Per helpers.md#Save-Output-Document, workflow_name="architecture"
Per helpers.md#Update-Workflow-Status, workflow_name="architecture", status="completed"
```

## Architecture Document Template

```markdown
# System Architecture Document
## {Project Name}

**Version:** 1.0
**Date:** {date}
**Author:** Claude Code
**Status:** Draft / Review / Approved

---

## Executive Summary

{High-level overview of the architecture}

---

## Architecture Goals

### Primary Goals
1. {Goal 1 - e.g., "Scalable to 100k users"}
2. {Goal 2 - e.g., "Maintainable by small team"}

### Non-Goals
1. {What we're NOT optimizing for}

---

## Architecture Pattern

**Pattern:** {Monolith / Modular Monolith / Microservices}

**Rationale:** {Why this pattern was chosen}

---

## System Overview

### High-Level Diagram
```
[Diagram or ASCII art]
```

### Components
- **Frontend:** {Description}
- **API Layer:** {Description}
- **Application Layer:** {Description}
- **Data Layer:** {Description}

---

## Technology Stack

### Backend
{Stack with versions and rationale}

### Frontend
{Stack with versions and rationale}

### Infrastructure
{Hosting, CI/CD, monitoring}

### Third-Party Services
{External services and integrations}

---

## Data Architecture

### Database Schema
{Schema diagram or DDL}

### Data Flow
{How data moves through the system}

### Data Storage Strategy
{Where different types of data are stored}

---

## API Design

### RESTful Endpoints
{List of endpoints with method, path, description}

### Authentication
{How API auth works}

### Rate Limiting
{Rate limiting rules}

---

## Security Architecture

### Authentication & Authorization
{Detailed security implementation}

### Data Protection
{Encryption, PII handling}

### Attack Mitigation
{CSRF, XSS, SQL Injection, SSRF protection}

---

## Scalability & Performance

### Current Capacity
{What the system can handle now}

### Scaling Strategy
{How to scale when needed}

### Performance Targets
{Response time, throughput targets}

---

## Deployment Architecture

### Development Environment
{Local setup with Docker Compose}

### Staging Environment
{Staging setup}

### Production Environment
{Production infrastructure}

### CI/CD Pipeline
{Deployment workflow}

---

## Monitoring & Observability

### Logging
{What we log and where}

### Metrics
{Key metrics to track}

### Alerting
{Alert rules and thresholds}

---

## Disaster Recovery

### Backup Strategy
{How and when backups are taken}

### Recovery Process
{How to restore from backup}

### RTO/RPO
{Recovery time/point objectives}

---

## Technical Decisions (ADRs)

### ADR-001: Use PostgreSQL instead of MySQL
**Date:** {date}
**Status:** Accepted
**Context:** {Why this decision was needed}
**Decision:** {What was decided}
**Consequences:** {Positive and negative outcomes}

---

## Open Questions & Risks

### Open Questions
- [ ] {Question 1}

### Risks
| Risk | Impact | Mitigation |
|------|--------|------------|
| {Risk} | {High/Med/Low} | {Strategy} |

---

## Appendix

### Glossary
{Technical terms and definitions}

### References
{Links to documentation, articles, etc.}
```

## Best Practices

1. **Start simple** - Don't over-engineer for scale you don't have yet
2. **Document decisions** - Use ADRs (Architecture Decision Records)
3. **Consider tradeoffs** - Every choice has pros and cons
4. **Think long-term** - But build for today
5. **Validate assumptions** - Prototype risky parts early

## Common Pitfalls

❌ **Avoid:**
- Premature optimization
- Following trends blindly
- Ignoring non-functional requirements
- Not considering operational complexity
- Forgetting about monitoring/logging

✅ **Do:**
- Choose proven technologies
- Plan for observability from day 1
- Consider developer experience
- Think about maintenance burden
- Document key decisions

## Usage Example

```bash
# Lancer le workflow architecture
claude --workflow bmad/workflows/architecture.md

# Avec PRD existant
claude --workflow bmad/workflows/architecture.md --prd docs/prd-link-tracker-2026-02-09.md
```

## Next Steps After Architecture

1. **Review** - Valider l'architecture avec l'équipe
2. **Prototype** - Tester les parties risquées
3. **Sprint Planning** - Découper en stories techniques

## Related Workflows

- `workflows/prd.md` - Product Requirements (before architecture)
- `workflows/sprint-planning.md` - Implementation planning (after architecture)

## Related Agents

- `agents/tech-lead.md` - Technical leadership guidance
