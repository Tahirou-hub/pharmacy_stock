<?php
/**
 * Script de correction de la base de données
 * Ajoute les colonnes manquantes
 */

require_once __DIR__ . '/../config/database.php';

echo "Vérification de la structure de la base de données...\n\n";

// Vérifier et ajouter prix_unitaire dans achats
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM achats LIKE 'prix_unitaire'");
    if ($stmt->rowCount() == 0) {
        echo "Ajout de la colonne prix_unitaire à la table achats...\n";
        $pdo->exec("ALTER TABLE achats ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite");
        echo "✅ Colonne prix_unitaire ajoutée avec succès!\n\n";
    } else {
        echo "✅ La colonne prix_unitaire existe déjà dans la table achats.\n\n";
    }
} catch (PDOException $e) {
    echo "❌ Erreur lors de la vérification de la table achats : " . $e->getMessage() . "\n\n";
}

// Afficher la structure actuelle
try {
    echo "Structure actuelle de la table achats :\n";
    $stmt = $pdo->query("DESCRIBE achats");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n✅ Vérification terminée!\n";
} catch (PDOException $e) {
    echo "❌ Erreur lors de l'affichage de la structure : " . $e->getMessage() . "\n";
}




