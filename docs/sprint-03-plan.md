# Sprint Plan — Sprint 3 : LinkTracker

**Date :** 2026-02-18
**Sprint :** 3 (18/02/2026 → 03/03/2026)
**Scrum Master :** BMAD Agent
**Niveau projet :** 4
**Stories :** 14
**Total points :** 44
**Capacité sprint :** 44 points
**Objectif :** Enrichir l'application avec les métriques SEO, la configuration globale, les graphiques d'évolution et poser les fondations de la marketplace

---

## Résumé exécutif

Le Sprint 3 fait passer LinkTracker d'un outil de monitoring fonctionnel à une plateforme SEO enrichie. L'utilisateur peut maintenant voir la qualité des domaines sources (DA, DR, TF/CF), configurer l'application selon ses besoins, visualiser l'évolution de ses backlinks dans le temps, et passer ses premières commandes de backlinks via la marketplace.

**Métriques clés :**
- Stories : 14
- Points : 44
- Vélocité estimée : 44 pts/sprint (code nouveau, moins de réutilisation)
- Fin prévue : 03/03/2026

---

## Inventaire des stories

---

### STORY-022 : SeoMetricService — Abstraction multi-providers

**Epic :** EPIC-005 — Intégration des Métriques SEO
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant que système,
Je veux récupérer les métriques SEO d'un domaine depuis plusieurs providers,
Afin d'afficher la qualité des domaines sources de backlinks.

**Critères d'acceptation :**
- [ ] Interface `SeoMetricProvider` avec méthode `getMetrics(string $domain): DomainMetrics`
- [ ] `DomainMetrics` value object : da, dr, tf, cf, spam_score, backlinks_count, last_updated_at
- [ ] Provider `MozProvider` (DA, PA, Spam Score via API Moz v2)
- [ ] Provider `CustomProvider` (fallback sans API — données nulles avec message)
- [ ] `SeoMetricService` qui résout le provider actif via config
- [ ] Respect des rate limits (délai configurable entre appels)
- [ ] Gestion des erreurs API (timeout 15s, fallback gracieux)
- [ ] Tests unitaires avec mocks HTTP

**Notes techniques :**
- `app/Services/Seo/` : SeoMetricService, Providers/, DomainMetrics DTO
- Config : `config/seo.php` avec `provider`, `moz_api_key`, `rate_limit_ms`
- Utiliser `Http::fake()` dans les tests
- Migration `domain_metrics` table : domain, da, dr, tf, cf, spam_score, backlinks_count, last_updated_at

**Dépendances :** STORY-008 ✅ (UrlValidator)

---

### STORY-023 : Migration domain_metrics + Model

**Epic :** EPIC-005 — Intégration des Métriques SEO
**Priorité :** Must Have
**Points :** 2

**User Story :**
En tant que système,
Je veux stocker les métriques SEO des domaines en base de données,
Afin d'éviter des appels API répétés et de conserver l'historique.

**Critères d'acceptation :**
- [ ] Table `domain_metrics` : id, domain (unique), da, dr, tf, cf, spam_score, backlinks_count, provider, last_updated_at, timestamps
- [ ] Model `DomainMetric` avec fillable et scope `stale()` (> 24h)
- [ ] Méthode `DomainMetric::forDomain(string $domain)` — find or create
- [ ] Relation inverse : un Backlink `hasOne` DomainMetric via son domaine source
- [ ] Index sur `domain` et `last_updated_at`

**Notes techniques :**
- Extraire le domaine depuis source_url : `parse_url($url, PHP_URL_HOST)`
- Scope : `->where('last_updated_at', '<', now()->subDay())->orWhereNull('last_updated_at')`

**Dépendances :** STORY-022

---

### STORY-024 : FetchSeoMetricsJob + scheduler quotidien

**Epic :** EPIC-005 — Intégration des Métriques SEO
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant que système,
Je veux mettre à jour automatiquement les métriques SEO chaque jour,
Afin que les données soient toujours récentes sans intervention manuelle.

**Critères d'acceptation :**
- [ ] Job `FetchSeoMetricsJob` avec ShouldQueue, retry 2, timeout 60s
- [ ] Prend un `DomainMetric` en paramètre, appelle SeoMetricService
- [ ] Met à jour les colonnes et `last_updated_at`
- [ ] Commande Artisan `app:refresh-seo-metrics` : dispatch un job par domaine unique stale
- [ ] Scheduler : quotidien à 4h du matin
- [ ] Rate limiting entre jobs (1 req/s configurable)
- [ ] Log du résultat (domaine, metrics, provider utilisé)

**Notes techniques :**
- Domaines uniques : `Backlink::distinct()->pluck('source_url')` → extraire host
- Throttler : `dispatch($job)->delay(now()->addSeconds($i * $rateLimit))`

**Dépendances :** STORY-023

---

### STORY-025 : Affichage métriques SEO sur page détail backlink

**Epic :** EPIC-005 — Intégration des Métriques SEO
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir les métriques SEO du domaine source sur la page détail d'un backlink,
Afin d'évaluer rapidement la qualité de ce backlink.

**Critères d'acceptation :**
- [ ] Section "Métriques du domaine" sur `backlinks/show.blade.php`
- [ ] Affiche : DA, DR, TF, CF, Spam Score avec indicateurs visuels (vert/orange/rouge)
- [ ] Barre de progression visuelle pour DA et DR (0-100)
- [ ] Date de dernière mise à jour + badge provider
- [ ] Si métriques absentes : bouton "Récupérer les métriques" (déclenche job via AJAX)
- [ ] Si provider non configuré : message "Configurez votre API SEO dans les paramètres"

**Notes techniques :**
- Seuils visuels : DA/DR > 40 = vert, > 20 = orange, < 20 = rouge
- Spam Score < 5 = vert, < 15 = orange, ≥ 15 = rouge
- Route POST `/backlinks/{id}/seo-metrics` pour refresh manuel (rate limit 3/min)

**Dépendances :** STORY-023, STORY-011 ✅

---

### STORY-026 : Colonne métriques dans liste backlinks

**Epic :** EPIC-005 — Intégration des Métriques SEO
**Priorité :** Should Have
**Points :** 2

**User Story :**
En tant qu'utilisateur,
Je veux voir un aperçu des métriques SEO directement dans la liste de backlinks,
Afin de comparer rapidement la qualité de mes différents backlinks.

**Critères d'acceptation :**
- [ ] Colonne "DA/DR" dans la table backlinks (valeur ou "–" si absente)
- [ ] Colonne optionnelle (toggle affichage via préférence session)
- [ ] Tri possible sur DA
- [ ] Indicateur coloré selon seuil
- [ ] Eager loading `with('domainMetric')` pour éviter N+1

**Notes techniques :**
- Ajouter `domainMetric` au BacklinkController@index
- AlpineJS pour toggle colonne

**Dépendances :** STORY-025

---

### STORY-027 : Configuration globale — Page Settings

**Epic :** EPIC-008 — Infrastructure d'Auth et Configuration
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux accéder à une page de configuration centralisée,
Afin de paramétrer tous les aspects de l'application en un seul endroit.

**Critères d'acceptation :**
- [ ] Route `GET /settings` avec navigation par onglets (AlpineJS)
- [ ] Onglet "Monitoring" : fréquence vérification (daily/hourly/custom), timeout HTTP
- [ ] Onglet "APIs SEO" : sélection provider (Moz/Custom), API key, test connexion
- [ ] Onglet "Webhook" : lien vers `/settings/webhook` existant
- [ ] Onglet "Compte" : email, changement mot de passe
- [ ] Sauvegarde via `user_settings` JSON ou colonnes dédiées en DB
- [ ] Feedback de succès/erreur après sauvegarde

**Notes techniques :**
- SettingsController avec méthodes index, update
- Stocker settings dans `users` table (colonnes JSON `settings`) ou table `user_settings`
- Utiliser composant Blade `<x-settings-tab>` réutilisable

**Dépendances :** STORY-002 ✅ (Auth)

---

### STORY-028 : Configuration API SEO + test de connexion

**Epic :** EPIC-005 — Intégration des Métriques SEO
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux configurer mon API key SEO et tester la connexion,
Afin de m'assurer que les métriques seront bien récupérées.

**Critères d'acceptation :**
- [ ] Formulaire dans Settings > onglet "APIs SEO"
- [ ] Champ API key (masqué, avec toggle affichage)
- [ ] Sélecteur de provider (Moz / Custom/Gratuit)
- [ ] Bouton "Tester la connexion" → appel API réel + résultat immédiat
- [ ] Affichage des quotas restants si supporté par l'API
- [ ] Sauvegarde sécurisée (API key chiffrée ou via .env user-level)
- [ ] Message d'aide avec lien vers doc Moz si besoin

**Notes techniques :**
- Stocker api_key de façon sécurisée (colonne encrypted ou settings JSON user)
- Route POST `/settings/seo/test` → teste avec un domaine exemple (google.com)
- Utiliser `Crypt::encryptString()` pour l'API key

**Dépendances :** STORY-027

---

### STORY-029 : Graphique évolution backlinks sur dashboard (Chart.js)

**Epic :** EPIC-007 — Dashboard et Reporting
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux voir un graphique d'évolution de mes backlinks sur les 30 derniers jours,
Afin de visualiser les tendances et détecter les pertes en un coup d'œil.

**Critères d'acceptation :**
- [ ] Graphique ligne (Chart.js) : actifs (vert), perdus (rouge), modifiés (orange)
- [ ] Axe X : 30 derniers jours (ou 7j/90j selon filtre)
- [ ] Sélecteur de période : 7j, 30j, 90j
- [ ] Filtre par projet (tous ou projet spécifique)
- [ ] Données rechargées via AJAX au changement de filtre
- [ ] Responsive (adapté mobile)
- [ ] Tooltip avec valeurs au survol

**Notes techniques :**
- Route `GET /api/dashboard/chart?days=30&project_id=` → JSON
- Agréger par jour : `BacklinkCheck::groupBy(date)->count()`
- Chart.js via CDN ou npm (déjà inclus si présent)
- Cacher les données 10 minutes : `Cache::remember('dashboard_chart_{user}_{days}_{project}', 600)`

**Dépendances :** STORY-020 ✅ (Dashboard)

---

### STORY-030 : Graphique disponibilité global (uptime 30j)

**Epic :** EPIC-007 — Dashboard et Reporting
**Priorité :** Should Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir le taux de disponibilité global de mes backlinks sur 30 jours,
Afin d'évaluer la santé globale de mon profil de backlinks.

**Critères d'acceptation :**
- [ ] Graphique donut/pie : % actifs vs perdus vs modifiés
- [ ] Card "Taux de disponibilité" avec pourcentage global
- [ ] Évolution par rapport à J-7 (flèche haut/bas + delta)
- [ ] Affiché sur le dashboard principal

**Notes techniques :**
- Calcul : `backlinks où is_present=true dans last 30j / total checks dans last 30j * 100`
- Chart.js doughnut chart

**Dépendances :** STORY-029

---

### STORY-031 : Import backlinks CSV

**Epic :** EPIC-002 — Gestion Manuelle des Backlinks
**Priorité :** Could Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux importer une liste de backlinks depuis un fichier CSV,
Afin de démarrer rapidement le monitoring d'un portefeuille existant.

**Critères d'acceptation :**
- [ ] Page `backlinks/import.blade.php` avec upload CSV
- [ ] Format CSV attendu : source_url, target_url, anchor_text (colonnes obligatoires)
- [ ] Colonnes optionnelles : tier_level, spot_type, price, platform
- [ ] Validation de chaque ligne (URL valide, SSRF, doublons)
- [ ] Rapport d'import : X importés, Y échoués avec raisons
- [ ] Limite : 500 backlinks par import
- [ ] Preview des 5 premières lignes avant confirmation
- [ ] Job asynchrone pour imports > 50 lignes

**Notes techniques :**
- Utiliser `League\Csv` (déjà disponible Laravel) ou `str_getcsv()`
- `BacklinkImportJob` pour gros imports
- Route POST `/backlinks/import` avec validation MIME (text/csv)
- `StoreBacklinkRequest` réutilisé pour valider chaque ligne

**Dépendances :** STORY-009 ✅

---

### STORY-032 : Structure Marketplace — Models + Migration

**Epic :** EPIC-006 — Marketplace de Backlinks
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant que système,
Je veux stocker les commandes de backlinks en base de données,
Afin de suivre le cycle de vie de chaque commande depuis la création jusqu'à la mise en ligne.

**Critères d'acceptation :**
- [ ] Table `backlink_orders` : id, user_id, project_id, backlink_id (nullable), platform_id, target_url, anchor_text, price, status (pending/approved/live/rejected/cancelled), platform_order_id, platform_response (JSON), placed_at, live_at, timestamps
- [ ] Model `BacklinkOrder` avec fillable, casts, relations
- [ ] Enum/const pour statuts : PENDING, APPROVED, LIVE, REJECTED, CANCELLED
- [ ] Scope `pending()`, `live()`, `forUser()`
- [ ] Relation `BacklinkOrder hasOne Backlink` (quand live)
- [ ] Relation `Project hasMany BacklinkOrders`

**Notes techniques :**
- Index sur (user_id, status), (platform_id, platform_order_id)
- `platform_response` en JSON pour stocker la réponse brute de l'API

**Dépendances :** STORY-003 ✅, STORY-006 ✅

---

### STORY-033 : Marketplace — Liste et création de commandes (UI)

**Epic :** EPIC-006 — Marketplace de Backlinks
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux voir mes commandes de backlinks et en passer de nouvelles,
Afin de gérer mes achats de backlinks directement depuis LinkTracker.

**Critères d'acceptation :**
- [ ] Page `orders/index.blade.php` : liste paginée avec statuts, dates, projets
- [ ] Badge coloré par statut : pending=gris, approved=bleu, live=vert, rejected=rouge
- [ ] Bouton "Nouvelle commande" → formulaire
- [ ] Formulaire `orders/create.blade.php` : projet, target_url, anchor_text, plateforme, notes
- [ ] Validation côté serveur (SSRF sur target_url)
- [ ] Création avec statut `pending` + dispatch `SyncOrderStatusJob`
- [ ] Lien vers le backlink associé quand statut = live
- [ ] Filtre par statut et par projet

**Notes techniques :**
- OrderController (resource) avec index, create, store, show
- Route dans routes/web.php : resource `orders`
- Pas d'appel API réel en Sprint 3 (mode "manuel" avec platforme = null acceptable)

**Dépendances :** STORY-032

---

### STORY-034 : Notification email pour alertes critiques

**Epic :** EPIC-004 — Alertes et Notifications
**Priorité :** Could Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux recevoir un email quand un backlink important est perdu,
Afin d'être notifié même si je ne consulte pas l'application.

**Critères d'acceptation :**
- [ ] Mail `BacklinkLostMail` : nom projet, URL backlink, date perte, lien vers app
- [ ] Envoyé uniquement pour alertes severity = critical ou high
- [ ] Option opt-out dans Settings (checkbox "Recevoir les emails d'alerte")
- [ ] Throttling : max 1 email par backlink par 24h
- [ ] Template email Blade responsive avec branding basique
- [ ] Queue : dispatché par AlertService après création alerte critique

**Notes techniques :**
- `php artisan make:mail BacklinkLostMail --markdown=emails.backlink-lost`
- MAIL_MAILER=smtp dans .env (ou log pour dev)
- Vérifier `auth()->user()->email_alerts_enabled` avant envoi
- Colonne `email_alerts_enabled` dans users (migration additionnelle)

**Dépendances :** STORY-017 ✅, STORY-027

---

### STORY-035 : Export CSV des backlinks

**Epic :** EPIC-007 — Dashboard et Reporting
**Priorité :** Could Have
**Points :** 2

**User Story :**
En tant qu'utilisateur,
Je veux exporter la liste de mes backlinks en CSV,
Afin de faire des analyses dans Excel ou de partager un rapport.

**Critères d'acceptation :**
- [ ] Bouton "Exporter CSV" sur la page backlinks/index
- [ ] Export filtré selon les filtres actifs (projet, statut)
- [ ] Colonnes : source_url, target_url, anchor_text, status, tier_level, DA, last_checked_at
- [ ] Nom de fichier : `backlinks-{project}-{date}.csv`
- [ ] Headers HTTP corrects (Content-Type: text/csv)
- [ ] Max 5000 lignes par export

**Notes techniques :**
- BacklinkController@export (GET /backlinks/export?project=&status=)
- `str_putcsv()` ou `League\Csv\Writer`
- StreamedResponse pour éviter le timeout sur gros exports

**Dépendances :** STORY-007 ✅

---

## Allocation du Sprint 3

### Sprint 3 (18/02 → 03/03/2026) — 44/44 points

**Objectif :** Enrichir LinkTracker avec les métriques SEO, la configuration centralisée, les graphiques de tendances et les fondations de la marketplace.

#### Semaine 1 — Métriques SEO + Configuration (22 pts)

| Story | Titre | Points | Priorité | Epic |
|-------|-------|--------|---------|------|
| STORY-022 | SeoMetricService multi-providers | 5 | Must Have | EPIC-005 |
| STORY-023 | Migration domain_metrics + Model | 2 | Must Have | EPIC-005 |
| STORY-024 | FetchSeoMetricsJob + scheduler | 3 | Must Have | EPIC-005 |
| STORY-025 | Affichage métriques sur détail backlink | 3 | Must Have | EPIC-005 |
| STORY-027 | Page Settings centralisée | 3 | Must Have | EPIC-008 |
| STORY-028 | Config API SEO + test connexion | 3 | Must Have | EPIC-005 |
| STORY-026 | Colonne métriques dans liste | 2 | Should Have | EPIC-005 |
| STORY-034 | Notification email alertes critiques | 3 | Could Have | EPIC-004 |

**Total semaine 1 : 24 pts**

#### Semaine 2 — Graphiques + Marketplace + Export (22 pts)

| Story | Titre | Points | Priorité | Epic |
|-------|-------|--------|---------|------|
| STORY-029 | Graphique évolution backlinks Chart.js | 5 | Must Have | EPIC-007 |
| STORY-030 | Graphique disponibilité global | 3 | Should Have | EPIC-007 |
| STORY-032 | Structure Marketplace — Models | 3 | Must Have | EPIC-006 |
| STORY-033 | Marketplace — Liste + création commandes | 5 | Must Have | EPIC-006 |
| STORY-031 | Import backlinks CSV | 5 | Could Have | EPIC-002 |
| STORY-035 | Export CSV backlinks | 2 | Could Have | EPIC-007 |

**Total semaine 2 : 23 pts**

**Total Sprint 3 : 47 pts** *(légèrement au-dessus — ajuster si besoin en déplaçant STORY-031 ou STORY-034 au backlog)*

> **Ajustement recommandé :** Si la capacité est confirmée à 44 pts, déplacer STORY-034 (email, 3pts) et STORY-035 (export, 2pts) au Sprint 4 → **44 pts exactement.**

---

## Ordre d'implémentation recommandé

```
Semaine 1 :
STORY-023 (migration BDD) → STORY-022 (service) → STORY-024 (job + scheduler)
→ STORY-027 (settings UI) → STORY-028 (config API SEO)
→ STORY-025 (affichage détail) → STORY-026 (colonne liste)

Semaine 2 :
STORY-029 (graphique évolution) → STORY-030 (graphique uptime)
→ STORY-032 (migration marketplace) → STORY-033 (UI marketplace)
→ STORY-031 (import CSV) → STORY-035 (export CSV)
```

Lancer `/dev-story STORY-023` pour commencer l'implémentation.

---

## Traçabilité Epic → Stories

| Epic | Nom | Stories Sprint 3 | Points |
|------|-----|---------|--------|
| EPIC-005 | Métriques SEO | STORY-022, 023, 024, 025, 026, 028 | 18 pts |
| EPIC-006 | Marketplace | STORY-032, 033 | 8 pts |
| EPIC-007 | Dashboard/Reporting | STORY-029, 030, 035 | 10 pts |
| EPIC-008 | Configuration | STORY-027 | 3 pts |
| EPIC-004 | Alertes (email) | STORY-034 | 3 pts |
| EPIC-002 | Backlinks (import) | STORY-031 | 5 pts |

---

## Couverture des exigences fonctionnelles

| FR | Description | Story |
|----|-------------|-------|
| FR-020 | Récupérer métriques SEO | STORY-022, 023, 024 |
| FR-021 | Afficher métriques SEO | STORY-025, 026 |
| FR-022 | Mettre à jour métriques quotidiennement | STORY-024 |
| FR-023 | Configurer providers SEO | STORY-028 |
| FR-024 | Passer commande backlink | STORY-033 |
| FR-025 | Suivre statut commande | STORY-033 |
| FR-029 | Visualiser évolution backlinks | STORY-029, 030 |
| FR-030 | Exporter rapport | STORY-035 |
| FR-010 | Importer backlinks CSV | STORY-031 |
| FR-019 | Notification email | STORY-034 |
| FR-032 | Configuration globale | STORY-027, 028 |

---

## Risques et mitigation

**Haut :**
- **API Moz indisponible ou quota épuisé** — Mitigation : CustomProvider comme fallback, tests avec mocks HTTP, pas de blocage si API down
- **Chart.js / graphiques complexes** — Mitigation : commencer avec graphique simple, ajouter filtres ensuite

**Moyen :**
- **Marketplace sans API réelle** — Mitigation : implémentation UI + data model sans appels API (fondations pour Sprint 4)
- **Import CSV encodages variés** — Mitigation : détecter et convertir UTF-8, limiter à 500 lignes

**Faible :**
- **Performances avec beaucoup de domaines à mettre à jour** — Mitigation : jobs en queue avec rate limiting, caching 24h

---

## Définition of Done

Pour qu'une story soit considérée complète :
- [ ] Code implémenté et commité
- [ ] Tests écrits et passants (Services + Jobs)
- [ ] Critères d'acceptation validés manuellement
- [ ] Pas de régressions sur les tests existants
- [ ] Code conforme aux conventions du projet (CLAUDE.md)

---

## Prochaines étapes Sprint 4 (prévisionnel)

- **EPIC-006 suite** : Intégration API marketplace réelle (NextLevel, SEOClerks)
- **EPIC-006** : Synchronisation statuts commandes + ajout auto au monitoring
- **EPIC-009** : Optimisations performances (indexes, caching avancé)
- **EPIC-012** : Tests automatisés — couverture > 60%
- Stories déplacées depuis Sprint 3 : STORY-034, STORY-035 (si non complétées)

---

**Plan généré avec BMAD Method v6 — Phase 4 (Implementation Planning) — 2026-02-18**
