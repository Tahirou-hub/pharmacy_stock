<?php
// api/add_vente.php
header('Content-Type: application/json');
include_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['medicament_id'], $data['quantite'], $data['prix_unitaire'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

// Vérifier que médicament existe
$stmt = $pdo->prepare("SELECT quantite FROM medicaments WHERE id = :id");
$stmt->execute([':id' => $data['medicament_id']]);
$med = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$med) {
    http_response_code(404);
    echo json_encode(['error' => 'Médicament non trouvé']);
    exit;
}
if ($med['quantite'] < $data['quantite']) {
    http_response_code(400);
    echo json_encode(['error' => 'Quantité insuffisante']);
    exit;
}

// Insérer la vente
$stmt = $pdo->prepare("
    INSERT INTO ventes (medicament_id, quantite, prix_unitaire) 
    VALUES (:medicament_id, :quantite, :prix_unitaire)
");
$stmt->execute([
    ':medicament_id' => $data['medicament_id'],
    ':quantite' => $data['quantite'],
    ':prix_unitaire' => $data['prix_unitaire']
]);

// Mettre à jour quantité médicament
$stmt = $pdo->prepare("
    UPDATE medicaments 
    SET quantite = quantite - :q 
    WHERE id = :id
");
$stmt->execute([
    ':q' => $data['quantite'],
    ':id' => $data['medicament_id']
]);

echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
