# Product Requirements Document: Link Tracker

**Date:** 2026-02-09
**Author:** axel
**Version:** 1.0
**Project Type:** web-app
**Project Level:** 4 (Enterprise - 40+ stories)
**Status:** Draft

---

## Document Overview

This Product Requirements Document (PRD) defines the functional and non-functional requirements for Link Tracker. It serves as the source of truth for what will be built and provides traceability from requirements through implementation.

**Related Documents:**
- Product Brief: N/A (PRD created directly from technical plan)
- Technical Plan: `~/.claude/plans/spicy-juggling-toucan.md`

---

## Executive Summary

**Link Tracker** est une application web SEO conçue pour automatiser le monitoring et la gestion des backlinks. L'application permet de surveiller automatiquement l'état de centaines de backlinks, détecter les changements critiques (perte, passage en nofollow), enrichir les données avec des métriques SEO tierces, et commander de nouveaux backlinks directement via des plateformes intégrées.

**Proposition de valeur :** Centraliser toute l'activité backlink en un seul endroit - monitoring, alertes, métriques SEO, et achat - pour gagner du temps et réagir rapidement aux problèmes.

**Cible :** Consultants SEO, propriétaires de sites e-commerce, et professionnels du marketing digital gérant activement leur profil de backlinks.

---

## Product Goals

### Business Objectives

1. **Automatiser le monitoring des backlinks** : Éliminer la vérification manuelle chronophage en vérifiant automatiquement l'état de tous les backlinks toutes les 4 heures

2. **Détecter rapidement les problèmes** : Alerter immédiatement l'utilisateur quand un backlink disparaît ou change (passage en nofollow, modification d'anchor text)

3. **Centraliser les données SEO** : Agréger les métriques SEO de multiples sources (Moz, Ahrefs, etc.) pour évaluer la qualité des domaines sources en un coup d'œil

4. **Simplifier l'achat de backlinks** : Permettre la commande de backlinks directement depuis l'application via APIs des plateformes tierces, avec suivi automatique

5. **Fournir des insights actionnables** : Visualiser l'évolution du profil de backlinks via dashboard et graphiques pour identifier tendances et opportunités

### Success Metrics

- **Gain de temps** : Réduction de 90% du temps passé à vérifier manuellement les backlinks (de 2h/semaine à 10min/semaine)
- **Réactivité** : Détection des backlinks perdus en moins de 4 heures (vs plusieurs jours en vérification manuelle)
- **Couverture** : Support de 1000+ backlinks par projet sans dégradation de performance
- **Fiabilité** : 99% uptime avec vérifications automatiques sans intervention manuelle
- **Centralisation** : 100% des activités backlink gérées depuis une seule interface (monitoring + achat)

---

## Functional Requirements

Functional Requirements (FRs) define **what** the system does - specific features and behaviors.

Each requirement includes:
- **ID**: Unique identifier (FR-001, FR-002, etc.)
- **Priority**: Must Have / Should Have / Could Have (MoSCoW)
- **Description**: What the system should do
- **Acceptance Criteria**: How to verify it's complete

---

### Domaine 1 : Gestion des Projets

#### FR-001 : Créer un projet de monitoring

**Priority:** Must Have

**Description:**
L'utilisateur peut créer un nouveau projet en spécifiant un nom et un domaine cible à monitorer.

**Acceptance Criteria:**
- [ ] Le formulaire accepte un nom de projet (texte, max 255 caractères)
- [ ] Le formulaire accepte un domaine (URL valide)
- [ ] Le système valide le format de l'URL avant sauvegarde
- [ ] Le projet est sauvegardé en base de données

**Dependencies:** Aucune

---

#### FR-002 : Lister tous les projets

**Priority:** Must Have

**Description:**
L'utilisateur peut voir la liste de tous ses projets avec informations clés.

**Acceptance Criteria:**
- [ ] La liste affiche nom, domaine, nombre de backlinks, date de création
- [ ] Les projets sont triables par date de création
- [ ] Chaque projet a un lien vers sa page détail

**Dependencies:** FR-001

---

#### FR-003 : Modifier un projet

**Priority:** Should Have

**Description:**
L'utilisateur peut éditer le nom et le domaine d'un projet existant.

**Acceptance Criteria:**
- [ ] Le formulaire pré-remplit les données actuelles
- [ ] Les modifications sont sauvegardées
- [ ] Le domaine modifié est re-validé

**Dependencies:** FR-001

---

#### FR-004 : Supprimer un projet

**Priority:** Should Have

**Description:**
L'utilisateur peut supprimer un projet et toutes ses données associées.

**Acceptance Criteria:**
- [ ] Une confirmation est demandée avant suppression
- [ ] La suppression en cascade inclut backlinks, checks, et alertes
- [ ] L'utilisateur reçoit une confirmation de suppression

**Dependencies:** FR-001

---

### Domaine 2 : Gestion des Backlinks

#### FR-005 : Ajouter un backlink manuellement

**Priority:** Must Have

**Description:**
L'utilisateur peut ajouter manuellement un backlink à surveiller.

**Acceptance Criteria:**
- [ ] Le formulaire accepte : source_url, target_url, anchor_text
- [ ] Les URLs sont validées avant sauvegarde
- [ ] Le backlink est associé au projet sélectionné
- [ ] Le système enregistre discovered_at et first_seen_at

**Dependencies:** FR-001

---

#### FR-006 : Lister les backlinks d'un projet

**Priority:** Must Have

**Description:**
L'utilisateur peut voir tous les backlinks d'un projet avec leur statut.

**Acceptance Criteria:**
- [ ] La liste affiche : source_url, target_url, anchor_text, status, last_checked_at
- [ ] Les backlinks sont filtrables par statut (active/lost/changed)
- [ ] Les backlinks sont triables par date de découverte
- [ ] Pagination de 50 items par page

**Dependencies:** FR-005

---

#### FR-007 : Voir le détail d'un backlink

**Priority:** Must Have

**Description:**
L'utilisateur peut voir l'historique complet d'un backlink.

**Acceptance Criteria:**
- [ ] Affiche toutes les informations du backlink
- [ ] Affiche l'historique des checks chronologique
- [ ] Affiche les alertes associées
- [ ] Affiche les métriques SEO du domaine source

**Dependencies:** FR-005

---

#### FR-008 : Modifier un backlink

**Priority:** Should Have

**Description:**
L'utilisateur peut modifier les informations d'un backlink.

**Acceptance Criteria:**
- [ ] Permet de modifier anchor_text, target_url
- [ ] Permet de modifier manuellement le statut
- [ ] Les modifications sont horodatées

**Dependencies:** FR-005

---

#### FR-009 : Supprimer un backlink

**Priority:** Should Have

**Description:**
L'utilisateur peut supprimer un backlink du monitoring.

**Acceptance Criteria:**
- [ ] Confirmation avant suppression
- [ ] Suppression en cascade des checks et alertes
- [ ] Le backlink est retiré de la liste

**Dependencies:** FR-005

---

#### FR-010 : Importer des backlinks en masse

**Priority:** Could Have

**Description:**
L'utilisateur peut importer une liste de backlinks via fichier CSV.

**Acceptance Criteria:**
- [ ] Accepte un fichier CSV avec colonnes : source_url, target_url, anchor_text
- [ ] Valide chaque ligne avant import
- [ ] Affiche un rapport d'import (réussis/échoués)
- [ ] Limite de 1000 backlinks par import

**Dependencies:** FR-001

---

### Domaine 3 : Monitoring Automatique

#### FR-011 : Vérifier automatiquement un backlink

**Priority:** Must Have

**Description:**
Le système vérifie automatiquement la présence et l'état d'un backlink.

**Acceptance Criteria:**
- [ ] Effectue une requête HTTP vers source_url
- [ ] Enregistre le http_status (200, 404, 500, etc.)
- [ ] Parse le HTML pour détecter la présence du lien vers target_url
- [ ] Extrait les attributs rel (follow/nofollow)
- [ ] Extrait l'anchor text actuel
- [ ] Enregistre response_time
- [ ] Crée un enregistrement BacklinkCheck

**Dependencies:** FR-005

---

#### FR-012 : Détecter les changements de backlink

**Priority:** Must Have

**Description:**
Le système détecte et signale les changements d'état d'un backlink.

**Acceptance Criteria:**
- [ ] Compare le check actuel avec le précédent
- [ ] Détecte si le lien a disparu (is_present = false)
- [ ] Détecte si le statut HTTP a changé
- [ ] Détecte si les attributs rel ont changé (dofollow → nofollow)
- [ ] Détecte si l'anchor text a changé
- [ ] Crée une Alert pour chaque type de changement

**Dependencies:** FR-011

---

#### FR-013 : Mettre à jour le statut du backlink

**Priority:** Must Have

**Description:**
Le système met à jour automatiquement le statut du backlink selon les vérifications.

**Acceptance Criteria:**
- [ ] Statut = "active" si le lien est présent et valide
- [ ] Statut = "lost" si le lien a disparu ou HTTP 404
- [ ] Statut = "changed" si attributs rel ou anchor modifiés
- [ ] Mise à jour de last_checked_at à chaque vérification

**Dependencies:** FR-011, FR-012

---

#### FR-014 : Programmer des vérifications périodiques

**Priority:** Must Have

**Description:**
Le système vérifie automatiquement tous les backlinks selon un planning.

**Acceptance Criteria:**
- [ ] Vérification de tous les backlinks toutes les 4 heures
- [ ] Utilisation d'une queue pour distribuer la charge
- [ ] Respect des délais entre requêtes (throttling)
- [ ] Logs des vérifications dans Laravel Horizon

**Dependencies:** FR-011

---

#### FR-015 : Déclencher une vérification manuelle

**Priority:** Should Have

**Description:**
L'utilisateur peut déclencher manuellement la vérification d'un backlink.

**Acceptance Criteria:**
- [ ] Bouton "Vérifier maintenant" sur la page du backlink
- [ ] Le job est ajouté à la queue avec priorité haute
- [ ] L'utilisateur reçoit une notification quand c'est terminé
- [ ] Les résultats sont affichés immédiatement

**Dependencies:** FR-011

---

### Domaine 4 : Alertes et Notifications

#### FR-016 : Créer des alertes automatiques

**Priority:** Must Have

**Description:**
Le système crée automatiquement des alertes lors de changements détectés.

**Acceptance Criteria:**
- [ ] Alerte "lost" quand un backlink disparaît
- [ ] Alerte "nofollow" quand un lien passe en nofollow
- [ ] Alerte "status_change" pour changements HTTP status
- [ ] Enregistre old_value et new_value
- [ ] Horodate avec detected_at

**Dependencies:** FR-012

---

#### FR-017 : Lister les alertes

**Priority:** Must Have

**Description:**
L'utilisateur peut voir toutes ses alertes avec filtres.

**Acceptance Criteria:**
- [ ] Liste toutes les alertes non lues en premier
- [ ] Filtrable par type (lost/nofollow/status_change)
- [ ] Filtrable par projet
- [ ] Tri par date décroissante
- [ ] Indique visuellement les alertes non lues

**Dependencies:** FR-016

---

#### FR-018 : Marquer une alerte comme lue

**Priority:** Must Have

**Description:**
L'utilisateur peut marquer une alerte comme lue.

**Acceptance Criteria:**
- [ ] Bouton "Marquer comme lu" sur chaque alerte
- [ ] Le champ is_read passe à true
- [ ] L'alerte disparaît de la liste "non lues"

**Dependencies:** FR-017

---

#### FR-019 : Notification par email

**Priority:** Could Have

**Description:**
L'utilisateur reçoit un email pour les alertes critiques.

**Acceptance Criteria:**
- [ ] Email envoyé pour les alertes "lost"
- [ ] Email contient : projet, backlink concerné, type d'alerte
- [ ] Lien direct vers le backlink dans l'email
- [ ] Option pour désactiver les notifications

**Dependencies:** FR-016

---

### Domaine 5 : Métriques SEO

#### FR-020 : Récupérer les métriques SEO d'un domaine

**Priority:** Must Have

**Description:**
Le système récupère les métriques SEO des domaines sources via APIs.

**Acceptance Criteria:**
- [ ] Appelle les APIs SEO (Moz, Ahrefs, ou custom)
- [ ] Récupère Domain Authority, Trust Flow, Citation Flow, Spam Score
- [ ] Enregistre les métriques en base de données
- [ ] Associe les métriques au domaine source
- [ ] Horodate avec last_updated_at

**Dependencies:** FR-005

---

#### FR-021 : Afficher les métriques SEO

**Priority:** Must Have

**Description:**
L'utilisateur peut voir les métriques SEO des domaines sources.

**Acceptance Criteria:**
- [ ] Affichage des métriques sur la page détail du backlink
- [ ] Affichage dans la liste des backlinks (optionnel)
- [ ] Indicateur visuel de la qualité (vert/orange/rouge)
- [ ] Date de dernière mise à jour affichée

**Dependencies:** FR-020

---

#### FR-022 : Mettre à jour les métriques SEO quotidiennement

**Priority:** Should Have

**Description:**
Le système met à jour les métriques SEO chaque jour.

**Acceptance Criteria:**
- [ ] Job planifié quotidiennement à 2h du matin
- [ ] Mise à jour de tous les domaines uniques
- [ ] Respect des rate limits des APIs
- [ ] Logs des mises à jour

**Dependencies:** FR-020

---

#### FR-023 : Configurer les providers SEO

**Priority:** Should Have

**Description:**
L'utilisateur peut configurer les APIs SEO à utiliser.

**Acceptance Criteria:**
- [ ] Interface de configuration des API keys
- [ ] Activation/désactivation par provider
- [ ] Test de connexion API
- [ ] Affichage des quotas restants

**Dependencies:** FR-020

---

### Domaine 6 : Commande de Backlinks

#### FR-024 : Passer une commande de backlink

**Priority:** Must Have

**Description:**
L'utilisateur peut commander un backlink via une plateforme intégrée.

**Acceptance Criteria:**
- [ ] Formulaire avec : target_url, anchor_text, plateforme
- [ ] Sélection du domaine souhaité (optionnel)
- [ ] Affichage du prix estimé
- [ ] Appel API vers la plateforme (NextLevel, SEOClerks, etc.)
- [ ] Enregistrement de la commande en base
- [ ] Statut initial = "pending"

**Dependencies:** FR-001

---

#### FR-025 : Suivre le statut d'une commande

**Priority:** Must Have

**Description:**
L'utilisateur peut suivre l'état de ses commandes de backlinks.

**Acceptance Criteria:**
- [ ] Liste de toutes les commandes avec statuts
- [ ] Statuts possibles : pending, approved, live, rejected
- [ ] Affichage de placed_at et live_at
- [ ] Lien vers le backlink une fois live

**Dependencies:** FR-024

---

#### FR-026 : Synchroniser les statuts de commandes

**Priority:** Should Have

**Description:**
Le système met à jour automatiquement les statuts via webhooks ou polling.

**Acceptance Criteria:**
- [ ] Webhooks pour mises à jour en temps réel
- [ ] Polling quotidien pour plateformes sans webhooks
- [ ] Mise à jour du statut en base
- [ ] Notification à l'utilisateur quand statut = "live"

**Dependencies:** FR-024

---

#### FR-027 : Ajouter automatiquement un backlink commandé

**Priority:** Should Have

**Description:**
Quand une commande passe à "live", le système l'ajoute au monitoring.

**Acceptance Criteria:**
- [ ] Création automatique d'un Backlink quand statut = "live"
- [ ] Association au projet correct
- [ ] Première vérification déclenchée automatiquement
- [ ] Lien entre BacklinkOrder et Backlink

**Dependencies:** FR-024, FR-026

---

### Domaine 7 : Dashboard et Rapports

#### FR-028 : Afficher le tableau de bord global

**Priority:** Must Have

**Description:**
L'utilisateur accède à un dashboard avec vue d'ensemble.

**Acceptance Criteria:**
- [ ] Cards avec statistiques : total backlinks, actifs, perdus, en attente
- [ ] Graphique d'évolution des backlinks dans le temps
- [ ] Liste des alertes récentes (5 dernières)
- [ ] Liste des dernières commandes

**Dependencies:** FR-006, FR-017, FR-025

---

#### FR-029 : Visualiser l'évolution des backlinks

**Priority:** Must Have

**Description:**
L'utilisateur peut voir l'évolution des backlinks sur un graphique.

**Acceptance Criteria:**
- [ ] Graphique ligne montrant actifs/perdus dans le temps
- [ ] Filtrable par période (7j, 30j, 90j, 1an)
- [ ] Filtrable par projet
- [ ] Données agrégées par jour

**Dependencies:** FR-006

---

#### FR-030 : Exporter un rapport

**Priority:** Could Have

**Description:**
L'utilisateur peut exporter un rapport de ses backlinks.

**Acceptance Criteria:**
- [ ] Export au format CSV
- [ ] Export au format PDF
- [ ] Inclut tous les backlinks avec statuts
- [ ] Inclut les métriques SEO
- [ ] Filtrable par projet et période

**Dependencies:** FR-006, FR-021

---

### Domaine 8 : Authentification et Configuration

#### FR-031 : Authentification utilisateur (simplifié)

**Priority:** Should Have

**Description:**
Protection basique de l'application (usage personnel).

**Acceptance Criteria:**
- [ ] Page de login avec email/password
- [ ] Session persistante
- [ ] Logout fonctionnel
- [ ] Redirection si non authentifié

**Dependencies:** Aucune

---

#### FR-032 : Configuration globale

**Priority:** Should Have

**Description:**
L'utilisateur peut configurer les paramètres globaux de l'application.

**Acceptance Criteria:**
- [ ] Fréquence de vérification des backlinks (réglable)
- [ ] Activation/désactivation des notifications email
- [ ] Configuration des API keys (Moz, Ahrefs, plateformes)
- [ ] Timeout des requêtes HTTP

**Dependencies:** Aucune

---

## Non-Functional Requirements

Non-Functional Requirements (NFRs) define **how** the system performs - quality attributes and constraints.

---

### NFR-001 : Performance - Temps de Réponse des Pages

**Priority:** Must Have

**Description:**
Les pages de l'application doivent se charger rapidement pour garantir une bonne expérience utilisateur.

**Acceptance Criteria:**
- [ ] Temps de chargement initial du dashboard < 2 secondes
- [ ] Temps de réponse des API REST < 500ms pour 95% des requêtes
- [ ] Temps de chargement de la liste des backlinks (50 items) < 1 seconde
- [ ] Temps de rendu des graphiques < 1.5 secondes

**Rationale:**
Pour un usage quotidien intensif, les temps de réponse rapides sont essentiels à la productivité de l'utilisateur.

---

### NFR-002 : Performance - Traitement des Jobs de Monitoring

**Priority:** Must Have

**Description:**
Le système doit pouvoir traiter efficacement les vérifications de backlinks en arrière-plan.

**Acceptance Criteria:**
- [ ] Vérification d'un backlink individuel < 10 secondes (incluant requête HTTP + parsing)
- [ ] Traitement concurrent de 10 backlinks simultanément minimum
- [ ] Queue workers configurés pour traiter 100 jobs/minute minimum
- [ ] Timeout des requêtes HTTP externes configuré à 30 secondes maximum

**Rationale:**
Avec potentiellement des centaines de backlinks à vérifier toutes les 4 heures, le traitement parallèle efficace est critique.

---

### NFR-003 : Sécurité - Protection des Données

**Priority:** Must Have

**Description:**
Les données sensibles (API keys, credentials) doivent être stockées et transmises de manière sécurisée.

**Acceptance Criteria:**
- [ ] Toutes les API keys stockées dans .env (jamais en base de données en clair)
- [ ] Connexion HTTPS obligatoire en production
- [ ] Cookies de session avec flags HttpOnly et Secure
- [ ] Tokens CSRF activés sur tous les formulaires
- [ ] Sanitization de toutes les URLs avant stockage (prévention XSS)

**Rationale:**
Les API keys SEO et des plateformes de backlinks ont une valeur commerciale et doivent être protégées.

---

### NFR-004 : Sécurité - Validation et Protection SSRF

**Priority:** Must Have

**Description:**
Le système doit se protéger contre les attaques SSRF (Server-Side Request Forgery) lors des vérifications de backlinks.

**Acceptance Criteria:**
- [ ] Validation stricte des URLs (regex, format)
- [ ] Blocage des URLs localhost, 127.0.0.1, et plages IP privées
- [ ] Blocage des protocols autres que http/https
- [ ] Rate limiting sur les endpoints API (60 requêtes/minute par IP)
- [ ] Logs de toutes les tentatives de requêtes bloquées

**Rationale:**
Accepter des URLs arbitraires pour vérification expose à des risques SSRF permettant de scanner le réseau interne.

---

### NFR-005 : Scalabilité - Capacité de Stockage

**Priority:** Should Have

**Description:**
Le système doit pouvoir gérer une croissance importante du volume de données.

**Acceptance Criteria:**
- [ ] Support de 10 projets simultanés minimum
- [ ] Support de 1000 backlinks par projet minimum
- [ ] Support de 100 000 BacklinkChecks historiques minimum
- [ ] Indexation PostgreSQL sur backlinks.project_id, backlinks.status, backlink_checks.backlink_id
- [ ] Pagination systématique des listes > 50 items

**Rationale:**
Avec des vérifications toutes les 4 heures, l'historique s'accumule rapidement. 1000 backlinks × 6 checks/jour × 365 jours = 2.19M checks/an.

---

### NFR-006 : Scalabilité - Gestion de la Queue

**Priority:** Must Have

**Description:**
Le système de queues doit gérer efficacement les pics de charge.

**Acceptance Criteria:**
- [ ] Utilisation de Laravel Horizon pour monitoring en temps réel
- [ ] Configuration de 3 niveaux de priorité (high, default, low)
- [ ] Vérifications manuelles en priorité haute
- [ ] Auto-scaling des workers selon la charge (2-5 workers)
- [ ] Retry automatique en cas d'échec (3 tentatives avec backoff exponentiel)

**Rationale:**
Les vérifications planifiées et manuelles doivent cohabiter sans se bloquer mutuellement.

---

### NFR-007 : Fiabilité - Disponibilité du Service

**Priority:** Should Have

**Description:**
L'application doit être disponible et stable pour un usage quotidien.

**Acceptance Criteria:**
- [ ] Uptime cible de 99% (environ 7h de downtime/mois maximum)
- [ ] Gestion gracieuse des erreurs (pages d'erreur personnalisées)
- [ ] Logs structurés de toutes les erreurs (Laravel log)
- [ ] Monitoring des queues (alertes si workers down)

**Rationale:**
Pour un usage personnel, 99% est suffisant. Une solution enterprise nécessiterait 99.9%.

---

### NFR-008 : Fiabilité - Gestion des Erreurs Externes

**Priority:** Must Have

**Description:**
Le système doit gérer gracieusement les échecs des APIs et services externes.

**Acceptance Criteria:**
- [ ] Retry automatique sur erreur HTTP 5xx (3 tentatives)
- [ ] Timeout sur requêtes externes (30 secondes max)
- [ ] Fallback si API SEO indisponible (pas de blocage de l'app)
- [ ] Cache des métriques SEO (TTL 24h) pour limiter les appels API
- [ ] Logs détaillés des erreurs API avec context

**Rationale:**
Les APIs tierces (Moz, Ahrefs, plateformes) peuvent être instables ou atteindre des quotas. L'app ne doit pas crasher.

---

### NFR-009 : Maintenabilité - Qualité du Code

**Priority:** Should Have

**Description:**
Le code doit être maintenable, lisible et suivre les standards Laravel.

**Acceptance Criteria:**
- [ ] Respect des conventions Laravel (PSR-12 pour PHP)
- [ ] Architecture Service/Repository pour la logique métier
- [ ] Utilisation d'Eloquent ORM pour toutes les requêtes DB
- [ ] Configuration via .env (jamais de credentials en dur)
- [ ] Code commenté pour la logique complexe (parsing HTML, algorithmes)

**Rationale:**
Un code maintenable facilite les évolutions futures (ajout de nouveaux providers, features).

---

### NFR-010 : Maintenabilité - Tests Automatisés

**Priority:** Should Have

**Description:**
Les fonctionnalités critiques doivent être couvertes par des tests automatisés.

**Acceptance Criteria:**
- [ ] Tests unitaires pour les Services (BacklinkChecker, BacklinkAnalyzer)
- [ ] Tests de feature pour les endpoints API critiques (CRUD projets, backlinks)
- [ ] Tests de job pour CheckBacklink et MonitorProjectBacklinks
- [ ] Couverture de tests > 60% pour les Services et Jobs
- [ ] Tests exécutables via `php artisan test`

**Rationale:**
Les tests automatisés évitent les régressions lors de l'ajout de nouveaux providers ou fonctionnalités.

---

### NFR-011 : Compatibilité - Navigateurs et Devices

**Priority:** Should Have

**Description:**
L'interface doit fonctionner sur les navigateurs modernes et être responsive.

**Acceptance Criteria:**
- [ ] Support des navigateurs : Chrome (dernières 2 versions), Firefox (dernières 2 versions), Edge, Safari
- [ ] Design responsive (desktop, tablette, mobile)
- [ ] Breakpoints Tailwind : sm (640px), md (768px), lg (1024px), xl (1280px)
- [ ] Tableaux avec scroll horizontal sur mobile
- [ ] Graphiques adaptatifs selon la taille d'écran

**Rationale:**
L'utilisateur peut consulter ses backlinks depuis différents devices (bureau, mobile).

---

### NFR-012 : Compatibilité - Base de Données

**Priority:** Must Have

**Description:**
L'application doit être compatible avec PostgreSQL 15+.

**Acceptance Criteria:**
- [ ] Migrations Laravel compatibles PostgreSQL
- [ ] Utilisation de types PostgreSQL (JSON pour platform_response)
- [ ] Support des transactions pour opérations critiques
- [ ] Support des index composés pour performance

**Rationale:**
PostgreSQL est choisi pour sa robustesse et ses fonctionnalités avancées (JSON, performances sur gros volumes).

---

## Epics

Epics are logical groupings of related functionality that will be broken down into user stories during sprint planning (Phase 4).

Each epic maps to multiple functional requirements and will generate 2-10 stories.

---

### EPIC-001 : Gestion des Projets de Monitoring

**Description:**
Permettre à l'utilisateur de créer, gérer et organiser ses projets de monitoring de backlinks. Chaque projet représente un site web à surveiller avec ses backlinks associés.

**Functional Requirements:**
- FR-001 : Créer un projet
- FR-002 : Lister tous les projets
- FR-003 : Modifier un projet
- FR-004 : Supprimer un projet

**Story Count Estimate:** 4-6 stories

**Priority:** Must Have

**Business Value:**
Fondation de l'application. Sans projets, impossible d'organiser les backlinks. Permet la segmentation par site web.

---

### EPIC-002 : Gestion Manuelle des Backlinks

**Description:**
Permettre à l'utilisateur d'ajouter, consulter, modifier et supprimer des backlinks à surveiller. Inclut l'import en masse pour démarrage rapide.

**Functional Requirements:**
- FR-005 : Ajouter un backlink manuellement
- FR-006 : Lister les backlinks d'un projet
- FR-007 : Voir le détail d'un backlink
- FR-008 : Modifier un backlink
- FR-009 : Supprimer un backlink
- FR-010 : Importer des backlinks en masse

**Story Count Estimate:** 6-8 stories

**Priority:** Must Have

**Business Value:**
Interface CRUD complète pour gérer les backlinks. L'import en masse accélère la configuration initiale pour utilisateurs ayant déjà une liste de backlinks.

---

### EPIC-003 : Moteur de Monitoring Automatique

**Description:**
Cœur du système : vérification automatique et périodique de la présence, de l'état et des attributs des backlinks. Détection des changements et mise à jour des statuts.

**Functional Requirements:**
- FR-011 : Vérifier automatiquement un backlink
- FR-012 : Détecter les changements de backlink
- FR-013 : Mettre à jour le statut du backlink
- FR-014 : Programmer des vérifications périodiques
- FR-015 : Déclencher une vérification manuelle

**Story Count Estimate:** 8-10 stories

**Priority:** Must Have

**Business Value:**
La proposition de valeur centrale de l'application. Automatise une tâche fastidieuse et chronophage. Détection précoce des backlinks perdus permet une réaction rapide.

---

### EPIC-004 : Système d'Alertes et Notifications

**Description:**
Informer l'utilisateur en temps réel des changements critiques détectés sur ses backlinks (perte, modification d'attributs). Gestion et consultation des alertes.

**Functional Requirements:**
- FR-016 : Créer des alertes automatiques
- FR-017 : Lister les alertes
- FR-018 : Marquer une alerte comme lue
- FR-019 : Notification par email

**Story Count Estimate:** 4-6 stories

**Priority:** Must Have

**Business Value:**
Transforme les données de monitoring en informations actionnables. Permet une réaction rapide aux problèmes (contacter le webmaster, remplacer un backlink perdu).

---

### EPIC-005 : Intégration des Métriques SEO

**Description:**
Enrichir les backlinks avec des métriques SEO provenant d'APIs tierces (Moz, Ahrefs, etc.) pour évaluer la qualité des domaines sources. Mise à jour automatique quotidienne.

**Functional Requirements:**
- FR-020 : Récupérer les métriques SEO d'un domaine
- FR-021 : Afficher les métriques SEO
- FR-022 : Mettre à jour les métriques SEO quotidiennement
- FR-023 : Configurer les providers SEO

**Story Count Estimate:** 5-7 stories

**Priority:** Must Have

**Business Value:**
Permet de prioriser les efforts (se concentrer sur les backlinks de haute qualité). Aide à identifier les opportunités (domaines à fort DA) et les risques (domaines spammy).

---

### EPIC-006 : Marketplace de Backlinks

**Description:**
Permettre l'achat de backlinks directement depuis l'application via intégration avec des plateformes tierces (NextLevel, SEOClerks, etc.). Suivi du statut des commandes et ajout automatique au monitoring.

**Functional Requirements:**
- FR-024 : Passer une commande de backlink
- FR-025 : Suivre le statut d'une commande
- FR-026 : Synchroniser les statuts de commandes
- FR-027 : Ajouter automatiquement un backlink commandé

**Story Count Estimate:** 6-8 stories

**Priority:** Must Have

**Business Value:**
Workflow complet end-to-end : acheter → surveiller. Élimine le besoin de jongler entre plusieurs plateformes. Centralise toute l'activité backlink.

---

### EPIC-007 : Dashboard et Reporting

**Description:**
Fournir une vue d'ensemble visuelle de l'état des backlinks avec graphiques, statistiques et capacités d'export pour rapports clients ou analyse.

**Functional Requirements:**
- FR-028 : Afficher le tableau de bord global
- FR-029 : Visualiser l'évolution des backlinks
- FR-030 : Exporter un rapport

**Story Count Estimate:** 4-6 stories

**Priority:** Must Have

**Business Value:**
Vue d'ensemble rapide de la santé du profil de backlinks. Les graphiques révèlent les tendances. Les exports facilitent le reporting et l'analyse hors ligne.

---

### EPIC-008 : Infrastructure d'Authentification et Configuration

**Description:**
Sécuriser l'application avec authentification basique et fournir une interface de configuration globale pour paramétrer l'application (fréquence monitoring, API keys, notifications).

**Functional Requirements:**
- FR-031 : Authentification utilisateur (simplifié)
- FR-032 : Configuration globale

**Story Count Estimate:** 3-5 stories

**Priority:** Should Have

**Business Value:**
Protection des données sensibles. Flexibilité de configuration selon les besoins spécifiques (ex: ajuster fréquence monitoring selon quotas API).

---

### EPIC-009 : Scalabilité et Performance

**Description:**
Optimiser l'infrastructure backend pour gérer des volumes croissants de backlinks et de vérifications. Inclut indexation DB, configuration des queues, caching.

**Functional Requirements:**
- NFR-002 : Performance - Traitement des jobs
- NFR-005 : Scalabilité - Capacité de stockage
- NFR-006 : Scalabilité - Gestion de la queue

**Story Count Estimate:** 4-6 stories

**Priority:** Should Have

**Business Value:**
Pérennité de l'application. Permet de passer de 100 à 10,000 backlinks sans refonte. Évite les dégradations de performance futures.

---

### EPIC-010 : Sécurité et Robustesse

**Description:**
Implémenter les mesures de sécurité critiques (protection SSRF, sanitization, HTTPS, gestion erreurs APIs externes) pour garantir la fiabilité et la sécurité de l'application.

**Functional Requirements:**
- NFR-003 : Sécurité - Protection des données
- NFR-004 : Sécurité - Validation et protection SSRF
- NFR-008 : Fiabilité - Gestion des erreurs externes

**Story Count Estimate:** 5-7 stories

**Priority:** Must Have

**Business Value:**
Protection contre les attaques (SSRF, XSS). Stabilité face aux défaillances des APIs tierces. Confiance dans le stockage sécurisé des API keys.

---

### EPIC-011 : Interface Utilisateur Moderne et Responsive

**Description:**
Créer une interface Vue.js moderne, intuitive et responsive avec composants réutilisables, navigation fluide et design cohérent basé sur Tailwind CSS.

**Functional Requirements:**
- NFR-001 : Performance - Temps de réponse des pages
- NFR-011 : Compatibilité - Navigateurs et devices

**Story Count Estimate:** 6-9 stories

**Priority:** Must Have

**Business Value:**
Expérience utilisateur de qualité. Accessibilité mobile pour consulter les backlinks en déplacement. Interface professionnelle augmente la crédibilité.

---

### EPIC-012 : Testing et Qualité du Code

**Description:**
Mettre en place une suite de tests automatisés (unitaires, feature, jobs) et garantir la qualité du code via standards Laravel et documentation.

**Functional Requirements:**
- NFR-009 : Maintenabilité - Qualité du code
- NFR-010 : Maintenabilité - Tests automatisés

**Story Count Estimate:** 5-8 stories

**Priority:** Should Have

**Business Value:**
Réduction des bugs en production. Facilite les évolutions futures. Onboarding rapide de nouveaux développeurs grâce au code lisible.

---

## User Stories (High-Level)

User stories follow the format: "As a [user type], I want [goal] so that [benefit]."

These are preliminary stories. Detailed stories will be created in Phase 4 (Implementation).

**Note :** Les user stories détaillées seront créées durant la phase de Sprint Planning (Phase 4).

Les epics définis ci-dessus se décomposeront en 60-86 stories estimées, couvrant l'ensemble des exigences fonctionnelles et non-fonctionnelles.

---

## User Personas

### Persona 1 : SEO Specialist / Consultant Indépendant

**Nom :** Marc, 32 ans
**Rôle :** Consultant SEO freelance

**Objectif :**
Monitorer les backlinks de ses clients pour prouver le ROI de ses campagnes

**Pain Points :**
- Perte de temps à vérifier manuellement des centaines de backlinks
- Difficulté à détecter rapidement quand un backlink disparaît
- Manque de centralisation (jongle entre Ahrefs, Moz, plateformes d'achat)

**Usage Typique :**
Consulte l'app 2-3 fois par jour, exporte des rapports mensuels pour clients

---

### Persona 2 : Propriétaire de Site E-commerce

**Nom :** Sophie, 28 ans
**Rôle :** Propriétaire d'une boutique e-commerce

**Objectif :**
Maintenir et améliorer le profil de backlinks de son site pour le SEO

**Pain Points :**
- Budget limité pour outils SEO enterprise
- Besoin de surveiller les backlinks achetés pour vérifier leur mise en ligne
- Veut être alertée rapidement si un backlink important disparaît

**Usage Typique :**
Consulte le dashboard hebdomadairement, achète 2-3 backlinks/mois

---

## User Flows

### Flow 1 : Configuration Initiale d'un Nouveau Projet

1. L'utilisateur se connecte à l'application
2. Clique sur "Nouveau Projet"
3. Saisit le nom du projet et le domaine cible
4. Valide et accède à la page du projet vide
5. Clique sur "Importer des backlinks" (ou "Ajouter un backlink")
6. Upload un fichier CSV avec sa liste de backlinks existants
7. Vérifie le rapport d'import (succès/échecs)
8. Lance une première vérification manuelle pour initialiser les données
9. Consulte le dashboard pour voir l'état initial

---

### Flow 2 : Monitoring Quotidien et Réaction aux Alertes

1. L'utilisateur reçoit un email : "3 nouvelles alertes sur projet XYZ"
2. Se connecte à l'application
3. Accède au dashboard, voit les alertes en rouge
4. Clique sur une alerte "Backlink perdu : example.com/article-123"
5. Consulte le détail du backlink avec historique des checks
6. Voit que le backlink retourne 404 depuis 2 jours
7. Note mentalement de contacter le webmaster
8. Marque l'alerte comme lue
9. Répète pour les autres alertes

---

### Flow 3 : Achat et Suivi d'un Nouveau Backlink

1. L'utilisateur accède à l'onglet "Commandes"
2. Clique sur "Nouvelle Commande"
3. Sélectionne le projet cible
4. Saisit l'URL cible et l'anchor text souhaité
5. Sélectionne la plateforme (ex: NextLevel)
6. Voit le prix estimé et les domaines disponibles
7. Valide la commande
8. Reçoit une confirmation "Commande #123 en attente"
9. Quelques jours plus tard, reçoit un email "Commande #123 est live !"
10. Le backlink apparaît automatiquement dans sa liste avec statut "active"
11. Consulte les métriques SEO du domaine source

---

## Dependencies

### Internal Dependencies

- **PostgreSQL 15+** : Base de données relationnelle pour stockage de toutes les données
- **Serveur Web** : Nginx ou Apache pour servir l'application Laravel
- **PHP 8.2+** : Runtime pour Laravel 10
- **Node.js 18+** : Build des assets Vue.js avec Vite
- **Queue System** : Redis ou base de données pour Laravel Queues
- **Cron / Task Scheduler** : Pour déclencher les jobs périodiques (vérifications, métriques SEO)
- **Laravel Horizon** : Monitoring et gestion des queue workers
- **Laravel Telescope** (dev only) : Debugging et profiling

### External Dependencies

**APIs Tierces (optionnelles selon configuration) :**

- **Moz API** : Domain Authority, Page Authority
  - Authentification : API Key
  - Quota : Variable selon abonnement
  - Fallback : Custom provider si indisponible

- **Ahrefs API** : Domain Rating, URL Rating
  - Authentification : API Key
  - Quota : Variable selon abonnement
  - Fallback : Custom provider si indisponible

- **NextLevel.link API** : Commande de backlinks
  - Authentification : API Key
  - Fallback : Formulaire manuel si API down

- **SEOClerks API** : Marketplace de backlinks
  - Authentification : OAuth ou API Key
  - Fallback : Lien externe vers plateforme

---

## Assumptions

1. **Usage personnel** : Un seul utilisateur par instance pour la v1 (pas de multi-tenant)
2. **Volume modéré** : Maximum 10 projets et 1000 backlinks/projet pour la v1
3. **APIs SEO disponibles** : L'utilisateur possède ou peut obtenir des API keys Moz/Ahrefs
4. **Budget plateforme** : L'utilisateur a un budget pour acheter des backlinks
5. **Serveur dédié/VPS** : L'application tourne sur un environnement contrôlé (pas de shared hosting)
6. **Accès HTTPS** : Certificat SSL disponible pour la production
7. **Vérifications espacées** : 4 heures entre vérifications suffisantes (pas de real-time)
8. **Langues** : Interface en français uniquement pour la v1 (pas d'i18n)
9. **Rate limits APIs** : Les quotas des APIs tierces sont suffisants pour l'usage prévu
10. **Backlinks publics** : Tous les backlinks à vérifier sont accessibles publiquement (pas d'authentification)

---

## Out of Scope

Fonctionnalités **explicitement exclues** de la version 1 :

1. **Multi-utilisateurs / Multi-tenant** : Pas de système de comptes multiples, permissions, organisations
2. **Système de facturation** : Pas de gestion d'abonnements ou paiements récurrents
3. **API publique** : Pas d'API REST publique pour intégrations tierces
4. **Mobile native apps** : Pas d'applications iOS/Android (responsive web uniquement)
5. **Analyse de contenu** : Pas d'analyse du contenu entourant le backlink (contexte, pertinence)
6. **Détection automatique de backlinks** : Pas de crawling automatique pour découvrir de nouveaux backlinks
7. **Competitor tracking** : Pas de monitoring des backlinks des concurrents
8. **Anchor text distribution analysis** : Pas d'analyse avancée de la distribution des anchor texts
9. **Disavow file generation** : Pas de génération automatique de fichiers Google Disavow
10. **Intégration Google Search Console** : Pas d'import automatique depuis GSC
11. **White-label / Reselling** : Pas de fonctionnalités pour revendre la solution
12. **Advanced reporting** : Pas de rapports PDF automatiques avec branding personnalisé

---

## Open Questions

Questions à résoudre avant ou pendant l'implémentation :

1. **Fréquence de vérification ajustable** : Doit-on permettre une fréquence différente par projet (ex: vérification horaire pour projets critiques) ?

2. **Historique des checks** : Combien de temps conserver l'historique complet ? (30j, 90j, 1 an, illimité avec archivage ?)

3. **Stratégie de retry** : Comment gérer les backlinks qui échouent temporairement (timeout, 503) ? Retry immédiat ou attendre le prochain cycle ?

4. **Webhooks entrants** : Doit-on exposer des webhooks pour que les plateformes nous notifient directement des changements de statut ?

5. **Gestion des redirections** : Si un backlink redirige (301/302), doit-on suivre la redirection ou considérer ça comme un changement ?

6. **Métriques SEO custom** : Quels KPIs spécifiques pour le "Custom Provider" en l'absence d'API payante ?

7. **Backup et export complet** : Doit-on prévoir une fonction de backup complet des données (projets + backlinks + historique) ?

---

## Approval & Sign-off

### Stakeholders

- **Utilisateur Principal / Product Owner** : axel (propriétaire du projet, utilisateur final)
- **Développeur / Engineering Lead** : axel ou équipe de développement
- **Providers API Tiers** : Moz, Ahrefs, plateformes de backlinks (support technique si problèmes d'intégration)

### Approval Status

- [x] Product Owner - Approuvé (2026-02-09)
- [ ] Engineering Lead
- [ ] Design Lead
- [ ] QA Lead

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-02-09 | axel | Initial PRD - Created via BMAD Method workflow |

---

## Next Steps

### Phase 3: Architecture

Run `/architecture` to create system architecture based on these requirements.

The architecture will address:
- All functional requirements (FRs)
- All non-functional requirements (NFRs)
- Technical stack decisions (Laravel, Vue.js, PostgreSQL)
- Data models and relationships
- API design
- System components and services
- Queue architecture
- Security patterns

### Phase 4: Sprint Planning

After architecture is complete, run `/sprint-planning` to:
- Break epics into detailed user stories
- Estimate story complexity
- Plan sprint iterations
- Prioritize backlog
- Begin implementation

---

**This document was created using BMAD Method v6 - Phase 2 (Planning)**

*To continue: Run `/workflow-status` to see your progress and next recommended workflow.*

---

## Appendix A: Requirements Traceability Matrix

| Epic ID | Epic Name | Functional Requirements | NFRs | Story Count (Est.) |
|---------|-----------|-------------------------|------|-------------------|
| EPIC-001 | Gestion des Projets de Monitoring | FR-001, FR-002, FR-003, FR-004 | - | 4-6 stories |
| EPIC-002 | Gestion Manuelle des Backlinks | FR-005, FR-006, FR-007, FR-008, FR-009, FR-010 | - | 6-8 stories |
| EPIC-003 | Moteur de Monitoring Automatique | FR-011, FR-012, FR-013, FR-014, FR-015 | - | 8-10 stories |
| EPIC-004 | Système d'Alertes et Notifications | FR-016, FR-017, FR-018, FR-019 | - | 4-6 stories |
| EPIC-005 | Intégration des Métriques SEO | FR-020, FR-021, FR-022, FR-023 | - | 5-7 stories |
| EPIC-006 | Marketplace de Backlinks | FR-024, FR-025, FR-026, FR-027 | - | 6-8 stories |
| EPIC-007 | Dashboard et Reporting | FR-028, FR-029, FR-030 | - | 4-6 stories |
| EPIC-008 | Infrastructure d'Auth et Config | FR-031, FR-032 | - | 3-5 stories |
| EPIC-009 | Scalabilité et Performance | - | NFR-002, NFR-005, NFR-006 | 4-6 stories |
| EPIC-010 | Sécurité et Robustesse | - | NFR-003, NFR-004, NFR-008 | 5-7 stories |
| EPIC-011 | Interface UI Moderne et Responsive | - | NFR-001, NFR-011 | 6-9 stories |
| EPIC-012 | Testing et Qualité du Code | - | NFR-009, NFR-010 | 5-8 stories |
| **TOTAL** | **12 Epics** | **32 FRs** | **12 NFRs** | **60-86 stories** |

---

## Appendix B: Prioritization Details

### Functional Requirements Summary

**Total FRs:** 32

**By Priority:**
- **Must Have:** 19 FRs (59%)
  - FR-001, FR-002, FR-005, FR-006, FR-007, FR-011, FR-012, FR-013, FR-014, FR-016, FR-017, FR-018, FR-020, FR-021, FR-024, FR-025, FR-028, FR-029

- **Should Have:** 10 FRs (31%)
  - FR-003, FR-004, FR-008, FR-009, FR-015, FR-022, FR-023, FR-026, FR-027, FR-031, FR-032

- **Could Have:** 3 FRs (10%)
  - FR-010, FR-019, FR-030

**By Domain:**
- Gestion des Projets: 4 FRs
- Gestion des Backlinks: 6 FRs
- Monitoring Automatique: 5 FRs
- Alertes et Notifications: 4 FRs
- Métriques SEO: 4 FRs
- Commande de Backlinks: 4 FRs
- Dashboard et Rapports: 3 FRs
- Authentification et Configuration: 2 FRs

### Non-Functional Requirements Summary

**Total NFRs:** 12

**By Priority:**
- **Must Have:** 7 NFRs (58%)
  - NFR-001, NFR-002, NFR-003, NFR-004, NFR-006, NFR-008, NFR-012

- **Should Have:** 5 NFRs (42%)
  - NFR-005, NFR-007, NFR-009, NFR-010, NFR-011

**By Category:**
- Performance: 2 NFRs
- Sécurité: 2 NFRs
- Scalabilité: 2 NFRs
- Fiabilité: 2 NFRs
- Maintenabilité: 2 NFRs
- Compatibilité: 2 NFRs

### Epic Summary

**Total Epics:** 12

**By Priority:**
- **Must Have:** 9 epics (75%)
  - EPIC-001 through EPIC-007, EPIC-010, EPIC-011

- **Should Have:** 3 epics (25%)
  - EPIC-008, EPIC-009, EPIC-012

**Estimated Total Stories:** 60-86 stories

**Critical Path Epics** (Must complete first):
1. EPIC-001: Gestion des Projets (foundation)
2. EPIC-002: Gestion Manuelle des Backlinks (data input)
3. EPIC-003: Moteur de Monitoring (core value)
4. EPIC-010: Sécurité et Robustesse (prevent vulnerabilities early)

**Foundation Epics** (Enable other epics):
- EPIC-008: Auth et Configuration
- EPIC-009: Scalabilité et Performance
- EPIC-011: Interface UI Moderne

**Value-Add Epics** (Enhance core functionality):
- EPIC-004: Système d'Alertes
- EPIC-005: Intégration Métriques SEO
- EPIC-006: Marketplace de Backlinks
- EPIC-007: Dashboard et Reporting

**Quality Epics** (Long-term maintainability):
- EPIC-012: Testing et Qualité du Code
