# BMAD Method v6 - Index Rapide

📚 **Guide de référence rapide** pour naviguer dans la structure BMAD.

---

## 🚀 Quick Links

| Besoin | Fichier |
|--------|---------|
| 📖 Documentation complète | [README.md](README.md) |
| ⚙️ Configuration projet | [config.yaml](config.yaml) |
| 🛠️ Fonctions utilitaires | [helpers.md](helpers.md) |
| 📋 Statut des workflows | [../docs/bmm-workflow-status.yaml](../docs/bmm-workflow-status.yaml) |
| 🏃 Statut du sprint | [../docs/sprint-status.yaml](../docs/sprint-status.yaml) |

---

## 🤖 Agents

| Agent | Fichier | Utilisation |
|-------|---------|-------------|
| **Sprint Planner** | [agents/sprint-planner.md](agents/sprint-planner.md) | Planifier les sprints |
| **Story Implementer** | [agents/story-implementer.md](agents/story-implementer.md) | Implémenter les stories |
| **Code Reviewer** | [agents/code-reviewer.md](agents/code-reviewer.md) | Review le code |
| **Story Validator** | [agents/story-validator.md](agents/story-validator.md) | Valider les stories |

---

## 📋 Workflows

| Workflow | Fichier | Phase | Durée |
|----------|---------|-------|-------|
| **PRD** | [workflows/prd.md](workflows/prd.md) | 2 - Planning | 4-8h |
| **Architecture** | [workflows/architecture.md](workflows/architecture.md) | 3 - Solutioning | 4-8h |
| **Sprint Planning** | [workflows/sprint-planning.md](workflows/sprint-planning.md) | 4 - Implementation | 2-4h |
| **Implementation** | [workflows/implementation.md](workflows/implementation.md) | 4 - Implementation | Variable |

---

## 📄 Templates

| Template | Fichier | Usage |
|----------|---------|-------|
| **User Story** | [templates/story.md](templates/story.md) | Créer une nouvelle story |

---

## 🎯 Par Cas d'Usage

### Je veux...

#### ...Démarrer un nouveau projet
1. 📖 Lire [README.md](README.md) → Section "Quick Start"
2. 📄 Lancer [workflows/prd.md](workflows/prd.md)
3. 🏗️ Lancer [workflows/architecture.md](workflows/architecture.md)
4. 📅 Lancer [workflows/sprint-planning.md](workflows/sprint-planning.md)

#### ...Implémenter une story
1. 💻 Utiliser [agents/story-implementer.md](agents/story-implementer.md)
2. 🔍 Utiliser [agents/code-reviewer.md](agents/code-reviewer.md)
3. ✅ Utiliser [agents/story-validator.md](agents/story-validator.md)

#### ...Planifier un sprint
1. 📅 Utiliser [workflows/sprint-planning.md](workflows/sprint-planning.md)
2. 🗓️ Ou utiliser [agents/sprint-planner.md](agents/sprint-planner.md)

#### ...Créer une nouvelle story
1. 📄 Copier [templates/story.md](templates/story.md)
2. Remplir les sections
3. Ajouter à `docs/sprint-status.yaml`

#### ...Utiliser un helper
1. 🛠️ Voir [helpers.md](helpers.md)
2. Appeler avec `Per helpers.md#{FunctionName}`

---

## 📊 État du Projet

### Workflows Complétés ✅
- ✅ PRD → `docs/prd-link-tracker-2026-02-09.md`
- ✅ Architecture → `docs/architecture-link-tracker-2026-02-09.md`
- ✅ Sprint Planning → `docs/sprint-01-plan.md`

### Phase Actuelle
🚀 **Phase 4: Implementation** (Sprint 1)

### Sprint Actuel
📋 **Sprint 1/6** - "Foundation & Projects"
- **Points committés:** 36
- **Capacité:** 40
- **Stories:** 9

---

## 🔧 Helpers Principaux

| Helper | Call | Usage |
|--------|------|-------|
| Charger config | `Per helpers.md#Combined-Config-Load` | Config complète |
| Charger sprint | `Per helpers.md#Load-Sprint-Status` | Statut sprint |
| Prochaine story | `Per helpers.md#Get-Next-Story` | Story à faire |
| Update status | `Per helpers.md#Update-Sprint-Status` | MAJ story |
| Check deps | `Per helpers.md#Check-Dependencies-Met` | Vérifier dépendances |
| Validate AC | `Per helpers.md#Validate-Acceptance-Criteria` | Valider critère |
| Save doc | `Per helpers.md#Save-Output-Document` | Sauvegarder doc |
| Report | `Per helpers.md#Generate-Sprint-Report` | Rapport sprint |

---

## 📁 Structure Complète

```
bmad/
│
├── 📖 README.md                     # Documentation complète
├── 📇 INDEX.md                      # Ce fichier (index rapide)
├── ⚙️ config.yaml                   # Configuration projet
├── 🛠️ helpers.md                    # Fonctions utilitaires
│
├── 🤖 agents/                       # Agents spécialisés
│   ├── sprint-planner.md            # Planification sprint
│   ├── story-implementer.md         # Implémentation stories
│   ├── code-reviewer.md             # Review code
│   └── story-validator.md           # Validation stories
│
├── 📋 workflows/                    # Workflows de développement
│   ├── prd.md                       # Product Requirements
│   ├── architecture.md              # Architecture système
│   ├── sprint-planning.md           # Planification sprint
│   └── implementation.md            # Implémentation story
│
└── 📄 templates/                    # Templates de documents
    └── story.md                     # Template user story
```

---

## 🎓 Apprentissage

### Nouveau sur BMAD ?
1. Commencez par [README.md](README.md) → Section "Introduction"
2. Regardez la structure dans [README.md](README.md) → Section "Structure"
3. Essayez un workflow simple: [workflows/sprint-planning.md](workflows/sprint-planning.md)

### Vous connaissez déjà BMAD ?
- Utilisez cet INDEX pour navigation rapide
- Référez-vous à [helpers.md](helpers.md) pour les fonctions
- Consultez les agents pour automatisation

---

## 💡 Tips

### Performance
- ⚡ Utilisez les agents pour les tâches répétitives
- ⚡ Appelez les helpers directement quand c'est simple
- ⚡ Gardez les workflows pour les processus complexes

### Organisation
- 📁 Tous les outputs vont dans `docs/`
- 📁 Stories individuelles dans `docs/stories/`
- 📁 Config et statuts en YAML pour faciliter le parsing

### Best Practices
- ✅ Mettez à jour `sprint-status.yaml` régulièrement
- ✅ Documentez les décisions importantes
- ✅ Faites des rétrospectives de sprint
- ✅ Utilisez les templates pour cohérence

---

## 📞 Besoin d'Aide ?

1. **Consulter la doc:** [README.md](README.md)
2. **Voir les helpers:** [helpers.md](helpers.md)
3. **Lire l'agent/workflow concerné**
4. **Vérifier les fichiers de statut:** `docs/bmm-workflow-status.yaml`, `docs/sprint-status.yaml`

---

## 🔄 Version

**BMAD Method:** v6.0
**Créé:** 2026-02-09
**Projet:** Link Tracker
**Status:** ✅ Opérationnel

---

**Navigation:**
- [⬆️ Retour au README](README.md)
- [⚙️ Configuration](config.yaml)
- [🛠️ Helpers](helpers.md)
- [📋 Statut Workflows](../docs/bmm-workflow-status.yaml)
- [🏃 Statut Sprint](../docs/sprint-status.yaml)
