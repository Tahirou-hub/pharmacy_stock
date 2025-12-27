# 🔧 Guide de Configuration - Pharmacy Stock

## ✅ Configuration Automatique (Recommandé)

Un script de configuration automatique a été exécuté avec succès ! Votre projet est maintenant configuré.

### Ce qui a été fait :

1. ✅ **Fichier `.env` créé** avec vos paramètres de base de données
2. ✅ **Dossier `logs/` créé** pour les fichiers de log
3. ✅ **Connexion à la base de données vérifiée**
4. ✅ **Structure de la base de données vérifiée** (tous les champs requis sont présents)

## 📋 Vérification de la Configuration

### 1. Vérifier le fichier `.env`

Ouvrez le fichier `.env` et vérifiez que les valeurs sont correctes :

```env
DB_HOST=localhost
DB_NAME=pharmacy_stock
DB_USER=root
DB_PASS=votre_mot_de_passe
```

**Important** : 
- Assurez-vous que le mot de passe est correct
- En production, changez la `SECRET_KEY` pour une valeur aléatoire forte

### 2. Vérifier la connexion à la base de données

Testez la connexion en accédant à `login.php` dans votre navigateur :
```
http://localhost/pharmacy-stock/login.php
```

Si vous voyez la page de connexion, la configuration est correcte !

### 3. Vérifier les permissions des fichiers

Sur Linux/Mac, assurez-vous que les permissions sont correctes :

```bash
chmod 600 .env          # Fichier .env (lecture/écriture pour propriétaire uniquement)
chmod 755 logs/        # Dossier logs (lecture/écriture pour propriétaire, lecture pour autres)
```

Sur Windows, ces permissions sont généralement gérées automatiquement.

## 🔄 Configuration Manuelle (Si nécessaire)

Si vous préférez configurer manuellement ou si le script automatique n'a pas fonctionné :

### Étape 1 : Créer le fichier `.env`

1. Copiez le fichier `.env.example` vers `.env` :
   ```bash
   cp .env.example .env
   ```

2. Éditez le fichier `.env` et modifiez les valeurs :
   ```env
   DB_HOST=localhost
   DB_NAME=pharmacy_stock
   DB_USER=root
   DB_PASS=votre_mot_de_passe_ici
   SECRET_KEY=une_cle_secrete_aleatoire_ici
   ```

### Étape 2 : Créer le dossier `logs`

```bash
mkdir logs
chmod 755 logs
```

### Étape 3 : Vérifier la base de données

#### Si vous créez une nouvelle base de données :

Exécutez le schéma SQL complet :
```sql
SOURCE sql/schema.sql;
```

Ou importez-le via phpMyAdmin.

#### Si votre base de données existe déjà :

Exécutez la migration pour ajouter les champs manquants :
```sql
SOURCE sql/migrations/add_missing_fields.sql;
```

Ou importez-le via phpMyAdmin.

## 🧪 Test de la Configuration

### Test 1 : Connexion à la base de données

Créez un fichier `test_db.php` à la racine :

```php
<?php
require_once 'config/database.php';
echo "✓ Connexion à la base de données réussie !\n";
echo "Base de données : " . $dbName . "\n";
?>
```

Exécutez-le :
```bash
php test_db.php
```

### Test 2 : Test de l'application

1. Accédez à `http://localhost/pharmacy-stock/login.php`
2. Connectez-vous avec vos identifiants
3. Vérifiez que vous pouvez naviguer dans l'application

## ⚠️ Dépannage

### Erreur : "Connexion à la base de données impossible"

**Solutions** :
1. Vérifiez que MySQL/MariaDB est démarré
2. Vérifiez les identifiants dans `.env`
3. Vérifiez que la base de données `pharmacy_stock` existe
4. Vérifiez que l'utilisateur a les permissions nécessaires

### Erreur : "Token CSRF invalide"

**Solutions** :
1. Vérifiez que les sessions PHP fonctionnent
2. Vérifiez que le dossier de sessions est accessible en écriture
3. Videz le cache du navigateur

### Erreur : "Champs manquants dans la base de données"

**Solutions** :
1. Exécutez la migration : `sql/migrations/add_missing_fields.sql`
2. Vérifiez que vous avez les permissions pour modifier la structure de la base

### Erreur : "Impossible d'écrire dans logs/"

**Solutions** :
1. Vérifiez les permissions du dossier `logs/`
2. Assurez-vous que le serveur web peut écrire dans ce dossier
3. Créez le dossier manuellement si nécessaire

## 📝 Notes Importantes

### Sécurité

1. **Ne commitez JAMAIS le fichier `.env`** (il est déjà dans `.gitignore`)
2. **Changez la SECRET_KEY en production** pour une valeur aléatoire forte
3. **Utilisez HTTPS en production**
4. **Limitez les permissions du fichier `.env`** (600 sur Linux/Mac)

### Performance

- Les index ajoutés amélioreront les performances des requêtes
- Le système de cache peut être activé pour les données fréquemment consultées

### Maintenance

- Les logs sont stockés dans `logs/error.log`
- Surveillez régulièrement les logs pour détecter les erreurs
- Faites des sauvegardes régulières de la base de données

## ✅ Checklist de Configuration

- [ ] Fichier `.env` créé et configuré
- [ ] Dossier `logs/` créé avec les bonnes permissions
- [ ] Connexion à la base de données testée et fonctionnelle
- [ ] Base de données créée ou migrée avec succès
- [ ] Application accessible via le navigateur
- [ ] Connexion utilisateur fonctionnelle
- [ ] Toutes les fonctionnalités testées

## 🎉 Configuration Terminée !

Votre projet Pharmacy Stock est maintenant configuré et prêt à être utilisé.

Pour toute question ou problème, consultez :
- `README_AMELIORATIONS.md` - Guide d'utilisation des améliorations
- `RESUME_AMELIORATIONS.md` - Résumé des améliorations apportées
- `ANALYSE_AMELIORATIONS.md` - Analyse complète du projet


