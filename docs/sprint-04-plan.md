# Sprint Plan — Sprint 4 : LinkTracker

**Date :** 2026-02-18
**Sprint :** 4 (18/02/2026 → 03/03/2026)
**Scrum Master :** BMAD Agent
**Niveau projet :** 4
**Stories :** 14
**Total points :** 43
**Capacité sprint :** 44 points
**Objectif :** Finaliser la Marketplace, renforcer la sécurité et la performance, corriger les bugs existants et soigner l'UX de navigation

---

## Résumé exécutif

Le Sprint 4 consolide LinkTracker en une application production-ready. Il complète la boucle Marketplace (Order → Backlink automatique), optimise les performances (indexes DB, queue monitoring, caching), renforce la sécurité (audit SSRF, CSRF, rate limiting), intègre les Orders dans la navigation et corrige les échecs de tests pré-existants.

**Métriques clés :**
- Stories : 14
- Points : 43
- Vélocité cible : 44 pts/sprint (basée sur moyenne sprints 1-3 : 40 pts)
- Fin prévue : 03/03/2026

---

## Inventaire des stories

---

### STORY-036 : Liaison automatique Order → Backlink lors publication

**Epic :** EPIC-006 — Marketplace de Backlinks
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux qu'un backlink soit automatiquement créé dans le monitoring quand je marque une commande comme "publiée",
Afin de ne pas avoir à le saisir manuellement.

**Critères d'acceptation :**
- [ ] Quand `order.status` passe à `published`, un `Backlink` est créé automatiquement
- [ ] Les champs `source_url`, `target_url`, `anchor_text`, `tier_level`, `spot_type`, `price`, `currency`, `platform_id` sont copiés
- [ ] `Order.backlink_id` est mis à jour avec l'ID du backlink créé
- [ ] Si un backlink existe déjà (même source_url + project), il est lié sans doublon
- [ ] L'utilisateur voit un message de confirmation sur la page de la commande
- [ ] Tests Feature couvrant la création automatique et le cas doublon

**Notes techniques :**
- Observer `Order` (`OrderObserver`) ou hook dans `OrderController@updateStatus`
- Utiliser `Backlink::firstOrCreate(['project_id' => ..., 'source_url' => ...])`
- Mettre à jour `Order::$backlink_id` après création

**Dépendances :** STORY-032 ✅, STORY-033 ✅

---

### STORY-037 : Statut de commande avec historique (timeline)

**Epic :** EPIC-006 — Marketplace de Backlinks
**Priorité :** Should Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir l'historique des changements de statut d'une commande,
Afin de suivre l'avancement de mon achat.

**Critères d'acceptation :**
- [ ] Migration `order_status_logs` (order_id, old_status, new_status, changed_at, notes)
- [ ] Chaque changement de statut enregistre une entrée dans `order_status_logs`
- [ ] La page `orders/show` affiche une timeline des changements
- [ ] Tests unitaires du log de statut

**Notes techniques :**
- Model `OrderStatusLog` avec relation `Order->statusLogs()`
- Timeline Blade simple avec icônes et dates

**Dépendances :** STORY-033 ✅

---

### STORY-038 : Lien "Commandes" dans la sidebar navigation

**Epic :** EPIC-011 — Interface UI Moderne
**Priorité :** Must Have
**Points :** 2

**User Story :**
En tant qu'utilisateur,
Je veux accéder aux commandes depuis le menu de navigation,
Afin de naviguer rapidement vers la marketplace.

**Critères d'acceptation :**
- [ ] Lien "Commandes" dans la sidebar avec icône panier
- [ ] Badge avec le nombre de commandes `pending` si > 0
- [ ] Route active highlighted correctement
- [ ] Lien "Import CSV" dans la section Backlinks de la sidebar

**Notes techniques :**
- Modifier `resources/views/layouts/app.blade.php` (sidebar nav)
- Badge dynamique via `Order::where('status', 'pending')->count()`

**Dépendances :** STORY-033 ✅

---

### STORY-039 : Rapport PDF / HTML exportable

**Epic :** EPIC-007 — Dashboard et Reporting
**Priorité :** Should Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux exporter un rapport HTML de mes backlinks par projet,
Afin de le partager avec un client ou le conserver en archive.

**Critères d'acceptation :**
- [ ] Route `GET /projects/{project}/report` retourne une vue HTML imprimable
- [ ] Le rapport inclut : nom du projet, stats (actifs/perdus/modifiés), liste des backlinks avec statut et DA
- [ ] Bouton "Exporter rapport" sur la page projet (`projects/show`)
- [ ] Styles CSS dédiés impression (`@media print`)
- [ ] Tests Feature pour la génération du rapport

**Notes techniques :**
- Vue Blade `pages/projects/report.blade.php` avec layout minimaliste
- Controller `ProjectController@report`
- Pas de dépendance externe (pas de wkhtmltopdf)

**Dépendances :** STORY-023 ✅ (DomainMetric)

---

### STORY-040 : Indexes DB pour colonnes filtrées fréquemment

**Epic :** EPIC-009 — Scalabilité et Performance
**Priorité :** Must Have
**Points :** 2

**User Story :**
En tant que système,
Je veux que les requêtes fréquentes soient optimisées avec des indexes DB,
Afin de maintenir des temps de réponse < 200ms avec 10 000+ backlinks.

**Critères d'acceptation :**
- [ ] Migration ajoutant index sur `backlinks(status)`, `backlinks(project_id, status)`, `backlinks(last_checked_at)`
- [ ] Index sur `alerts(backlink_id, is_read)`, `alerts(created_at)`
- [ ] Index sur `domain_metrics(last_updated_at)`, `domain_metrics(domain)`
- [ ] Index sur `orders(project_id, status)`
- [ ] Test vérifiant que les indexes existent en base

**Notes techniques :**
- Une seule migration `add_performance_indexes`
- Utiliser `$table->index([...])` et `$table->unique([...])`

**Dépendances :** STORY-023 ✅, STORY-032 ✅

---

### STORY-041 : Caching des stats dashboard avec invalidation

**Epic :** EPIC-009 — Scalabilité et Performance
**Priorité :** Should Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux que le dashboard se charge rapidement même avec de nombreux backlinks,
Afin d'avoir une expérience fluide.

**Critères d'acceptation :**
- [ ] Les stats du dashboard (activeBacklinks, lostBacklinks, etc.) sont mises en cache 5 minutes
- [ ] Le cache est invalidé quand un backlink est créé/modifié/supprimé (Observer ou Event)
- [ ] Les projets et alertes récents restent sans cache (données fraîches)
- [ ] Tests vérifiant le hit/miss de cache

**Notes techniques :**
- `Cache::remember('dashboard_stats', 300, fn() => [...])`
- `BacklinkObserver` dispatch `DashboardCacheInvalidated` event
- Ou simplement `Cache::forget('dashboard_stats')` dans BacklinkController

**Dépendances :** Aucune

---

### STORY-042 : Queue monitoring — commande Artisan status

**Epic :** EPIC-009 — Scalabilité et Performance
**Priorité :** Should Have
**Points :** 2

**User Story :**
En tant qu'administrateur,
Je veux voir l'état des queues et jobs en cours via une commande Artisan,
Afin de diagnostiquer les problèmes de monitoring.

**Critères d'acceptation :**
- [ ] Commande `app:queue-status` affiche : jobs en attente, jobs échoués, dernier job exécuté
- [ ] Option `--failed` liste les jobs échoués avec message d'erreur
- [ ] Option `--reset-failed` remet en queue les jobs échoués
- [ ] Tests de la commande

**Notes techniques :**
- Utiliser `DB::table('jobs')` et `DB::table('failed_jobs')`
- Affichage avec `$this->table()`

**Dépendances :** Aucune

---

### STORY-043 : Audit sécurité — validation URL et protection SSRF

**Epic :** EPIC-010 — Sécurité et Robustesse
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'administrateur,
Je veux que toutes les URLs soumises par l'utilisateur soient validées contre les attaques SSRF,
Afin d'empêcher les requêtes vers des ressources internes.

**Critères d'acceptation :**
- [ ] `UrlValidator` refuse les IPs privées (10.x, 192.168.x, 127.x, 169.254.x)
- [ ] `UrlValidator` refuse les schémas non-HTTP/HTTPS (file://, ftp://, etc.)
- [ ] Validation appliquée sur : `source_url`, `target_url` dans BacklinkController et OrderController
- [ ] Validation appliquée sur webhook URL dans WebhookSettingsController
- [ ] Tests unitaires exhaustifs du UrlValidator
- [ ] Tests Feature vérifiant que les URLs invalides sont rejetées

**Notes techniques :**
- Vérifier si `UrlValidator` existe déjà, sinon créer dans `app/Services/`
- `filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)`

**Dépendances :** Aucune

---

### STORY-044 : Rate limiting avancé par IP et utilisateur

**Epic :** EPIC-010 — Sécurité et Robustesse
**Priorité :** Should Have
**Points :** 3

**User Story :**
En tant qu'administrateur,
Je veux des limites de taux par IP et par utilisateur sur les routes sensibles,
Afin de prévenir les abus et protéger les APIs externes.

**Critères d'acceptation :**
- [ ] Rate limiting par IP : 60 req/min sur les routes publiques
- [ ] Rate limiting par utilisateur : 10 req/min sur `/backlinks/{id}/check`
- [ ] Rate limiting par utilisateur : 5 req/min sur `/backlinks/import`
- [ ] Réponse 429 avec header `Retry-After` correct
- [ ] Tests Feature vérifiant les limites

**Notes techniques :**
- Utiliser `RateLimiter::for()` dans `RouteServiceProvider` ou dans les routes
- `throttle:60,1` déjà en place, affiner avec `throttle:10,1,user`

**Dépendances :** Aucune

---

### STORY-045 : Fix tests BacklinkControllerTest pré-existants

**Epic :** EPIC-012 — Testing et Qualité
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant que développeur,
Je veux que tous les tests existants passent,
Afin de maintenir la confiance dans la suite de tests.

**Critères d'acceptation :**
- [ ] `can create backlink with all extended fields` → passe (price, currency stockés correctement)
- [ ] `tier2 requires parent backlink id` → passe (validation custom)
- [ ] `price requires currency` → passe (validation bidirectionnelle)
- [ ] `checkboxes handle unchecked state` → passe (is_dofollow, invoice_paid)
- [ ] `tier level defaults to tier1` → passe (valeur par défaut modèle ou migration)
- [ ] `spot type defaults to external` → passe
- [ ] `invoice paid defaults to false` → passe
- [ ] `platform deletion sets backlinks platform to null` → passe (cascade)

**Notes techniques :**
- Analyser chaque test en échec et corriger la cause racine dans les controllers/modèles/migrations
- Ne pas modifier les tests, corriger le code applicatif

**Dépendances :** Aucune

---

### STORY-046 : Page profil utilisateur (changement mot de passe)

**Epic :** EPIC-008 — Infrastructure et Configuration
**Priorité :** Should Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux pouvoir changer mon mot de passe depuis mon profil,
Afin de maintenir la sécurité de mon compte.

**Critères d'acceptation :**
- [ ] Page `GET /profile` avec formulaire de changement de mot de passe
- [ ] Validation : mot de passe actuel correct, nouveau mot de passe ≥ 8 caractères, confirmation
- [ ] Message de succès après changement
- [ ] Lien vers le profil dans la sidebar / menu utilisateur
- [ ] Tests Feature

**Notes techniques :**
- `ProfileController` avec méthodes `show`, `updatePassword`
- Hash via `Hash::make()`, vérification via `Hash::check()`

**Dépendances :** STORY-002 ✅ (Authentification)

---

### STORY-047 : Pagination et tri sur la page Orders

**Epic :** EPIC-011 — Interface UI Moderne
**Priorité :** Should Have
**Points :** 2

**User Story :**
En tant qu'utilisateur,
Je veux pouvoir trier et paginer mes commandes,
Afin de retrouver facilement une commande spécifique.

**Critères d'acceptation :**
- [ ] Tri par date de commande, statut, prix (asc/desc)
- [ ] Pagination 20 items/page avec liens
- [ ] Tri conservé dans l'URL pour navigation
- [ ] Tests Feature

**Notes techniques :**
- Même pattern que BacklinkController (sort/direction dans URL)
- `SortableHeader` component déjà disponible

**Dépendances :** STORY-033 ✅

---

### STORY-048 : Amélioration UX — messages flash et confirmations

**Epic :** EPIC-011 — Interface UI Moderne
**Priorité :** Should Have
**Points :** 2

**User Story :**
En tant qu'utilisateur,
Je veux voir des messages de confirmation clairs après chaque action,
Afin de savoir que mon action a été prise en compte.

**Critères d'acceptation :**
- [ ] Messages flash auto-dismissibles après 4 secondes (AlpineJS)
- [ ] Confirmations de suppression avec modale (plutôt que `confirm()` natif)
- [ ] Message de succès animé en haut de page
- [ ] Composant Blade `x-flash-message` réutilisable

**Notes techniques :**
- `x-data="{ show: true }"` + `x-init="setTimeout(() => show = false, 4000)"` + `x-show="show"` + `x-transition`
- Créer composant `resources/views/components/flash-message.blade.php`

**Dépendances :** Aucune

---

### STORY-049 : Documentation technique API interne

**Epic :** EPIC-012 — Testing et Qualité
**Priorité :** Could Have
**Points :** 3

**User Story :**
En tant que développeur,
Je veux une documentation des endpoints et services principaux,
Afin de faciliter la maintenance et les évolutions futures.

**Critères d'acceptation :**
- [ ] `docs/API.md` documentant les routes web principales (dashboard, backlinks, orders, settings)
- [ ] `docs/SERVICES.md` documentant les services (BacklinkCheckerService, SeoMetricService, AlertService)
- [ ] Exemples de requêtes/réponses pour les endpoints JSON (chart, seo-metrics refresh)
- [ ] CLAUDE.md mis à jour avec les nouvelles fonctionnalités Sprint 3-4

**Notes techniques :**
- Documentation Markdown simple, pas de Swagger nécessaire
- Mettre à jour la section "EPICs complétés" de CLAUDE.md

**Dépendances :** Aucune

---

## Allocation Sprint 4

### Sprint 4 (18/02/2026 → 03/03/2026) — 43/44 points

**Objectif :** Production-ready : Marketplace complète, performance, sécurité, qualité

| Story | Titre | Points | Priorité | Epic |
|-------|-------|--------|----------|------|
| STORY-036 | Order → Backlink automatique | 5 | Must Have | EPIC-006 |
| STORY-037 | Historique statut commande (timeline) | 3 | Should Have | EPIC-006 |
| STORY-038 | Sidebar navigation Orders + Import | 2 | Must Have | EPIC-011 |
| STORY-039 | Rapport HTML exportable par projet | 5 | Should Have | EPIC-007 |
| STORY-040 | Indexes DB performance | 2 | Must Have | EPIC-009 |
| STORY-041 | Caching stats dashboard | 3 | Should Have | EPIC-009 |
| STORY-042 | Commande Artisan queue-status | 2 | Should Have | EPIC-009 |
| STORY-043 | Audit SSRF + validation URL | 3 | Must Have | EPIC-010 |
| STORY-044 | Rate limiting avancé | 3 | Should Have | EPIC-010 |
| STORY-045 | Fix tests pré-existants | 5 | Must Have | EPIC-012 |
| STORY-046 | Page profil / changement mot de passe | 3 | Should Have | EPIC-008 |
| STORY-047 | Pagination + tri Orders | 2 | Should Have | EPIC-011 |
| STORY-048 | UX — flash messages auto-dismiss | 2 | Should Have | EPIC-011 |
| STORY-049 | Documentation technique | 3 | Could Have | EPIC-012 |

**Total : 43 / 44 points (98% utilisation)**

---

## Traçabilité Epic → Stories

| Epic | Stories Sprint 4 | Points |
|------|-----------------|--------|
| EPIC-006 Marketplace | STORY-036, 037 | 8 pts |
| EPIC-007 Reporting | STORY-039 | 5 pts |
| EPIC-008 Auth/Config | STORY-046 | 3 pts |
| EPIC-009 Performance | STORY-040, 041, 042 | 7 pts |
| EPIC-010 Sécurité | STORY-043, 044 | 6 pts |
| EPIC-011 UI/UX | STORY-038, 047, 048 | 6 pts |
| EPIC-012 Qualité | STORY-045, 049 | 8 pts |

---

## Risques

**Moyen :**
- STORY-045 (fix tests) : la cause racine peut être dans les migrations SQLite vs comportements → analyser avant de coder
- STORY-039 (rapport HTML) : alignement CSS print potentiellement complexe → simplifier si nécessaire

**Faible :**
- STORY-036 (auto-create backlink) : éviter double création si status passe plusieurs fois à "published"
- STORY-040 (indexes) : vérifier compatibilité SQLite (indexes uniques) avant d'ajouter

---

## Définition de Terminé

- [ ] Code implémenté et committé sur `master`
- [ ] Tests écrits et passants (Feature + Unit selon contexte)
- [ ] Aucune régression sur les tests existants
- [ ] Routes accessibles et fonctionnelles
- [ ] Code review (auto-review via CLAUDE.md standards)

---

**Plan créé avec BMAD Method v6 - Phase 4 (Sprint Planning)**
