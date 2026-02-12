# 🎨 Comment Voir le Nouveau Design

**Date:** 2026-02-12
**Status:** Preview disponible

---

## 🚀 Accès Rapide

### **Option 1 : Route Preview (Recommandé)**

Le nouveau design Blade est accessible via une route dédiée :

```
http://linktracker.test/dashboard-preview
```

**OU** (si vous utilisez `php artisan serve`) :

```
http://localhost:8000/dashboard-preview
```

---

## 📋 Étapes Complètes

### 1. **Vérifier que le serveur tourne**

**Avec Laravel Herd:**
```
L'app devrait être accessible automatiquement à:
http://linktracker.test
```

**Sans Herd (serveur manuel):**
```bash
cd app-laravel
php artisan serve
# Puis ouvrir http://localhost:8000
```

---

### 2. **Accéder à la Preview**

Ouvrir dans le navigateur :
```
http://linktracker.test/dashboard-preview
```

**Ce que vous devriez voir :**
- ✅ Sidebar navigation (gauche, 256px)
- ✅ Topbar avec breadcrumb "Dashboard"
- ✅ Page header "Dashboard"
- ✅ 3 stats cards (Backlinks actifs: 0, Perdus: 0, Projets: 0)
- ✅ Section "Alertes récentes" (vide avec message)
- ✅ Section "Projets récents" (vide avec message)

---

### 3. **Tester la Sidebar Navigation**

Cliquer sur les liens de la sidebar :
- 📊 Dashboard → `/dashboard` (route n'existe pas encore, erreur 404 normale)
- 📁 Projets → `/projects` (route Vue.js existante)
- 🔗 Backlinks → `/backlinks` (route n'existe pas encore)
- 🔔 Alertes → `/alerts` (route n'existe pas encore)
- 🛒 Commandes → `/orders` (route n'existe pas encore)
- ⚙️ Paramètres → `/settings` (route n'existe pas encore)

**Note:** Seul `/dashboard-preview` fonctionne pour l'instant. Les autres routes seront créées dans les prochains sprints.

---

### 4. **Tester Responsive**

**Desktop (>1024px):**
- Sidebar visible et fixe
- Content area décalé de 256px

**Mobile/Tablet (<1024px):**
- Sidebar cachée (sera un drawer dans STORY-027)
- Content area pleine largeur

**Test:**
1. Resize la fenêtre du navigateur
2. Vérifier que le layout s'adapte

---

## 🎨 Comparaison Ancien/Nouveau

### **Ancien Design (Vue.js SPA)**

Accessible à toutes les routes actuelles :
```
http://linktracker.test/          (Home)
http://linktracker.test/login     (Login)
http://linktracker.test/projects  (Projects List)
```

**Caractéristiques:**
- Layout centré (landing page style)
- Navigation par boutons éparpillés
- Pas de sidebar
- Pas de breadcrumb

---

### **Nouveau Design (Blade Layout)**

Accessible uniquement via :
```
http://linktracker.test/dashboard-preview
```

**Caractéristiques:**
- Layout SaaS avec sidebar fixe
- Navigation persistante (toujours visible)
- Breadcrumb contextuel
- Palette minimale (4 couleurs)
- Stats cards cohérentes

---

## 🔧 Dépannage

### Problème 1 : Page blanche

**Solution:**
```bash
cd app-laravel
php artisan view:clear
php artisan config:clear
npm run build
```

---

### Problème 2 : CSS non chargé

**Solution:**
```bash
cd app-laravel
npm run build
# Puis rafraîchir le navigateur (Ctrl+F5)
```

---

### Problème 3 : 404 Not Found

**Vérifier:**
1. URL correcte : `/dashboard-preview` (pas `/dashboard`)
2. Serveur tourne
3. Route ajoutée dans `routes/web.php`

**Clear routes:**
```bash
php artisan route:clear
```

---

### Problème 4 : Erreur "View not found"

**Vérifier que le fichier existe:**
```
app-laravel/resources/views/pages/dashboard.blade.php
```

**Clear views:**
```bash
php artisan view:clear
```

---

## 📊 Données Affichées

### **Stats Cards**

Pour l'instant, toutes les valeurs sont à **0** (placeholders) :

```php
// DashboardController.php
$activeBacklinks = 0;  // TODO: Backlink::where('status', 'active')->count()
$lostBacklinks = 0;    // TODO: Backlink::where('status', 'lost')->count()
$totalProjects = 0;    // TODO: Project::count()
```

**Ces valeurs seront remplacées** quand les models et données seront disponibles.

---

### **Sections Vides**

Les sections suivantes affichent des messages "Aucun..." :

- **Alertes récentes** → "Aucune alerte récente"
- **Projets récents** → "Aucun projet configuré"

**Ces sections seront peuplées** quand les EPICs correspondants seront complétés :
- EPIC-004 : Alertes
- EPIC-002 : Projets (déjà partiellement fait)

---

## 🧪 Tester les Composants

Tous les composants sont visibles sur `/dashboard-preview` :

### **Composants affichés:**

1. ✅ **Layout** (`layouts/app.blade.php`)
2. ✅ **Sidebar** (`components/sidebar.blade.php`)
3. ✅ **Topbar** (`components/topbar.blade.php`)
4. ✅ **Page Header** (`components/page-header.blade.php`)
5. ✅ **Stats Card** (`components/stats-card.blade.php`) x3
6. ✅ **Button** (`components/button.blade.php`)

### **Composants non affichés (mais disponibles):**

- **Badge** (`components/badge.blade.php`) - Sera utilisé dans tables
- **Table** (`components/table.blade.php`) - Sera utilisé pour listes
- **Alert** (`components/alert.blade.php`) - Sera utilisé pour messages
- **Form Input** (`components/form-input.blade.php`) - Sera utilisé dans formulaires

---

## 📱 Test Mobile

### **Navigateurs recommandés:**

- Chrome (F12 → Device Toolbar)
- Firefox (Ctrl+Shift+M)
- Safari (Develop → Responsive Design Mode)

### **Résolutions à tester:**

- **Mobile:** 375px (iPhone SE)
- **Tablet:** 768px (iPad)
- **Desktop:** 1440px (Desktop standard)

### **Comportements attendus:**

| Taille | Sidebar | Content | Topbar Stats |
|--------|---------|---------|--------------|
| <1024px | Cachée* | 100% width | Cachés |
| ≥1024px | Visible | Décalé 256px | Visibles |

*Note: Drawer mobile sera ajouté dans STORY-027

---

## 🎯 Prochaines Étapes

### **Pour utiliser le nouveau design partout:**

**Phase 2 (Sprint 4):**
- Implémenter mobile drawer (STORY-027)

**Phase 3 (Sprint 5):**
- Migrer `/projects` vers Blade (STORY-024)
- Migrer backlinks vers Blade (STORY-025)

**Phase 4 (Sprint 6):**
- Supprimer ancien code Vue.js dupliqué
- Remplacer route catch-all par Blade

---

## ✅ Checklist Validation

Cocher si vous voyez :

- [ ] Sidebar navigation visible (desktop)
- [ ] Breadcrumb "Dashboard" visible
- [ ] Page header "Dashboard" avec "Vue d'ensemble"
- [ ] 3 stats cards (valeurs à 0)
- [ ] Bouton "+ Nouveau projet" (bleu)
- [ ] Section "Alertes récentes" (vide)
- [ ] Section "Projets récents" (vide avec bouton "Créer")
- [ ] Palette de couleurs cohérente (Neutral/Bleu/Vert/Rouge)
- [ ] Responsive fonctionne (resize fenêtre)

---

## 📚 Documentation Complète

Pour plus de détails :

- **Composants:** `docs/design-system/COMPONENT-LIBRARY.md`
- **Architecture:** `docs/design-system/UI-REDESIGN-PROPOSAL.md`
- **Status:** `docs/design-system/IMPLEMENTATION-STATUS.md`
- **Mockup:** `docs/design-system/VISUAL-MOCKUP.html` (ouvrir dans navigateur)

---

## 🐛 Signaler un Problème

Si quelque chose ne fonctionne pas :

1. Vérifier section Dépannage ci-dessus
2. Clear tous les caches Laravel
3. Rebuild assets (npm run build)
4. Vérifier console navigateur (F12)

---

**Créé le:** 2026-02-12
**Dernière mise à jour:** 2026-02-12
**Route preview:** `/dashboard-preview`
