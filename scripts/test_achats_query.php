<?php
require_once __DIR__ . '/../config/database.php';

echo "Test de la requête achats...\n\n";

try {
    $stmt = $pdo->query("
        SELECT a.id, a.date_achat, m.nom AS medicament, a.quantite, a.prix_unitaire,
               (a.quantite * a.prix_unitaire) AS total_achat
        FROM achats a
        JOIN medicaments m ON m.id = a.medicament_id
        ORDER BY a.date_achat DESC
        LIMIT 5
    ");
    $achats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Requête réussie ! " . count($achats) . " résultat(s)\n\n";
    
    if (!empty($achats)) {
        echo "Premier résultat :\n";
        print_r($achats[0]);
    }
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Code : " . $e->getCode() . "\n";
}




