<?php
/**
 * Script de configuration du projet Pharmacy Stock
 * Exécutez ce fichier une seule fois pour configurer votre environnement
 */

echo "🔧 Configuration du projet Pharmacy Stock\n";
echo "==========================================\n\n";

// Étape 1 : Créer le fichier .env
echo "📝 Étape 1 : Création du fichier .env\n";

if (file_exists('.env')) {
    echo "⚠️  Le fichier .env existe déjà.\n";
    $overwrite = readline("Voulez-vous le réécrire ? (o/n) : ");
    if (strtolower($overwrite) !== 'o') {
        echo "✓ Fichier .env conservé.\n\n";
    } else {
        createEnvFile();
    }
} else {
    createEnvFile();
}

// Étape 2 : Créer le dossier logs
echo "\n📁 Étape 2 : Création du dossier logs\n";
if (!is_dir('logs')) {
    if (mkdir('logs', 0755, true)) {
        echo "✓ Dossier logs créé avec succès.\n";
    } else {
        echo "⚠️  Impossible de créer le dossier logs automatiquement.\n";
        echo "   Veuillez le créer manuellement avec les permissions 755.\n";
    }
} else {
    echo "✓ Le dossier logs existe déjà.\n";
}

// Étape 3 : Vérifier la connexion à la base de données
echo "\n🔌 Étape 3 : Vérification de la connexion à la base de données\n";
require_once 'config/database.php';

try {
    // Test de connexion
    $pdo->query("SELECT 1");
    echo "✓ Connexion à la base de données réussie.\n";
    
    // Vérifier si la base existe et contient les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "\n⚠️  La base de données est vide.\n";
        echo "   Vous devez exécuter le schéma SQL : sql/schema.sql\n";
    } else {
        echo "✓ Base de données trouvée avec " . count($tables) . " table(s).\n";
        
        // Vérifier les champs manquants
        checkMissingFields($pdo);
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
    echo "   Vérifiez vos paramètres dans le fichier .env\n";
}

echo "\n✅ Configuration terminée !\n";
echo "\n📋 Prochaines étapes :\n";
echo "   1. Vérifiez le fichier .env et ajustez les valeurs si nécessaire\n";
echo "   2. Si la base de données est vide, exécutez : sql/schema.sql\n";
echo "   3. Si la base existe déjà, exécutez : sql/migrations/add_missing_fields.sql\n";
echo "   4. Testez l'application en accédant à login.php\n";

function createEnvFile() {
    $envContent = "# Configuration de la base de données\n";
    $envContent .= "DB_HOST=localhost\n";
    $envContent .= "DB_NAME=pharmacy_stock\n";
    $envContent .= "DB_USER=root\n";
    
    echo "Entrez le mot de passe de la base de données (ou laissez vide pour '12345678') : ";
    $password = readline();
    $envContent .= "DB_PASS=" . ($password ?: "12345678") . "\n\n";
    
    $envContent .= "# Configuration de l'application\n";
    $envContent .= "APP_ENV=development\n";
    $envContent .= "APP_DEBUG=true\n\n";
    
    $envContent .= "# Clé secrète pour CSRF et sessions\n";
    $envContent .= "SECRET_KEY=" . bin2hex(random_bytes(32)) . "\n";
    
    if (file_put_contents('.env', $envContent)) {
        echo "✓ Fichier .env créé avec succès.\n";
        // Définir les permissions (600 = lecture/écriture pour le propriétaire uniquement)
        if (PHP_OS_FAMILY !== 'Windows') {
            chmod('.env', 0600);
        }
    } else {
        echo "❌ Erreur lors de la création du fichier .env\n";
    }
}

function checkMissingFields($pdo) {
    echo "\n🔍 Vérification des champs de la base de données...\n";
    
    $checks = [
        'medicaments' => ['prix_achat'],
        'ventes' => ['agent_id', 'total'],
        'vente_items' => ['prix_achat']
    ];
    
    $missing = [];
    
    foreach ($checks as $table => $fields) {
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($fields as $field) {
                if (!in_array($field, $columns)) {
                    $missing[] = "$table.$field";
                }
            }
        } catch (PDOException $e) {
            echo "⚠️  Table $table non trouvée.\n";
        }
    }
    
    if (empty($missing)) {
        echo "✓ Tous les champs requis sont présents.\n";
    } else {
        echo "⚠️  Champs manquants détectés :\n";
        foreach ($missing as $field) {
            echo "   - $field\n";
        }
        echo "\n   Exécutez la migration : sql/migrations/add_missing_fields.sql\n";
    }
}


