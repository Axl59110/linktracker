# Migration de SQLite vers PostgreSQL

## Contexte

Le projet LinkTracker utilise actuellement **SQLite** pour le développement local afin de faciliter la mise en place rapide. La migration vers **PostgreSQL** en production sera simple et sans perte de données.

---

## Quand migrer ?

**Option 1 : Avant le déploiement en production**
- Recommandé si vous développez localement avec SQLite
- Migration lors de la phase de déploiement

**Option 2 : Dès maintenant en développement**
- Si vous préférez développer avec PostgreSQL dès le début
- Nécessite l'installation de PostgreSQL localement

---

## Étapes de Migration

### 1. Installer PostgreSQL

**Via Herd (si disponible) :**
```powershell
herd services
# Vérifier si PostgreSQL est disponible
```

**Ou manuellement :**
1. Télécharger : https://www.postgresql.org/download/windows/
2. Installer avec les paramètres par défaut
3. Retenir le mot de passe du superutilisateur `postgres`

### 2. Créer la base de données

```sql
-- Se connecter à PostgreSQL (psql ou pgAdmin)
CREATE DATABASE linktracker;
CREATE USER linktracker_user WITH ENCRYPTED PASSWORD 'votre_mot_de_passe_securise';
GRANT ALL PRIVILEGES ON DATABASE linktracker TO linktracker_user;

-- PostgreSQL 15+ nécessite aussi :
\c linktracker
GRANT ALL ON SCHEMA public TO linktracker_user;
```

### 3. Modifier le fichier `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=linktracker
DB_USERNAME=linktracker_user
DB_PASSWORD=votre_mot_de_passe_securise
```

### 4. Réexécuter les migrations

**IMPORTANT** : Cela va recréer toutes les tables (les données SQLite seront perdues)

```powershell
cd C:\Users\axel\OneDrive\Desktop\Claude\Linktracker\app-laravel

# Supprimer la base SQLite (optionnel)
Remove-Item database\database.sqlite

# Réexécuter les migrations
php artisan migrate:fresh

# Si vous avez des seeders :
php artisan db:seed
```

---

## Migration avec Données (Avancé)

Si vous avez déjà des données importantes en SQLite et souhaitez les migrer :

### Option 1 : Export/Import manuel

```powershell
# 1. Exporter les données SQLite
php artisan tinker
> DB::connection('sqlite')->table('users')->get()->toJson()
# Copier le JSON

# 2. Changer vers PostgreSQL dans .env
# 3. Recréer les tables
php artisan migrate:fresh

# 4. Importer les données via tinker ou seeder
```

### Option 2 : Package de migration

```bash
composer require --dev doctrine/dbal

# Créer un script de migration personnalisé
php artisan make:command MigrateToPostgres
```

---

## Différences SQLite vs PostgreSQL

### Compatibilité Laravel

Laravel gère automatiquement les différences mineures entre les deux bases de données. **Aucune modification du code n'est nécessaire** dans 99% des cas.

### Cas spécifiques à vérifier

1. **Types de colonnes**
   - SQLite : plus permissif sur les types
   - PostgreSQL : strict (peut détecter des bugs cachés)

2. **Recherche insensible à la casse**
   - SQLite : `LIKE` est insensible par défaut
   - PostgreSQL : utiliser `ILIKE` au lieu de `LIKE`
   ```php
   // Au lieu de :
   User::where('name', 'LIKE', '%john%')

   // Utiliser (compatible avec les deux) :
   User::where('name', 'ILIKE', '%john%')
   ```

3. **Booléens**
   - SQLite : stocke comme 0/1
   - PostgreSQL : vrai type boolean
   - Laravel gère automatiquement la conversion ✅

4. **JSON**
   - Les deux supportent JSON
   - PostgreSQL a des opérateurs JSON avancés

---

## Recommandations

### Pour le Développement

**Continuer avec SQLite jusqu'au Sprint 2-3**, puis migrer vers PostgreSQL local pour tester :
- Les performances des requêtes complexes
- Les index spécifiques à PostgreSQL
- Les fonctionnalités JSON avancées (si utilisées)

### Pour la Production

**Utiliser PostgreSQL obligatoirement** car :
- ✅ Meilleure gestion de la concurrence
- ✅ Support des connexions multiples
- ✅ Performances supérieures avec gros volumes
- ✅ Fonctionnalités avancées (full-text search, JSONB, etc.)
- ✅ Backups et réplication professionnels

---

## Checklist de Migration

- [ ] PostgreSQL installé et accessible
- [ ] Base de données `linktracker` créée
- [ ] Utilisateur `linktracker_user` créé avec permissions
- [ ] `.env` modifié avec les credentials PostgreSQL
- [ ] Migrations exécutées : `php artisan migrate:fresh`
- [ ] Seeders exécutés (si applicable) : `php artisan db:seed`
- [ ] Tests passent : `php artisan test`
- [ ] Application accessible : http://linktracker.test

---

## Support

En cas de problème lors de la migration, vérifier :

1. **PostgreSQL est démarré** : vérifier dans les services Windows
2. **Port 5432 disponible** : `netstat -an | findstr 5432`
3. **Connexion possible** : `psql -U linktracker_user -d linktracker`
4. **Extensions nécessaires** :
   ```sql
   CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
   CREATE EXTENSION IF NOT EXISTS "pg_trgm";  -- pour full-text search
   ```

---

## Conclusion

La migration SQLite → PostgreSQL est **simple et sans risque** grâce à Laravel. Vous pouvez développer sereinement avec SQLite et migrer quand vous serez prêt, sans modification de code.

**Timeline recommandée :**
- **Maintenant - Sprint 1-2** : Développement avec SQLite ✅
- **Sprint 3-4** : Migration locale vers PostgreSQL (test)
- **Production** : Déploiement avec PostgreSQL
