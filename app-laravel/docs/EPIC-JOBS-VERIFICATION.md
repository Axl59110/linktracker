# EPIC : Jobs de vérification des backlinks et extraction des ancres

## 📋 Vue d'ensemble

Ce document décrit l'implémentation complète du système automatisé de vérification des backlinks, d'extraction des ancres de liens, et du système d'alertes associé.

## ✅ Fonctionnalités implémentées

### 1. Système de queues Laravel (✓ Complété)

**Fichiers créés :**
- `database/migrations/2026_02_13_200000_create_jobs_table.php`
- `docs/QUEUES.md` - Documentation complète

**Configuration :**
- Driver database pour les queues
- Tables `jobs` et `failed_jobs`
- Rate limiting sur les jobs de vérification
- Documentation pour production (Supervisor)

**Commandes disponibles :**
```bash
# Lancer le worker
php artisan queue:work --verbose

# En production avec Supervisor
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
```

---

### 2. Modèle BacklinkCheck (✓ Complété)

**Fichiers existants :**
- `app/Models/BacklinkCheck.php`
- `database/migrations/2026_02_12_131808_create_backlink_checks_table.php`

**Colonnes :**
- `backlink_id` - FK vers backlinks
- `checked_at` - Date/heure de vérification
- `is_present` - Booléen : lien trouvé ou non
- `http_status` - Code HTTP (200, 404, etc.)
- `anchor_text` - Ancre détectée lors de la vérification
- `rel_attributes` - Attributs rel (nofollow, sponsored, etc.)
- `error_message` - Message d'erreur si échec

**Méthodes utiles :**
- `isSuccessful()` - Vérifie si HTTP 2xx
- `wasFound()` - Vérifie si le lien a été trouvé

---

### 3. Système d'alertes complet (✓ Complété)

#### a) Modèle et migration

**Fichiers créés :**
- `app/Models/Alert.php`
- `database/migrations/2026_02_13_201000_create_alerts_table.php`

**Types d'alertes :**
- `backlink_lost` - Backlink perdu (non trouvé)
- `backlink_changed` - Backlink modifié (ancre, rel, dofollow)
- `backlink_recovered` - Backlink récupéré (était perdu, maintenant trouvé)

**Niveaux de sévérité :**
- `critical` - Critique (tier1 perdu, passage en nofollow)
- `high` - Élevé (backlink payant perdu, ancre modifiée)
- `medium` - Moyen (tier1 modifié)
- `low` - Faible (tier2 modifié, backlink récupéré)

#### b) Service AlertService

**Fichier créé :**
- `app/Services/Alert/AlertService.php`

**Méthodes :**
- `createBacklinkLostAlert(Backlink $backlink, ?string $reason)`
- `createBacklinkChangedAlert(Backlink $backlink, array $changes)`
- `createBacklinkRecoveredAlert(Backlink $backlink)`
- `markBacklinkAlertsAsRead(Backlink $backlink)`
- `cleanupOldAlerts(int $daysOld = 30)`

**Logique de sévérité :**
```php
// Pour backlink perdu
- Tier 1 → CRITICAL
- Prix > 0 → HIGH
- Autres → MEDIUM

// Pour backlink modifié
- Passage en nofollow → CRITICAL
- Ancre modifiée → HIGH
- Tier 1 → MEDIUM
- Tier 2 → LOW
```

#### c) Interface utilisateur des alertes

**Fichiers créés :**
- `app/Http/Controllers/AlertController.php`
- `resources/views/pages/alerts/index.blade.php`
- `app/Providers/ViewServiceProvider.php`

**Routes :**
```
GET  /alerts                      → Liste des alertes avec filtres
PATCH /alerts/{id}/mark-read      → Marquer une alerte comme lue
PATCH /alerts/mark-all-read       → Marquer toutes comme lues
DELETE /alerts/{id}               → Supprimer une alerte
DELETE /alerts/destroy-all-read   → Supprimer toutes les alertes lues
```

**Filtres disponibles :**
- Type (lost, changed, recovered)
- Sévérité (critical, high, medium, low)
- Statut (lues / non lues)
- Période (dernières 24h, 7j, 30j, 90j)

**Statistiques affichées :**
- Total des alertes
- Alertes non lues
- Alertes critiques
- Alertes du jour

#### d) Intégration dans l'application

**Modifications :**
- `config/app.php` - Ajout de ViewServiceProvider
- `components/sidebar.blade.php` - Badge avec nombre d'alertes non lues
- `app/Http/Controllers/DashboardController.php` - Alertes récentes
- `resources/views/pages/dashboard.blade.php` - Widget alertes récentes
- `app/Jobs/CheckBacklinkJob.php` - Création automatique d'alertes

---

### 4. Job CheckBacklinkJob amélioré (✓ Complété)

**Fichier modifié :**
- `app/Jobs/CheckBacklinkJob.php`

**Améliorations :**
- Intégration AlertService
- Détection précise des changements d'attributs
- Création automatique d'alertes selon le type de changement
- Méthode `getAttributesChanges()` pour tracker les modifications

**Changements détectés :**
- Modification de l'ancre (`anchor_text`)
- Modification des attributs rel (`rel_attributes`)
- Passage dofollow ↔ nofollow (`is_dofollow`)

---

### 5. Service BacklinkCheckerService (existant)

**Fichier existant :**
- `app/Services/Backlink/BacklinkCheckerService.php`

**Fonctionnalités :**
- Requête HTTP avec timeout et User-Agent personnalisé
- Protection SSRF (Server-Side Request Forgery)
- Parsing HTML avec DOMDocument/DOMXPath
- Extraction de l'ancre de lien
- Détection des attributs rel (nofollow, sponsored, ugc)
- Détection dofollow/nofollow
- Normalisation d'URLs (http/https, www, trailing slash)

**Méthodes principales :**
- `check(Backlink $backlink)` - Vérifie un backlink
- `findLinkInHtml($html, $targetUrl)` - Parse le HTML
- `urlsMatch($url1, $url2)` - Compare les URLs
- `normalizeUrl($url)` - Normalise une URL

---

### 6. Commande de vérification en batch (existante)

**Fichier existant :**
- `app/Console/Commands/CheckBacklinksCommand.php`

**Utilisation :**
```bash
# Vérifier les backlinks non vérifiés depuis 24h
php artisan app:check-backlinks --frequency=daily

# Vérifier tous les backlinks non vérifiés depuis 7 jours
php artisan app:check-backlinks --frequency=weekly

# Vérifier tous les backlinks
php artisan app:check-backlinks --frequency=all

# Filtres
php artisan app:check-backlinks --project=1 --status=active --limit=50
```

**Options :**
- `--frequency=daily|weekly|all` - Fréquence de vérification
- `--project=ID` - Filtrer par projet
- `--status=active|lost|changed|all` - Filtrer par statut
- `--limit=N` - Limiter le nombre de backlinks

---

### 7. Scheduler Laravel (✓ Complété)

**Fichier existant :**
- `app/Console/Kernel.php`

**Tâches planifiées :**
```php
// Vérification quotidienne à 2h du matin
$schedule->command('app:check-backlinks --frequency=daily')
         ->dailyAt('02:00')
         ->withoutOverlapping()
         ->appendOutputTo(storage_path('logs/scheduler.log'));

// Vérification hebdomadaire complète (dimanches à 3h)
$schedule->command('app:check-backlinks --frequency=weekly --status=all')
         ->weekly()
         ->sundays()
         ->at('03:00')
         ->withoutOverlapping()
         ->appendOutputTo(storage_path('logs/scheduler.log'));
```

**Activation :**
```bash
# Ajouter au crontab (production)
* * * * * cd /path/to/app-laravel && php artisan schedule:run >> /dev/null 2>&1

# Simuler le cron (développement)
php artisan schedule:work
```

---

### 8. Page d'historique de vérifications (✓ Complété)

**Fichier modifié :**
- `resources/views/pages/backlinks/show.blade.php`

**Affichage :**
- Taux de disponibilité en pourcentage avec barre de progression
- Historique des 10 dernières vérifications
- Pour chaque vérification :
  - Icône ✓ ou ✗
  - Statut HTTP avec badge
  - Date et heure
  - Ancre détectée
  - Message d'erreur si échec

**Calcul du taux de disponibilité :**
```php
$totalChecks = $backlink->checks->count();
$successfulChecks = $backlink->checks->where('is_present', true)->count();
$availabilityRate = round(($successfulChecks / $totalChecks) * 100, 1);
```

**Code couleur :**
- ≥ 95% → Vert (success)
- ≥ 80% → Orange (warning)
- < 80% → Rouge (danger)

---

### 9. Commande de vérification manuelle unique (✓ Complété)

**Fichier créé :**
- `app/Console/Commands/CheckBacklinkCommand.php`

**Utilisation :**
```bash
# Vérifier un backlink spécifique
php artisan app:check-backlink 42

# Mode verbose avec détails complets
php artisan app:check-backlink 42 --verbose
```

**Affichage :**
```
🔍 Checking backlink #42
   Project: Mon site web
   Source URL: https://example.com/article
   Target URL: https://monsite.com

⏳ Fetching and analyzing page...

📊 Check Results:
─────────────────────────────────────────
   HTTP Status: 200
   Backlink Found: ✓ YES
   Anchor Text: Visitez mon site
   Rel Attributes: <none>
   Dofollow: Yes
─────────────────────────────────────────

💾 Saving check results...
✅ Check completed successfully!
   Check ID: #156
   Backlink Status: active
```

**Fonctionnalités :**
- Vérification en temps réel avec résultats détaillés
- Sauvegarde dans la base de données
- Création automatique d'alertes
- Affichage des changements de statut
- Mode verbose pour debugging

---

### 10. Bouton de vérification manuelle dans l'UI (✓ Complété)

**Fichiers modifiés :**
- `app/Http/Controllers/BacklinkController.php`
- `routes/web.php`
- `resources/views/pages/backlinks/show.blade.php`
- `resources/views/components/alert.blade.php`

**Route :**
```
POST /backlinks/{id}/check → backlinks.check
```

**Rate limiting :**
- 5 vérifications manuelles par minute maximum

**Bouton dans l'UI :**
- Positionné dans le header de la page show
- Icône 🔄 "Vérifier maintenant"
- Confirmation avant lancement
- Variante "brand" pour attirer l'attention

**Messages flash :**
- ✅ Succès (variante success) : lien trouvé et actif
- ⚠️ Avertissement (variante warning) : lien non trouvé
- ❌ Erreur (variante danger) : échec de la vérification

**Support des alertes warning :**
Ajout du type "warning" dans le composant alert :
```php
'warning' => [
    'container' => 'bg-warning-50 border-warning-200 text-warning-900',
    'icon' => '⚠',
],
```

---

## 🗂️ Structure des fichiers

### Modèles
```
app/Models/
├── Backlink.php (modifié - relations alerts)
├── BacklinkCheck.php (existant)
└── Alert.php (nouveau)
```

### Services
```
app/Services/
├── Backlink/
│   └── BacklinkCheckerService.php (existant)
├── Alert/
│   └── AlertService.php (nouveau)
└── Security/
    └── UrlValidator.php (existant)
```

### Jobs
```
app/Jobs/
└── CheckBacklinkJob.php (modifié - intégration alertes)
```

### Commandes
```
app/Console/Commands/
├── CheckBacklinksCommand.php (existant)
└── CheckBacklinkCommand.php (nouveau)
```

### Controllers
```
app/Http/Controllers/
├── BacklinkController.php (modifié - méthode check)
├── AlertController.php (nouveau)
└── DashboardController.php (modifié - alertes récentes)
```

### Vues
```
resources/views/
├── pages/
│   ├── alerts/
│   │   └── index.blade.php (nouveau)
│   ├── backlinks/
│   │   └── show.blade.php (modifié - historique)
│   └── dashboard.blade.php (modifié - widget alertes)
└── components/
    ├── sidebar.blade.php (modifié - badge alertes)
    └── alert.blade.php (modifié - variante warning)
```

### Migrations
```
database/migrations/
├── 2026_02_13_200000_create_jobs_table.php (nouveau)
└── 2026_02_13_201000_create_alerts_table.php (nouveau)
```

### Documentation
```
docs/
├── QUEUES.md (nouveau)
└── EPIC-JOBS-VERIFICATION.md (ce fichier)
```

---

## 🚀 Installation et configuration

### 1. Lancer les migrations

```bash
cd app-laravel
php artisan migrate
```

Cela créera les tables :
- `jobs` - Queue des jobs
- `failed_jobs` - Jobs échoués
- `alerts` - Système d'alertes

### 2. Configurer les queues

**Développement (synchrone) :**
```env
QUEUE_CONNECTION=sync
```

**Production (asynchrone) :**
```env
QUEUE_CONNECTION=database
```

### 3. Lancer le worker (production)

```bash
# Terminal dédié
php artisan queue:work --verbose --tries=3 --timeout=120

# Ou avec Supervisor (recommandé)
# Voir docs/QUEUES.md pour la configuration
```

### 4. Activer le scheduler (production)

```bash
# Ajouter au crontab
crontab -e

# Ajouter cette ligne
* * * * * cd /var/www/linktracker/app-laravel && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 Utilisation

### Vérification automatique

Les backlinks seront vérifiés automatiquement selon le planning :
- **Quotidien (2h)** : backlinks non vérifiés depuis 24h
- **Hebdomadaire (dimanche 3h)** : tous les backlinks non vérifiés depuis 7 jours

### Vérification manuelle via commande

```bash
# Un backlink spécifique
php artisan app:check-backlink 42 --verbose

# Batch de backlinks
php artisan app:check-backlinks --frequency=daily --limit=100
```

### Vérification manuelle via UI

1. Aller sur la page du backlink : `/backlinks/{id}`
2. Cliquer sur "🔄 Vérifier maintenant"
3. Confirmer
4. Voir les résultats et l'historique mis à jour

### Consulter les alertes

1. Aller sur `/alerts`
2. Filtrer par type, sévérité, statut, période
3. Marquer comme lu / supprimer
4. Les alertes non lues apparaissent dans la sidebar avec badge

---

## 🔧 Maintenance

### Nettoyer les anciennes alertes lues

```bash
php artisan tinker
>>> app(App\Services\Alert\AlertService::class)->cleanupOldAlerts(30);
# Supprime les alertes lues de plus de 30 jours
```

### Voir les jobs en attente

```bash
php artisan tinker
>>> DB::table('jobs')->count();
>>> DB::table('jobs')->get();
```

### Voir les jobs échoués

```bash
# Liste
php artisan queue:failed

# Réessayer un job
php artisan queue:retry {id}

# Réessayer tous
php artisan queue:retry all

# Supprimer tous les jobs échoués
php artisan queue:flush
```

### Logs

```bash
# Logs Laravel généraux
tail -f storage/logs/laravel.log

# Logs du scheduler
tail -f storage/logs/scheduler.log

# Filtrer les logs de vérification
tail -f storage/logs/laravel.log | grep "CheckBacklink"
```

---

## 📈 Statistiques et métriques

### Taux de disponibilité

Calculé pour chaque backlink dans la page show :
```
Taux = (Vérifications réussies / Total vérifications) × 100
```

### Alertes par type

Consultable dans `/alerts` avec filtres

### Dashboard

Affiche les 5 alertes les plus récentes avec :
- Icône du type
- Titre
- Projet
- Sévérité
- Date

---

## 🎯 Prochaines améliorations possibles

Les tâches suivantes n'ont pas encore été implémentées mais sont planifiées :

### ⏳ Tâche #5 : Améliorer extraction d'ancres avec détection de types

**Objectif :** Classifier les ancres automatiquement
- exact_match (ancre = mot-clé exact)
- partial_match (ancre contient le mot-clé)
- branded (nom de marque)
- url (ancre est une URL)
- generic ("cliquez ici", "en savoir plus")
- image (lien sur image avec alt text)

**Implémentation :**
- Ajouter colonne `anchor_type` dans table backlinks
- Créer méthode `detectAnchorType()` dans BacklinkCheckerService
- Afficher le type dans l'UI

### ⏳ Tâche #6 : Créer job d'extraction de métriques SEO

**Objectif :** Intégrer APIs SEO (Ahrefs, Moz, Majestic)

**Métriques à extraire :**
- Domain Rating (DR) / Domain Authority (DA)
- Trust Flow / Citation Flow
- Trafic organique
- Nombre de backlinks du domaine

**Implémentation :**
- Créer `ExtractSeoMetricsJob`
- Ajouter colonnes dans backlinks : `domain_rating`, `domain_authority`, etc.
- Créer wrappers pour APIs externes
- Afficher dans la page show du backlink

---

## 🎉 Conclusion

L'EPIC "Jobs de vérification et extraction des ancres" est **majoritairement complété** avec 8 tâches sur 10 terminées.

**Fonctionnalités opérationnelles :**
- ✅ Vérification automatique des backlinks (scheduler + queues)
- ✅ Système d'alertes complet (3 types, 4 niveaux)
- ✅ Historique détaillé des vérifications
- ✅ Vérification manuelle (commande + UI)
- ✅ Extraction et détection des ancres
- ✅ Détection des changements (ancre, rel, dofollow)
- ✅ Interface utilisateur complète pour les alertes

**Prêt pour la production :**
Le système est entièrement fonctionnel et peut être déployé en production avec la configuration des queues (Supervisor) et du scheduler (cron).

---

**Date de création :** 13 février 2026
**Dernière mise à jour :** 13 février 2026
**Version :** 1.0
