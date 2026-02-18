# Sprint Plan — Sprint 2 : LinkTracker

**Date :** 2026-02-17
**Sprint :** 2 (17/02/2026 → 03/03/2026)
**Scrum Master :** BMAD Agent
**Niveau projet :** 4
**Stories :** 13
**Total points :** 50
**Capacité sprint :** 50 points (rythme intensif)
**Frontend :** Blade + AlpineJS (code existant réutilisé)

---

## Résumé exécutif

Le Sprint 2 construit les fonctionnalités cœur de LinkTracker sur les fondations du Sprint 1.
Objectif : avoir un produit fonctionnel de bout en bout — ajout de backlinks, vérification automatique, détection d'anomalies, alertes en temps réel et dashboard de pilotage.

**Métriques clés :**
- Stories : 13
- Points : 50
- Sprints planifiés : Sprint 2
- Capacité : 50 points/sprint
- Fin prévue : 03/03/2026

---

## Inventaire des stories

---

### STORY-007 : Backlinks List Page (Blade)

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir la liste de tous mes backlinks par projet,
Afin de surveiller leur état global en un coup d'œil.

**Critères d'acceptation :**
- [ ] Tableau paginé (20 items/page) avec colonnes : URL source, URL cible, ancre, statut, dernière vérification
- [ ] Filtres : par projet, par statut (active/lost/changed), par tier_level
- [ ] Badge coloré pour le statut (vert/rouge/orange)
- [ ] Liens vers la page détail du backlink
- [ ] Bouton "Ajouter un backlink"
- [ ] Responsive mobile

**Notes techniques :**
- BacklinkController@index déjà existant, adapter la vue Blade
- Utiliser AlpineJS pour les filtres dynamiques (sans rechargement)
- Eager loading avec `with(['project', 'checks' => fn($q) => $q->latest()->limit(1)])`
- Route : GET /backlinks

**Dépendances :** STORY-006 (migration backlinks ✅ complété)

---

### STORY-009 : Backlink Create/Edit Form (Blade)

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux créer et modifier des backlinks,
Afin de gérer mon portefeuille de liens entrants.

**Critères d'acceptation :**
- [ ] Formulaire : source_url, target_url, anchor_text, projet (select), tier_level, spot_type, prix (optionnel), plateforme (optionnel)
- [ ] Validation côté serveur avec messages d'erreur inline
- [ ] Validation SSRF sur source_url et target_url via UrlValidator (STORY-008 ✅)
- [ ] Mode création et mode édition (même composant Blade)
- [ ] Redirection vers la liste avec message de succès
- [ ] Annuler sans sauvegarder

**Notes techniques :**
- Réutiliser BacklinkController@store et @update
- Formulaire Blade avec AlpineJS pour afficher/masquer champs optionnels
- StoreBacklinkRequest et UpdateBacklinkRequest avec validation

**Dépendances :** STORY-007, STORY-008 ✅

---

### STORY-010 : Backlink Delete + Status Badges

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Must Have
**Points :** 2

**User Story :**
En tant qu'utilisateur,
Je veux supprimer un backlink et visualiser clairement son statut,
Afin de maintenir un portefeuille propre et lisible.

**Critères d'acceptation :**
- [ ] Bouton supprimer avec confirmation AlpineJS (modal)
- [ ] Delete définitif avec cascade sur BacklinkChecks et Alerts
- [ ] Badges de statut cohérents sur toutes les pages : vert (active), rouge (lost), orange (changed)
- [ ] Statut mis à jour visuellement sans rechargement

**Notes techniques :**
- BacklinkController@destroy
- Utiliser AlpineJS `x-show` / `x-on:click` pour la confirmation
- Composant Blade `<x-status-badge>` réutilisable

**Dépendances :** STORY-007

---

### STORY-011 : Backlink Detail Page + Historique

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir la page détail d'un backlink avec son historique de vérifications,
Afin de diagnostiquer les problèmes de disponibilité.

**Critères d'acceptation :**
- [ ] Infos complètes du backlink (URLs, ancre, attributs rel, tier, prix)
- [ ] Taux de disponibilité calculé (% de checks is_present=true)
- [ ] Historique des 30 dernières vérifications (tableau : date, statut HTTP, présent, erreur)
- [ ] Timeline visuelle de disponibilité (barres colorées)
- [ ] Bouton "Vérifier maintenant" (STORY-014)
- [ ] Alertes liées au backlink

**Notes techniques :**
- BacklinkController@show
- Calcul taux : `$backlink->checks()->where('is_present', true)->count() / $backlink->checks()->count() * 100`
- Limiter l'historique aux 30 derniers pour les performances

**Dépendances :** STORY-007, STORY-013

---

### STORY-012 : BacklinkCheckerService (adapter existant)

**Epic :** EPIC-003 — Monitoring automatique
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant que système,
Je veux vérifier la présence d'un backlink sur une page source,
Afin de détecter les pertes ou changements d'attributs.

**Critères d'acceptation :**
- [ ] Requête HTTP vers source_url (timeout 30s, User-Agent réaliste)
- [ ] Parse HTML avec DOMDocument/DOMXPath pour trouver le lien vers target_url
- [ ] Extraction : ancre actuelle, rel attributes (nofollow, sponsored, ugc), HTTP status
- [ ] Détection de changements : ancre modifiée, passage en nofollow, perte du lien
- [ ] Retour structuré : CheckResult (is_present, http_status, anchor_text, rel_attrs, error)
- [ ] Gestion d'erreurs : timeout, DNS fail, 4xx/5xx
- [ ] Protection SSRF via UrlValidator (STORY-008 ✅)
- [ ] Tests unitaires complets (mock HTTP)

**Notes techniques :**
- Service existant dans `app/Services/BacklinkCheckerService.php` — auditer et compléter
- Utiliser `Http::withUserAgent()->timeout(30)->get($url)`
- DOMXPath pour parser `//a[@href]`

**Dépendances :** STORY-008 ✅

---

### STORY-013 : CheckBacklinkJob + Queue Dispatch

**Epic :** EPIC-003 — Monitoring automatique
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant que système,
Je veux dispatcher des jobs de vérification en queue,
Afin de vérifier les backlinks de manière asynchrone et fiable.

**Critères d'acceptation :**
- [ ] Job `CheckBacklinkJob` avec `implements ShouldQueue`
- [ ] Appel à BacklinkCheckerService dans le handle()
- [ ] Création d'un BacklinkCheck après chaque vérification
- [ ] Mise à jour du statut du Backlink (active/lost/changed)
- [ ] Création d'alertes via AlertService si changement détecté
- [ ] Retry : 3 tentatives, backoff exponentiel, timeout 120s
- [ ] Log des erreurs avec contexte (backlink_id, url, error)
- [ ] Tests feature du job (mock service)

**Notes techniques :**
- Job existant dans `app/Jobs/CheckBacklinkJob.php` — adapter si nécessaire
- `$this->tries = 3; $this->timeout = 120;`
- Dispatcher en queue 'default'

**Dépendances :** STORY-012, STORY-016 ✅

---

### STORY-014 : Vérification manuelle (bouton + feedback)

**Epic :** EPIC-003 — Monitoring automatique
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux déclencher manuellement la vérification d'un backlink,
Afin de tester immédiatement sa disponibilité sans attendre le cron.

**Critères d'acceptation :**
- [ ] Bouton "Vérifier maintenant" sur la page détail et la liste
- [ ] Rate limiting : 5 vérifications manuelles/minute
- [ ] Dispatch du CheckBacklinkJob avec feedback immédiat
- [ ] Indicateur de chargement AlpineJS pendant la vérification
- [ ] Message de succès/erreur après dispatch
- [ ] En mode sync (dev) : afficher résultat immédiat

**Notes techniques :**
- Route POST : `/backlinks/{id}/check`
- BacklinkController@check déjà défini dans routes
- Réponse JSON pour AlpineJS : `{ dispatched: true, message: '...' }`

**Dépendances :** STORY-011, STORY-013

---

### STORY-015 : Scheduler cron quotidien + commande Artisan

**Epic :** EPIC-003 — Monitoring automatique
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant que système,
Je veux vérifier automatiquement tous les backlinks selon une planification,
Afin de détecter les problèmes sans intervention manuelle.

**Critères d'acceptation :**
- [ ] Commande `app:check-backlinks` fonctionnelle avec options `--frequency` et `--project`
- [ ] Scheduler : quotidien à 2h pour backlinks non vérifiés depuis 24h
- [ ] Scheduler : hebdomadaire dimanche à 3h pour backlinks non vérifiés depuis 7j
- [ ] Dispatch en batch avec limite configurable (--limit=50 par défaut)
- [ ] Log de démarrage et fin avec count de backlinks traités
- [ ] Test de la commande Artisan

**Notes techniques :**
- Commande existante dans `app/Console/Commands/` — vérifier et compléter
- `$schedule->command('app:check-backlinks')->dailyAt('02:00')`
- Filtre : `where('last_checked_at', '<', now()->subDay())`

**Dépendances :** STORY-013

---

### STORY-017 : AlertService (adapter existant)

**Epic :** EPIC-004 — Alertes et Notifications
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant que système,
Je veux créer des alertes intelligentes lors de changements détectés,
Afin de notifier l'utilisateur des problèmes critiques.

**Critères d'acceptation :**
- [ ] `createBacklinkLostAlert()` — sévérité selon tier (tier1=critical, tier2=high)
- [ ] `createBacklinkChangedAlert()` — sévérité selon type (nofollow=high, ancre=medium)
- [ ] `createBacklinkRecoveredAlert()` — sévérité low, fermer les alertes précédentes
- [ ] Déduplication : pas de nouvelle alerte si alerte non lue du même type existe
- [ ] Attributs : type, severity, backlink_id, title, message, is_read, read_at
- [ ] Tests unitaires du service

**Notes techniques :**
- Service existant dans `app/Services/AlertService.php` — compléter
- Déduplication : `Alert::where('backlink_id')->where('type')->where('is_read', false)->exists()`

**Dépendances :** STORY-013

---

### STORY-018 : Centre de notifications in-app (Blade/Alpine)

**Epic :** EPIC-004 — Alertes et Notifications
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux voir mes alertes dans l'application et les marquer comme lues,
Afin de gérer mes notifications sans quitter l'interface.

**Critères d'acceptation :**
- [ ] Page `/alerts` avec liste paginée (date, type, sévérité, backlink, statut)
- [ ] Compteur de non-lues dans la navbar (badge rouge)
- [ ] Marquer comme lu : bouton unitaire et "Tout marquer comme lu"
- [ ] Filtre par sévérité et par statut (lues/non-lues)
- [ ] Badge de sévérité coloré : critique=rouge, high=orange, medium=jaune, low=bleu
- [ ] Lien vers le backlink concerné

**Notes techniques :**
- AlertController@index déjà défini
- Compteur navbar via accessor ou scope sur User
- AlpineJS pour marquer comme lu via fetch API
- Routes : PATCH `/alerts/{id}/read` et POST `/alerts/read-all`

**Dépendances :** STORY-017

---

### STORY-019 : Webhook configurable (URL + secret)

**Epic :** EPIC-004 — Alertes et Notifications
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux configurer un webhook pour recevoir les alertes dans Slack ou un outil externe,
Afin d'être notifié en temps réel sans surveiller l'application.

**Critères d'acceptation :**
- [ ] Page de configuration webhook : URL, secret HMAC, types d'alertes à envoyer
- [ ] Envoi HTTP POST lors de chaque nouvelle alerte (en queue)
- [ ] Payload JSON : `{ event, alert: { type, severity, backlink_url, message }, timestamp, signature }`
- [ ] Signature HMAC-SHA256 dans header `X-Webhook-Signature`
- [ ] Retry 3 fois si échec (timeout 10s)
- [ ] Log des envois et erreurs
- [ ] Bouton "Tester le webhook"

**Notes techniques :**
- Colonne JSON dans users : `webhook_url`, `webhook_secret`, `webhook_events`
- Job `SendWebhookJob` dispatché par AlertService après création alerte
- `Http::withHeaders(['X-Webhook-Signature' => $signature])->post($url, $payload)`

**Dépendances :** STORY-017

---

### STORY-020 : Dashboard métriques (stats globales)

**Epic :** EPIC-007 — Dashboard et Rapports
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux voir un dashboard avec les métriques clés de mon portefeuille de backlinks,
Afin de piloter ma stratégie SEO en un coup d'œil.

**Critères d'acceptation :**
- [ ] KPI cards : total backlinks, actifs, perdus, taux de disponibilité global
- [ ] Graphique disponibilité sur 30 jours
- [ ] Répartition par statut (barres ou donut)
- [ ] Top 5 backlinks récemment perdus
- [ ] Top 5 projets par nombre de backlinks
- [ ] Dernière vérification globale (timestamp)

**Notes techniques :**
- DashboardController@index existant — enrichir avec les métriques
- Cache les stats 5 minutes : `Cache::remember('dashboard_stats', 300, fn() => ...)`
- Chart.js pour les graphiques

**Dépendances :** STORY-007, STORY-013

---

### STORY-021 : Widget alertes récentes sur dashboard

**Epic :** EPIC-007 — Dashboard et Rapports
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir les dernières alertes directement sur le dashboard,
Afin d'identifier immédiatement les problèmes urgents.

**Critères d'acceptation :**
- [ ] Widget "Alertes récentes" : les 5 dernières alertes non lues
- [ ] Chaque alerte : icône sévérité, message court, backlink concerné, date relative
- [ ] Lien "Voir toutes les alertes" vers `/alerts`
- [ ] Si aucune alerte : message positif "Tout va bien"

**Notes techniques :**
- `Alert::with('backlink')->unread()->latest()->limit(5)->get()` dans DashboardController
- Composant Blade `<x-alert-widget>`

**Dépendances :** STORY-018, STORY-020

---

## Allocation du Sprint 2

### Sprint 2 (17/02 → 03/03/2026) — 50/50 points

**Objectif :** Livrer un produit fonctionnel de bout en bout : backlinks CRUD, monitoring automatique, alertes en temps réel et dashboard de pilotage.

#### Semaine 1 — Fondations backlinks + moteur de vérification (26 pts)

| Story | Titre | Points | Priorité |
|-------|-------|--------|---------|
| STORY-007 | Backlinks List Page | 3 | Must Have |
| STORY-009 | Backlink Create/Edit Form | 3 | Must Have |
| STORY-010 | Backlink Delete + Badges | 2 | Must Have |
| STORY-012 | BacklinkCheckerService | 5 | Must Have |
| STORY-013 | CheckBacklinkJob + Queue | 5 | Must Have |
| STORY-015 | Scheduler cron + Artisan | 3 | Must Have |
| STORY-017 | AlertService | 5 | Must Have |

#### Semaine 2 — Interface complète + dashboard (24 pts)

| Story | Titre | Points | Priorité |
|-------|-------|--------|---------|
| STORY-011 | Backlink Detail + Historique | 3 | Must Have |
| STORY-014 | Vérification manuelle | 3 | Must Have |
| STORY-018 | Centre de notifications | 5 | Must Have |
| STORY-019 | Webhook configurable | 5 | Must Have |
| STORY-020 | Dashboard métriques | 5 | Must Have |
| STORY-021 | Widget alertes dashboard | 3 | Must Have |

**Risques :**
- Parsing HTML variable selon les sites (mitigation : tests avec fixtures HTML)
- Fiabilité des envois webhook externes (mitigation : retry queue + logs)
- Volume de jobs en queue (mitigation : QUEUE_CONNECTION=sync en dev)

---

## Traçabilité Epic → Stories

| Epic | Nom | Stories | Points |
|------|-----|---------|--------|
| EPIC-002 | Gestion des Backlinks | STORY-007, 009, 010, 011 | 11 pts |
| EPIC-003 | Monitoring automatique | STORY-012, 013, 014, 015 | 16 pts |
| EPIC-004 | Alertes et Notifications | STORY-017, 018, 019 | 15 pts |
| EPIC-007 | Dashboard et Rapports | STORY-020, 021 | 8 pts |

---

## Couverture des exigences fonctionnelles

| FR | Description | Story |
|----|-------------|-------|
| FR-005 | Ajouter des backlinks | STORY-009 |
| FR-006 | Lister les backlinks par projet | STORY-007 |
| FR-007 | Modifier/supprimer un backlink | STORY-009, 010 |
| FR-008 | Historique des vérifications | STORY-011 |
| FR-011 | Vérification automatique quotidienne | STORY-015 |
| FR-012 | Vérification manuelle | STORY-014 |
| FR-013 | Détection présence + attributs lien | STORY-012 |
| FR-014 | Mise à jour statut backlink | STORY-013 |
| FR-015 | Taux de disponibilité | STORY-011 |
| FR-016 | Alerte backlink perdu | STORY-017 |
| FR-017 | Alerte attributs modifiés | STORY-017 |
| FR-018 | Alerte backlink récupéré | STORY-017 |
| FR-019 | Notifications webhook | STORY-019 |
| FR-028 | Dashboard métriques globales | STORY-020 |
| FR-029 | Alertes récentes sur dashboard | STORY-021 |

---

## Definition of Done

Pour qu'une story soit considérée complète :
- [ ] Code implémenté et commité
- [ ] Tests écrits et passants
- [ ] Validation des critères d'acceptation
- [ ] Pas de régressions sur les tests existants
- [ ] Code conforme aux conventions du projet (CLAUDE.md)

---

## Ordre d'implémentation recommandé

1. STORY-007 → STORY-009 → STORY-010 (CRUD backlinks — interface visible rapidement)
2. STORY-012 → STORY-013 → STORY-015 (moteur de vérification)
3. STORY-017 → STORY-018 → STORY-019 (alertes)
4. STORY-011 → STORY-014 (détail + vérification manuelle)
5. STORY-020 → STORY-021 (dashboard)

Lancer `/dev-story STORY-007` pour commencer l'implémentation.

---

**Plan généré avec BMAD Method v6 — Phase 4 (Implementation Planning) — 2026-02-17**
