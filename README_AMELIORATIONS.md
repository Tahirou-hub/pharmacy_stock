# 🔧 Guide d'Installation des Améliorations

Ce document explique comment utiliser les améliorations apportées au projet Pharmacy Stock.

## 📋 Améliorations Implémentées

### ✅ Sécurité

1. **Configuration sécurisée de la base de données**
   - Utilisation de variables d'environnement via fichier `.env`
   - Fichier `.env.example` fourni comme modèle

2. **Protection CSRF**
   - Tous les formulaires protégés contre les attaques CSRF
   - Fonctions disponibles dans `includes/csrf.php`

3. **Rate Limiting**
   - Protection contre les attaques par force brute sur le login
   - 5 tentatives maximum par 5 minutes

4. **Validation stricte des entrées**
   - Fonctions de validation dans `includes/validation.php`
   - Validation de tous les formulaires

5. **Vérification des permissions**
   - Vérification du rôle admin pour les actions sensibles
   - Suppression de médicaments sécurisée

### ✅ Base de Données

1. **Schéma SQL mis à jour**
   - Ajout des champs manquants : `prix_achat`, `agent_id`, `total`
   - Ajout d'index pour améliorer les performances
   - Ajout de contraintes CHECK pour la validation

2. **Fichier de migration**
   - `sql/migrations/add_missing_fields.sql` pour mettre à jour les bases existantes

### ✅ Architecture

1. **Composant sidebar réutilisable**
   - `includes/sidebar.php` pour éviter la duplication de code

2. **Système de gestion d'erreurs**
   - `includes/errors.php` pour afficher les messages de manière cohérente

## 🚀 Installation

### Étape 1 : Configuration de l'environnement

1. Copiez le fichier `.env.example` vers `.env` :
   ```bash
   cp .env.example .env
   ```

2. Modifiez le fichier `.env` avec vos paramètres :
   ```env
   DB_HOST=localhost
   DB_NAME=pharmacy_stock
   DB_USER=root
   DB_PASS=votre_mot_de_passe
   SECRET_KEY=une_cle_secrete_aleatoire
   ```

### Étape 2 : Mise à jour de la base de données

Si votre base de données existe déjà, exécutez la migration :

```sql
SOURCE sql/migrations/add_missing_fields.sql;
```

Ou importez le fichier via phpMyAdmin ou votre outil de gestion MySQL.

Si vous créez une nouvelle base de données, utilisez le schéma mis à jour :

```sql
SOURCE sql/schema.sql;
```

### Étape 3 : Créer le dossier de logs

```bash
mkdir logs
chmod 755 logs
```

### Étape 4 : Vérifier les permissions

Assurez-vous que le serveur web peut :
- Lire le fichier `.env`
- Écrire dans le dossier `logs/`

## 📝 Utilisation

### Protection CSRF dans les formulaires

Dans vos formulaires, ajoutez le champ CSRF :

```php
<?php require_once "includes/csrf.php"; ?>
<form method="POST">
    <?= csrfField() ?>
    <!-- Vos champs de formulaire -->
</form>
```

Dans le fichier de traitement, vérifiez le token :

```php
<?php
require_once "includes/csrf.php";
requireCSRFToken(); // Vérifie automatiquement le token
// Votre code de traitement
?>
```

### Validation des données

```php
<?php require_once "includes/validation.php"; ?>

<?php
$username = validateUsername($_POST['username']);
$price = validatePositiveFloat($_POST['price']);
$quantity = validatePositiveInt($_POST['quantity'], 1);
?>
```

### Affichage des messages

```php
<?php require_once "includes/errors.php"; ?>

<?php displayMessages(); // Affiche les messages depuis $_GET ?>
<?php displayError("Une erreur est survenue"); ?>
<?php displaySuccess("Opération réussie"); ?>
```

### Utilisation de la sidebar

```php
<?php require_once "includes/sidebar.php"; ?>
```

## 🔒 Sécurité

### Points importants

1. **Ne jamais commiter le fichier `.env`**
   - Il est déjà dans `.gitignore`
   - Contient des informations sensibles

2. **Changer la SECRET_KEY en production**
   - Utilisez une clé aléatoire forte
   - Minimum 32 caractères

3. **Permissions des fichiers**
   - `.env` : 600 (lecture/écriture pour le propriétaire uniquement)
   - `logs/` : 755 (lecture/écriture pour le propriétaire, lecture pour les autres)

## 🐛 Dépannage

### Erreur de connexion à la base de données

- Vérifiez que le fichier `.env` existe et contient les bonnes valeurs
- Vérifiez que les identifiants sont corrects
- Vérifiez que MySQL/MariaDB est démarré

### Erreur CSRF

- Assurez-vous d'avoir inclus `includes/csrf.php`
- Vérifiez que `session_start()` est appelé avant l'utilisation de CSRF
- Vérifiez que le champ `csrf_token` est présent dans le formulaire

### Erreurs de migration SQL

- Vérifiez que vous utilisez MySQL 5.7+ ou MariaDB 10.2+
- Les contraintes CHECK peuvent ne pas fonctionner sur les anciennes versions
- Dans ce cas, commentez les lignes `ADD CONSTRAINT ... CHECK` dans la migration

## 📚 Fichiers Modifiés/Créés

### Nouveaux fichiers

- `.env.example` - Modèle de configuration
- `.gitignore` - Fichiers à ignorer par Git
- `includes/csrf.php` - Protection CSRF
- `includes/validation.php` - Fonctions de validation
- `includes/rate_limit.php` - Rate limiting
- `includes/sidebar.php` - Composant sidebar
- `includes/errors.php` - Gestion d'erreurs
- `sql/migrations/add_missing_fields.sql` - Migration SQL
- `README_AMELIORATIONS.md` - Ce fichier

### Fichiers modifiés

- `config/database.php` - Support des variables d'environnement
- `api/auth.php` - Rate limiting et validation
- `api/add_medicament.php` - CSRF et validation
- `api/edit_medicament.php` - CSRF et validation
- `api/delete_medicament.php` - CSRF, permissions et validation
- `add_vente.php` - CSRF et validation améliorée
- `medicaments.php` - Formulaire POST pour suppression
- `edit_medicament.php` - CSRF
- `ventes.php` - CSRF
- `achats.php` - CSRF
- `parametres.php` - CSRF et validation
- `export_rapport.php` - Chemin FPDF corrigé
- `sql/schema.sql` - Schéma mis à jour

## ⚠️ Notes Importantes

1. **Compatibilité**
   - Les améliorations sont rétrocompatibles
   - Si `.env` n'existe pas, le système utilise les valeurs par défaut

2. **Performance**
   - Les index ajoutés amélioreront les performances des requêtes
   - Le rate limiting peut être ajusté dans `includes/rate_limit.php`

3. **Sécurité en production**
   - Changez tous les mots de passe par défaut
   - Utilisez HTTPS
   - Configurez correctement les permissions de fichiers
   - Activez les logs d'erreurs PHP

## 📞 Support

Pour toute question ou problème, consultez le fichier `ANALYSE_AMELIORATIONS.md` pour la liste complète des améliorations recommandées.


