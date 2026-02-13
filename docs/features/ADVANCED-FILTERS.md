# Filtres Avancés - Backlinks Index

**Feature**: Système de filtrage avancé pour la page `/backlinks`
**Date**: 2026-02-13
**Status**: ✅ Implémenté

## Vue d'ensemble

Système complet de filtres et de tri pour la page d'index des backlinks, permettant aux utilisateurs de trouver rapidement les backlinks souhaités parmi une grande liste.

## Fonctionnalités implémentées

### 1. Recherche textuelle

**Champ**: Input texte libre
**Champs recherchés**:
- `source_url` (URL de la page source)
- `anchor_text` (Texte d'ancre du lien)
- `target_url` (URL cible du backlink)

**Comportement**: Recherche avec LIKE `%terme%` (insensible à la casse PostgreSQL/MySQL)

**Exemple**:
```
Recherche: "example"
Résultats: Tous les backlinks contenant "example" dans URL source, ancre ou URL cible
```

### 2. Filtres par critères

#### Statut
- **Options**: Tous / Actif / Perdu / Modifié
- **Valeurs**: `active`, `lost`, `changed`
- **Comportement**: Filtre exact sur le champ `status`

#### Projet
- **Type**: Dropdown des projets existants
- **Comportement**: Filtre sur `project_id`
- **Affichage**: Nom du projet

#### Niveau (Tier)
- **Options**: Tous / Tier 1 / Tier 2
- **Valeurs**: `tier1`, `tier2`
- **Description**:
  - Tier 1: Liens directs vers les projets
  - Tier 2: Liens vers d'autres backlinks

#### Type de réseau
- **Options**: Tous / Externe / Interne (PBN)
- **Valeurs**: `external`, `internal`
- **Description**:
  - Externe: Sites tiers (achetés/partenariats)
  - Interne: Réseau de sites propriétaires (PBN)

### 3. Tri des colonnes

**Colonnes triables**:
- URL Source (`source_url`)
- Tier (`tier_level`)
- Réseau (`spot_type`)
- Statut (`status`)

**Directions**:
- Ascendant (A→Z, 0→9)
- Descendant (Z→A, 9→0)

**Comportement**:
- Clic sur l'en-tête: Tri ascendant
- Re-clic: Inverse la direction
- Icône indique la direction active

**Tri par défaut**: `created_at DESC` (plus récents en premier)

### 4. Interface utilisateur

#### Badge de filtres actifs
```blade
Filtres [3 actif(s)]
```
- Affiche le nombre de filtres appliqués
- Badge coloré en indigo (#4F46E5)

#### Compteur de résultats
```
42 backlink(s) trouvé(s) (3 filtre(s) actif(s))
```
- Total de résultats après filtrage
- Indication du nombre de filtres appliqués

#### Bouton "Réinitialiser"
- Visible uniquement si des filtres sont actifs
- Redirige vers `/backlinks` sans paramètres
- Efface tous les filtres d'un coup

#### Icônes de tri
- ▲ Flèche haut: Tri ascendant actif
- ▼ Flèche bas: Tri descendant actif
- ⇅ Double flèche: Colonne non triée (hover)

## Architecture technique

### Controller

**Fichier**: `app/Http/Controllers/BacklinkController.php`

**Méthode**: `index(Request $request)`

**Logique de filtrage**:
```php
// Recherche textuelle
if ($request->filled('search')) {
    $query->where(function($q) use ($search) {
        $q->where('source_url', 'like', "%{$search}%")
          ->orWhere('anchor_text', 'like', "%{$search}%")
          ->orWhere('target_url', 'like', "%{$search}%");
    });
}

// Filtres simples
if ($request->filled('status')) {
    $query->where('status', $request->status);
}

// Tri
$query->orderBy($sortField, $sortDirection);
```

**Validation du tri**:
- Liste blanche de champs autorisés
- Direction validée (asc/desc)
- Fallback sur created_at si champ invalide

### Vue Blade

**Fichier**: `resources/views/pages/backlinks/index.blade.php`

**Structure**:
1. Header avec filtres
2. Barre de statistiques (résultats + filtres actifs)
3. Tableau avec en-têtes triables
4. Pagination Laravel

### Composant Sortable Header

**Fichier**: `resources/views/components/sortable-header.blade.php`

**Props**:
- `field`: Nom du champ DB
- `label`: Texte affiché

**Comportement**:
- Conserve tous les paramètres de filtrage dans l'URL
- Génère le lien avec direction inversée
- Affiche l'icône appropriée selon l'état

## Paramètres URL

### Format
```
/backlinks?search=example&status=active&project_id=1&tier_level=tier1&spot_type=external&sort=source_url&direction=asc
```

### Paramètres disponibles

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `search` | string | Recherche textuelle | `example` |
| `status` | string | Statut du backlink | `active`, `lost`, `changed` |
| `project_id` | int | ID du projet | `1`, `5` |
| `tier_level` | string | Niveau du lien | `tier1`, `tier2` |
| `spot_type` | string | Type de réseau | `external`, `internal` |
| `sort` | string | Champ de tri | `source_url`, `status`, etc. |
| `direction` | string | Direction du tri | `asc`, `desc` |
| `page` | int | Numéro de page | `1`, `2`, `3` |

### Persistance
- Tous les paramètres sont conservés dans l'URL
- Permet de partager un lien avec filtres actifs
- Pagination conserve les filtres via `withQueryString()`

## Tests

**Fichier**: `tests/Feature/BacklinkFilterTest.php`

**Scénarios testés** (10 tests):

1. ✅ Filtrage par recherche textuelle
2. ✅ Filtrage par statut
3. ✅ Filtrage par projet
4. ✅ Filtrage par tier level
5. ✅ Filtrage par spot type
6. ✅ Tri ascendant par source_url
7. ✅ Tri descendant par statut
8. ✅ Comptage des filtres actifs
9. ✅ Reset des filtres
10. ✅ Combinaison de plusieurs filtres

**Commande**:
```bash
php artisan test tests/Feature/BacklinkFilterTest.php
```

## Performance

### Optimisations

1. **Index DB**: Colonnes filtrées/triées ont des index
   - `status`
   - `project_id`
   - `tier_level`
   - `spot_type`
   - `created_at`

2. **Eager Loading**: Chargement anticipé des relations
   ```php
   Backlink::with('project')->latest()
   ```

3. **Pagination**: 15 items par page (configurable)

4. **Query Builder**: Utilisation du Query Builder Laravel (pas d'ORM lourd)

### Temps de réponse attendu

- Sans filtres: ~50ms (100 backlinks)
- Avec filtres: ~30ms (requête optimisée)
- Tri: Pas d'impact (index DB)

## Améliorations futures possibles

### Court terme
- [ ] Filtre par date de création (range picker)
- [ ] Filtre par dernière vérification
- [ ] Export CSV avec filtres appliqués

### Moyen terme
- [ ] Sauvegarde de filtres favoris
- [ ] Filtres prédéfinis ("Mes backlinks perdus cette semaine")
- [ ] Recherche full-text (PostgreSQL FTS)

### Long terme
- [ ] Filtres avancés avec opérateurs AND/OR
- [ ] Recherche par expressions régulières
- [ ] Interface de filtrage visuelle (drag & drop)

## Exemples d'utilisation

### Cas d'usage 1: Trouver backlinks perdus récemment
```
Filtres:
- Statut: Perdu
- Tri: created_at DESC

URL: /backlinks?status=lost&sort=created_at&direction=desc
```

### Cas d'usage 2: Backlinks Tier 2 d'un projet spécifique
```
Filtres:
- Projet: Mon Site
- Tier: Tier 2

URL: /backlinks?project_id=3&tier_level=tier2
```

### Cas d'usage 3: Tous les backlinks externes actifs
```
Filtres:
- Statut: Actif
- Type: Externe
- Tri: Prix (si disponible)

URL: /backlinks?status=active&spot_type=external
```

## Accessibilité

- Labels explicites sur tous les filtres
- Placeholder informatif sur la recherche
- Boutons avec texte clair
- Navigation au clavier possible
- Liens triables (pas de JavaScript requis)

## Compatibilité

- ✅ Desktop (Chrome, Firefox, Safari, Edge)
- ✅ Tablet
- ✅ Mobile (formulaires responsive)
- ✅ Sans JavaScript (filtres fonctionnent)
- ✅ JavaScript activé (amélioration progressive possible)

## Changelog

### v1.0.0 - 2026-02-13

**Ajouté**:
- Recherche textuelle sur 3 champs
- Filtres: statut, projet, tier, spot_type
- Tri cliquable sur 4 colonnes
- Compteur de résultats
- Badge filtres actifs
- Bouton réinitialiser
- Composant sortable-header
- 10 tests Feature

**Modifié**:
- Layout filtres (grille 4 colonnes)
- Pagination conserve query string
- Controller avec validation tri

**Documenté**:
- Guide utilisateur
- Architecture technique
- Exemples d'usage
