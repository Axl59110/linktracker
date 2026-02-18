# Sprint Plan — Sprint 5 : LinkTracker

**Date :** 2026-02-18
**Sprint :** 5 (18/02/2026 → 03/03/2026)
**Scrum Master :** BMAD Agent
**Niveau projet :** 4
**Stories :** 12
**Total points :** 47
**Capacité sprint :** 44 points (buffer : 3 pts en bonus)
**Objectif :** Consolider les acquis post-Sprint 4, améliorer la précision des données de pilotage, et jeter les bases pour l'intégration API SEO réelle

---

## Résumé exécutif

Le Sprint 5 formalise les fonctionnalités livrées en dehors des sprints officiels (bulk actions, graphiques avancés, is_indexed) et engage les prochaines priorités : intégration Ahrefs/Moz réelle, amélioration de la précision du monitoring (re-check automatique avant alerte), et multi-projet dashboard.

**Métriques clés :**
- Stories : 12 (5 déjà livrées à formaliser + 7 nouvelles)
- Points : 47
- Vélocité moyenne : 40 pts/sprint (sprints 1-4)
- Fin prévue : 03/03/2026

---

## Inventaire des stories

---

### STORY-050 : Bulk actions — édition et suppression en masse des backlinks

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Must Have
**Points :** 5
**Statut :** ✅ Complété (2026-02-18)

**User Story :**
En tant qu'utilisateur,
Je veux pouvoir sélectionner plusieurs backlinks et les éditer ou supprimer en masse,
Afin de mettre à jour rapidement les données importées depuis un CSV.

**Critères d'acceptation :**
- [x] Checkbox par ligne + "tout sélectionner" dans l'en-tête
- [x] Barre d'actions flottante (visible dès 1 sélection)
- [x] Suppression en masse avec confirmation
- [x] Modification en masse : published_at, status, is_indexed, is_dofollow
- [x] Max 500 IDs par requête (protection DoS)
- [x] Message de succès avec nombre d'éléments modifiés/supprimés

**Notes techniques :**
- `BacklinkController@bulkDelete()` et `@bulkEdit()` ajoutés
- Routes `POST /backlinks/bulk-delete` et `POST /backlinks/bulk-edit`
- AlpineJS `bulkActions()` function avec `selected[]`, `toggleAll()`, `confirmBulkDelete()`

---

### STORY-051 : Double graphique synchronisé — courbes qualité + bougies gains/pertes

**Epic :** EPIC-007 — Dashboard et Reporting
**Priorité :** Must Have
**Points :** 8
**Statut :** ✅ Complété (2026-02-18)

**User Story :**
En tant qu'utilisateur,
Je veux voir l'évolution de mes backlinks en deux graphiques superposés partageant la même période,
Afin de comprendre à la fois la qualité du profil dans le temps et les acquisitions journalières.

**Critères d'acceptation :**
- [x] Graphique 1 : 4 courbes cumulatives (Total, Parfaits, Non indexés, Nofollow)
- [x] Boutons toggle individuels par courbe
- [x] Graphique 2 : bougies journalières gains (vert) / pertes (rouge inversé)
- [x] Sélecteur période unique (30j / 90j / 6m / 1an) synchronisant les deux graphiques
- [x] Un seul fetch pour les deux graphiques
- [x] Disponible sur /dashboard et /projects/{id}

**Notes techniques :**
- `DashboardController::chartData()` enrichi : `perfect`, `not_indexed`, `nofollow` + base cumulatif
- 6 requêtes SQL groupées (vs N boucles précédentes)
- `backlinkChart()` AlpineJS : `renderQuality()` + `renderCandles()` + `toggleSeries()`

---

### STORY-052 : Dashboard de pilotage enrichi — health score et KPI scoring

**Epic :** EPIC-007 — Dashboard et Reporting
**Priorité :** Must Have
**Points :** 5
**Statut :** ✅ Complété (2026-02-18)

**User Story :**
En tant qu'utilisateur,
Je veux voir un score de santé global et des KPI de pilotage sur le dashboard et sur chaque projet,
Afin d'identifier immédiatement les problèmes d'indexation ou de qualité.

**Critères d'acceptation :**
- [x] Health Score 0-100 (SVG donut) : 60% ratio actifs + 40% ratio qualité
- [x] 6 KPI cards : Actifs, Parfaits (actif+indexé+dofollow), Non indexés, Nofollow, Budget, Uptime
- [x] Identique sur /dashboard et /projects/{id}
- [x] Stats mises en cache 5 minutes

**Notes techniques :**
- `DashboardController::index()` + `ProjectController::show()` : calcul `healthScore`, `qualityLinks`, `notIndexed`, `notDofollow`, `unknownIndexed`, `budgetTotal`, `budgetActive`

---

### STORY-053 : Import CSV tiers-parti avec auto-détection de format

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Should Have
**Points :** 5
**Statut :** ✅ Complété (2026-02-18)

**User Story :**
En tant qu'utilisateur,
Je veux pouvoir importer un CSV exporté depuis une plateforme tierce (Ahrefs, Semrush, etc.),
Afin de migrer mes données sans ressaisie manuelle.

**Critères d'acceptation :**
- [x] Auto-détection du format : colonnes "Domain Rating" ou "URL Rating" → format tiers
- [x] Mapping : Source Page → source_url, Anchor → anchor_text, Created At → published_at, Indexed → is_indexed
- [x] `project_id` toujours requis (pas de création automatique de projet)
- [x] PHP 8.4 compatible (fgetcsv avec paramètre escape explicite)
- [x] 20 tests Feature couvrant import natif, tiers, erreurs

**Notes techniques :**
- `BacklinkCsvImportService::import()` avec `importNative()` / `importThirdParty()`
- `is_indexed` : "Yes" → true, "No" → false, vide → null

---

### STORY-054 : Champ is_indexed et published_at sur les backlinks

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Should Have
**Points :** 3
**Statut :** ✅ Complété (2026-02-18)

**User Story :**
En tant qu'utilisateur,
Je veux pouvoir tracker si mes backlinks sont indexés par Google et connaître leur date de publication réelle,
Afin d'évaluer la qualité de mes links building.

**Critères d'acceptation :**
- [x] Colonne `is_indexed` boolean nullable sur la table backlinks
- [x] `published_at` date nullable (date réelle de publication du contenu)
- [x] Affiché dans les listes et tableaux (colonne Indexé : Yes/No/—)
- [x] Éditable via bulk edit et formulaire individuel
- [x] Graphiques basés sur `published_at` (fallback `created_at`)

**Notes techniques :**
- Migration `add_is_indexed_to_backlinks_table`
- `Backlink::$fillable` et `$casts` mis à jour

---

### STORY-055 : Intégration Ahrefs API réelle (Domain Rating, URL Rating)

**Epic :** EPIC-005 — Métriques SEO Enrichies
**Priorité :** Should Have
**Points :** 8

**User Story :**
En tant qu'utilisateur,
Je veux que les métriques DA/DR affichées soient récupérées depuis la vraie API Ahrefs,
Afin d'avoir des données fiables pour évaluer la qualité de mes backlinks.

**Critères d'acceptation :**
- [ ] Nouveau provider `AhrefsProvider` implémentant `SeoMetricProviderInterface`
- [ ] Récupère Domain Rating (DR) et URL Rating (UR) via Ahrefs API v3
- [ ] Clé API configurable depuis `/settings` (onglet SEO)
- [ ] Test de connexion fonctionnel dans l'UI
- [ ] Fallback gracieux si quota dépassé ou erreur API
- [ ] Cache 24h des résultats par domaine
- [ ] Tests Feature avec mock HTTP (HTTP Fake Laravel)

**Notes techniques :**
- `app/Services/SeoMetric/Providers/AhrefsProvider.php`
- Endpoint : `https://api.ahrefs.com/v3/site-explorer/domain-rating`
- Auth : Bearer token dans header
- `SeoMetricService::getMetrics()` sélectionne le provider configuré

**Dépendances :** STORY-022 ✅, STORY-028 ✅

---

### STORY-056 : Re-check automatique avant alerte (réduction faux positifs)

**Epic :** EPIC-003 — Monitoring Automatique
**Priorité :** Should Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux que le système vérifie deux fois un backlink avant d'envoyer une alerte "perdu",
Afin d'éviter les fausses alertes dues à des erreurs temporaires de serveur.

**Critères d'acceptation :**
- [ ] Si `is_present = false`, relancer une 2ème vérification après 15 minutes
- [ ] L'alerte `backlink_lost` n'est créée que si les 2 checks confirment l'absence
- [ ] La 2ème vérification utilise la même queue prioritaire
- [ ] Option configurable dans `/settings` (activer/désactiver le double-check)
- [ ] Tests Feature couvrant le scénario de faux positif évité

**Notes techniques :**
- `CheckBacklinkJob::handle()` : si `is_present = false`, dispatcher un nouveau job avec `delay(minutes(15))`
- Flag `is_recheck` sur le job pour éviter boucle infinie
- Nouveau champ `double_check_enabled` dans `users` table

**Dépendances :** STORY-013 ✅

---

### STORY-057 : Filtre "Mes alertes non lues" + badge global dans la topbar

**Epic :** EPIC-004 — Alertes et Notifications
**Priorité :** Should Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir immédiatement le nombre d'alertes non lues dans la navigation,
Afin de ne jamais rater un problème critique.

**Critères d'acceptation :**
- [ ] Badge rouge dans la topbar avec le nombre d'alertes non lues
- [ ] Le badge disparaît quand toutes les alertes sont lues
- [ ] Dropdown rapide dans la topbar avec les 5 dernières alertes non lues
- [ ] Clic sur une alerte → marque comme lue + redirige vers le backlink
- [ ] Le badge se met à jour via polling toutes les 60 secondes (Alpine `x-init setInterval`)

**Notes techniques :**
- `app/Http/Controllers/AlertController` : endpoint `GET /api/alerts/unread-count`
- AlpineJS dans `layouts/app.blade.php` : polling léger

**Dépendances :** STORY-017 ✅ (AlertController)

---

### STORY-058 : Page /projects avec métriques agrégées (vue portfolio)

**Epic :** EPIC-001 — Gestion des Projets
**Priorité :** Could Have
**Points :** 3

**User Story :**
En tant qu'utilisateur,
Je veux voir sur la liste de mes projets les KPI essentiels (total backlinks, health score, alertes non lues),
Afin d'identifier rapidement quel projet nécessite mon attention.

**Critères d'acceptation :**
- [ ] Chaque carte projet affiche : total backlinks, health score (badge coloré), nombre d'alertes non lues
- [ ] Tri par health score (croissant = problèmes en premier)
- [ ] Vue grille + vue liste switchable
- [ ] Badge "urgent" si alertes non lues > 0

**Notes techniques :**
- `ProjectController::index()` : eager load `backlinks_count`, `unread_alerts_count`
- Calcul health_score côté PHP (même formule que dashboard)

**Dépendances :** STORY-052 ✅

---

### STORY-059 : Amélioration formulaire backlink — autocomplétion projet + plateforme

**Epic :** EPIC-002 — Gestion des Backlinks
**Priorité :** Could Have
**Points :** 2

**User Story :**
En tant qu'utilisateur,
Je veux pouvoir sélectionner rapidement un projet et une plateforme lors de l'ajout d'un backlink,
Afin de gagner du temps dans la saisie.

**Critères d'acceptation :**
- [ ] Champ projet : select avec recherche (type-ahead) AlpineJS
- [ ] Champ plateforme : select avec option "Nouvelle plateforme" inline
- [ ] Le projet est pré-sélectionné si venant de `/projects/{id}`
- [ ] Sauvegarde de la dernière plateforme utilisée (localStorage)

**Notes techniques :**
- `x-data` AlpineJS avec filtre en temps réel sur les options
- Query param `?project_id=` dans `backlinks/create`

**Dépendances :** STORY-009 ✅

---

### STORY-060 : Tests de régression post-Sprint 5 + couverture bulk actions

**Epic :** EPIC-012 — Testing et Qualité
**Priorité :** Must Have
**Points :** 3

**User Story :**
En tant que développeur,
Je veux une suite de tests complète pour les fonctionnalités du Sprint 5,
Afin de garantir la non-régression sur les 360+ tests existants.

**Critères d'acceptation :**
- [ ] Tests Feature pour `bulkDelete` : succès, vide, trop d'IDs, IDs invalides
- [ ] Tests Feature pour `bulkEdit` : chaque champ (published_at, status, is_indexed, is_dofollow)
- [ ] Tests pour `DashboardController::chartData()` : `perfect`, `not_indexed`, `nofollow` dans la réponse
- [ ] Suite complète ≥ 360 tests passants sans régression

**Notes techniques :**
- `tests/Feature/BacklinkBulkActionsTest.php` à créer
- Vérifier `DashboardChartsTest` avec les 8 nouvelles clés JSON

**Dépendances :** STORY-050 ✅, STORY-051 ✅

---

### STORY-061 : Multi-utilisateurs — isolation des données par user_id

**Epic :** EPIC-001 — Gestion des Projets
**Priorité :** Must Have
**Points :** 5

**User Story :**
En tant qu'utilisateur,
Je veux que mes données (projets, backlinks, alertes) soient complètement isolées de celles des autres utilisateurs,
Afin de garantir la confidentialité de mes données SEO.

**Critères d'acceptation :**
- [ ] `ProjectController` : toutes les requêtes filtrées par `auth()->id()`
- [ ] `BacklinkController` : accès refusé si le projet ne m'appartient pas (403)
- [ ] `AlertController` : alertes filtrées par user via relation backlink→project→user
- [ ] `DashboardController` : stats filtrées par user
- [ ] Tests Feature vérifiant qu'un utilisateur ne peut pas accéder aux données d'un autre

**Notes techniques :**
- Ajouter `->where('user_id', auth()->id())` dans tous les controllers
- Ou utiliser `Policy` Laravel pour chaque model
- `ProjectPolicy` déjà créé en Sprint 1, à étendre aux autres models

**Dépendances :** STORY-003 ✅ (ProjectPolicy)

---

## Allocation Sprint 5

### Sprint 5 (18/02/2026 → 03/03/2026) — 47/44 points (légèrement au-dessus, sprints passés à 100%)

**Objectif :** Formaliser les acquis post-Sprint 4 et avancer sur la qualité des données et l'isolation multi-users

| Story | Titre | Points | Priorité | Statut |
|-------|-------|--------|----------|--------|
| STORY-050 | Bulk actions (edit/delete) | 5 | Must Have | ✅ Livré |
| STORY-051 | Double graphique synchronisé | 8 | Must Have | ✅ Livré |
| STORY-052 | Dashboard health score + KPI | 5 | Must Have | ✅ Livré |
| STORY-053 | Import CSV tiers-parti | 5 | Should Have | ✅ Livré |
| STORY-054 | is_indexed + published_at | 3 | Should Have | ✅ Livré |
| STORY-055 | Intégration Ahrefs API réelle | 8 | Should Have | À faire |
| STORY-056 | Re-check avant alerte | 5 | Should Have | À faire |
| STORY-057 | Badge alertes topbar | 3 | Should Have | À faire |
| STORY-058 | Projects — vue portfolio KPI | 3 | Could Have | À faire |
| STORY-059 | Formulaire backlink autocomplétion | 2 | Could Have | À faire |
| STORY-060 | Tests régression bulk actions | 3 | Must Have | À faire |
| STORY-061 | Multi-users isolation | 5 | Must Have | À faire |

**Total : 47 / 44 points (livraisons déjà faites compensent)**

---

## Traçabilité Epic → Stories

| Epic | Stories Sprint 5 | Points |
|------|-----------------|--------|
| EPIC-001 Projets | STORY-058, 061 | 8 pts |
| EPIC-002 Backlinks | STORY-050, 053, 054, 059 | 15 pts |
| EPIC-003 Monitoring | STORY-056 | 5 pts |
| EPIC-004 Alertes | STORY-057 | 3 pts |
| EPIC-005 SEO Métriques | STORY-055 | 8 pts |
| EPIC-007 Reporting | STORY-051, 052 | 13 pts |
| EPIC-012 Qualité | STORY-060 | 3 pts |

---

## Risques

**Haut :**
- STORY-055 (Ahrefs API) : dépend d'une clé API réelle et de la structure de l'API Ahrefs v3 — prévoir mock HTTP si pas de clé disponible
- STORY-061 (multi-users) : risque de régression sur tous les controllers si mal implémenté → tester chaque controller séparément

**Moyen :**
- STORY-056 (re-check) : le délai de 15 minutes complique les tests Feature → utiliser `Carbon::setTestNow()` pour simuler le délai

**Faible :**
- STORY-057 (badge alertes) : polling toutes les 60s peut créer un pic de requêtes si beaucoup d'onglets ouverts → limiter à 1 requête active à la fois

---

## Définition de Terminé

- [ ] Code implémenté et committé sur `master`
- [ ] Tests écrits et passants (≥ 360 tests total)
- [ ] Aucune régression sur les tests existants
- [ ] Routes accessibles et fonctionnelles
- [ ] Isolation multi-users validée

---

**Plan créé avec BMAD Method v6 - Phase 4 (Sprint Planning)**
