<?php
/**
 * Vérifie et corrige la structure de la base de données
 * Ajoute les colonnes manquantes si nécessaire
 */

require_once __DIR__ . '/../config/database.php';

function checkAndFixDatabase($pdo) {
    $fixes = [];
    
    // Vérifier la colonne prix_unitaire dans achats
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM achats LIKE 'prix_unitaire'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE achats ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite");
            $fixes[] = "Colonne prix_unitaire ajoutée à la table achats";
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la vérification de la table achats : " . $e->getMessage());
    }
    
    return $fixes;
}

// Exécuter automatiquement la vérification
checkAndFixDatabase($pdo);




