# BMAD Helpers v6

Collection de fonctions utilitaires réutilisables pour les workflows BMAD.

---

## Combined-Config-Load

**Usage:** Charger la configuration complète du projet (config.yaml + workflow status)

**Steps:**
1. Read `bmad/config.yaml`
2. Read workflow status file (path dans config: `bmm.workflow_status_file`)
3. Merge les deux configs en mémoire
4. Return config object

**Example:**
```yaml
# Résultat attendu
project_name: "Link Tracker"
project_level: 4
current_phase: 4
workflows_completed: ["brainstorm", "research", "product-brief", "prd", "tech-spec", "architecture", "sprint-planning"]
```

---

## Load-Sprint-Status

**Usage:** Charger le statut actuel du sprint

**Steps:**
1. Read config pour obtenir `bmm.sprint_status_file`
2. Read le fichier sprint status YAML
3. Return sprint status object

**Example:**
```yaml
sprint_number: 1
sprint_goal: "Foundation & Projects"
total_points: 40
completed_points: 0
stories:
  - id: "STORY-001"
    status: "not_started"
    points: 5
```

---

## Update-Sprint-Status

**Usage:** Mettre à jour le statut d'une story dans le sprint

**Parameters:**
- `story_id`: ID de la story (ex: "STORY-001")
- `new_status`: "not_started" | "in_progress" | "completed"
- `actual_points`: (optionnel) Points réels si différents de l'estimation

**Steps:**
1. Read sprint status file
2. Find story by ID
3. Update status + completion_date si completed
4. Update sprint completed_points si story completed
5. Write sprint status file

**Example:**
```yaml
# Avant
- id: "STORY-001"
  status: "in_progress"
  points: 5

# Après (status=completed)
- id: "STORY-001"
  status: "completed"
  points: 5
  actual_points: 5
  completion_date: "2026-02-10"
  completed_by: "Claude"
```

---

## Save-Output-Document

**Usage:** Sauvegarder un document de workflow dans le bon dossier

**Parameters:**
- `workflow_name`: Nom du workflow (ex: "prd", "architecture")
- `content`: Contenu du document
- `format`: "md" | "yaml" (default: "md")

**Steps:**
1. Read config pour obtenir `output_folder`
2. Generate filename: `{workflow_name}-{project_name}-{date}.{format}`
3. Write file to `{output_folder}/{filename}`
4. Return full path

**Example:**
```
Input:
  workflow_name: "prd"
  project_name: "Link Tracker"
  date: "2026-02-09"

Output:
  docs/prd-link-tracker-2026-02-09.md
```

---

## Create-Story-Document

**Usage:** Créer un fichier story individuel

**Parameters:**
- `story_id`: ID de la story (ex: "STORY-001")
- `story_data`: Object avec title, description, acceptance_criteria, etc.

**Steps:**
1. Read config pour obtenir `paths.stories`
2. Generate filename: `STORY-{id}.md`
3. Format content selon template story
4. Write to `{paths.stories}/STORY-{id}.md`

**Template:**
```markdown
# {story_id}: {title}

**Epic:** {epic}
**Priority:** {priority}
**Points:** {points}

## User Story

{user_story}

## Acceptance Criteria

{acceptance_criteria}

## Technical Notes

{technical_notes}

## Dependencies

{dependencies}

## Progress Tracking

**Status:** {status}
**Assigned to:** {assignee}
**Started:** {start_date}
**Completed:** {completion_date}

## Implementation Notes

{implementation_notes}
```

---

## Update-Workflow-Status

**Usage:** Marquer un workflow comme complété

**Parameters:**
- `workflow_name`: Nom du workflow (ex: "prd")
- `status`: "completed" | "in_progress" | "skipped"

**Steps:**
1. Read workflow status file
2. Find workflow entry
3. Update status + completion_date
4. Update current_phase si nécessaire
5. Write workflow status file

**Example:**
```yaml
# Avant
- name: "prd"
  status: "in_progress"
  started_at: "2026-02-09T20:00:00Z"

# Après
- name: "prd"
  status: "completed"
  started_at: "2026-02-09T20:00:00Z"
  completed_at: "2026-02-09T23:08:00Z"
```

---

## Get-Next-Story

**Usage:** Obtenir la prochaine story à implémenter

**Steps:**
1. Read sprint status
2. Filter stories where status != "completed"
3. Filter stories where dependencies are met
4. Sort by priority + story ID
5. Return first story

**Returns:**
```yaml
id: "STORY-002"
title: "Implement User Authentication"
status: "not_started"
points: 5
dependencies: ["STORY-001"]
```

---

## Validate-Acceptance-Criteria

**Usage:** Vérifier qu'un critère d'acceptance est rempli

**Parameters:**
- `story_id`: ID de la story
- `criterion_index`: Index du critère (0-based)
- `is_met`: boolean

**Steps:**
1. Read story document
2. Parse acceptance criteria
3. Mark criterion as ✓ or ✗
4. Update story document

---

## Generate-Sprint-Report

**Usage:** Générer un rapport de sprint

**Returns:**
```yaml
sprint: 1
goal: "Foundation & Projects"
total_stories: 9
completed_stories: 3
total_points: 40
completed_points: 15
velocity: 15
stories_completed:
  - STORY-001
  - STORY-002
  - STORY-003
stories_in_progress:
  - STORY-004
stories_blocked: []
```

---

## Check-Dependencies-Met

**Usage:** Vérifier si les dépendances d'une story sont satisfaites

**Parameters:**
- `story_id`: ID de la story à vérifier

**Steps:**
1. Read sprint status
2. Get story dependencies
3. Check if all dependency stories have status="completed"
4. Return boolean

**Example:**
```
Input: STORY-003 (depends on STORY-001, STORY-002)

Check:
  STORY-001: completed ✓
  STORY-002: in_progress ✗

Return: false (dependencies not met)
```

---

## Notes d'implémentation

Ces helpers doivent être utilisés par les agents BMAD pour :
- Éviter la duplication de code
- Garantir la cohérence des formats
- Simplifier la maintenance
- Accélérer le développement

Chaque workflow peut appeler ces helpers via des instructions comme :
```
Per helpers.md#Load-Sprint-Status, load the current sprint
```
