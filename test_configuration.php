<?php
/**
 * Script de test de la configuration
 * Vérifie que tous les composants sont correctement configurés
 */

echo "🧪 Test de Configuration - Pharmacy Stock\n";
echo "==========================================\n\n";

$errors = [];
$warnings = [];
$success = [];

// Test 1 : Fichier .env
echo "1. Vérification du fichier .env...\n";
if (file_exists('.env')) {
    $success[] = "✓ Fichier .env existe";
    echo "   ✓ Fichier .env trouvé\n";
    
    // Vérifier le contenu
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'DB_HOST') !== false && 
        strpos($envContent, 'DB_NAME') !== false &&
        strpos($envContent, 'DB_USER') !== false) {
        $success[] = "✓ Fichier .env contient les paramètres requis";
        echo "   ✓ Paramètres de base de données présents\n";
    } else {
        $warnings[] = "⚠ Fichier .env incomplet";
        echo "   ⚠ Certains paramètres manquent dans .env\n";
    }
} else {
    $errors[] = "✗ Fichier .env manquant";
    echo "   ✗ Fichier .env non trouvé\n";
}

// Test 2 : Dossier logs
echo "\n2. Vérification du dossier logs...\n";
if (is_dir('logs')) {
    $success[] = "✓ Dossier logs existe";
    echo "   ✓ Dossier logs trouvé\n";
    
    if (is_writable('logs')) {
        $success[] = "✓ Dossier logs est accessible en écriture";
        echo "   ✓ Permissions d'écriture OK\n";
    } else {
        $warnings[] = "⚠ Dossier logs non accessible en écriture";
        echo "   ⚠ Problème de permissions sur le dossier logs\n";
    }
} else {
    $errors[] = "✗ Dossier logs manquant";
    echo "   ✗ Dossier logs non trouvé\n";
}

// Test 3 : Connexion à la base de données
echo "\n3. Test de connexion à la base de données...\n";
try {
    require_once 'config/database.php';
    $success[] = "✓ Connexion à la base de données réussie";
    echo "   ✓ Connexion réussie\n";
    
    // Test de requête
    $stmt = $pdo->query("SELECT DATABASE()");
    $dbName = $stmt->fetchColumn();
    echo "   ✓ Base de données active : $dbName\n";
    
    // Vérifier les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $expectedTables = ['users', 'medicaments', 'ventes', 'achats', 'vente_items', 'factures'];
    $missingTables = array_diff($expectedTables, $tables);
    
    if (empty($missingTables)) {
        $success[] = "✓ Toutes les tables requises sont présentes";
        echo "   ✓ Toutes les tables requises trouvées (" . count($tables) . " tables)\n";
    } else {
        $warnings[] = "⚠ Tables manquantes : " . implode(', ', $missingTables);
        echo "   ⚠ Tables manquantes : " . implode(', ', $missingTables) . "\n";
    }
    
    // Vérifier les champs manquants
    echo "\n4. Vérification de la structure de la base de données...\n";
    $checks = [
        'medicaments' => ['prix_achat'],
        'ventes' => ['agent_id', 'total'],
        'vente_items' => ['prix_achat']
    ];
    
    $allFieldsOk = true;
    foreach ($checks as $table => $fields) {
        if (in_array($table, $tables)) {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($fields as $field) {
                if (in_array($field, $columns)) {
                    echo "   ✓ $table.$field présent\n";
                } else {
                    $allFieldsOk = false;
                    $warnings[] = "⚠ Champ manquant : $table.$field";
                    echo "   ⚠ $table.$field manquant\n";
                }
            }
        }
    }
    
    if ($allFieldsOk) {
        $success[] = "✓ Tous les champs requis sont présents";
    }
    
} catch (Exception $e) {
    $errors[] = "✗ Erreur de connexion : " . $e->getMessage();
    echo "   ✗ Erreur : " . $e->getMessage() . "\n";
}

// Test 4 : Fichiers includes
echo "\n5. Vérification des fichiers includes...\n";
$requiredFiles = [
    'includes/auth.php',
    'includes/csrf.php',
    'includes/validation.php',
    'includes/rate_limit.php',
    'includes/sidebar.php',
    'includes/errors.php'
];

$allFilesOk = true;
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ✓ $file\n";
    } else {
        $allFilesOk = false;
        $errors[] = "✗ Fichier manquant : $file";
        echo "   ✗ $file manquant\n";
    }
}

if ($allFilesOk) {
    $success[] = "✓ Tous les fichiers includes sont présents";
}

// Test 5 : Fonctions CSRF
echo "\n6. Test des fonctions CSRF...\n";
try {
    require_once 'includes/csrf.php';
    $token = generateCSRFToken();
    if (!empty($token)) {
        $success[] = "✓ Système CSRF fonctionnel";
        echo "   ✓ Génération de token CSRF OK\n";
        
        if (verifyCSRFToken($token)) {
            $success[] = "✓ Vérification CSRF fonctionnelle";
            echo "   ✓ Vérification de token CSRF OK\n";
        } else {
            $warnings[] = "⚠ Vérification CSRF échouée";
            echo "   ⚠ Problème avec la vérification CSRF\n";
        }
    }
} catch (Exception $e) {
    $errors[] = "✗ Erreur CSRF : " . $e->getMessage();
    echo "   ✗ Erreur : " . $e->getMessage() . "\n";
}

// Résumé
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 RÉSUMÉ DES TESTS\n";
echo str_repeat("=", 50) . "\n\n";

if (!empty($success)) {
    echo "✅ SUCCÈS (" . count($success) . ")\n";
    foreach ($success as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠️  AVERTISSEMENTS (" . count($warnings) . ")\n";
    foreach ($warnings as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERREURS (" . count($errors) . ")\n";
    foreach ($errors as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

// Conclusion
if (empty($errors)) {
    if (empty($warnings)) {
        echo "🎉 Tous les tests sont passés avec succès !\n";
        echo "   Votre application est prête à être utilisée.\n";
    } else {
        echo "✅ Configuration fonctionnelle avec quelques avertissements.\n";
        echo "   Consultez les avertissements ci-dessus.\n";
    }
} else {
    echo "❌ Des erreurs ont été détectées.\n";
    echo "   Veuillez corriger les erreurs avant d'utiliser l'application.\n";
}

echo "\n";


