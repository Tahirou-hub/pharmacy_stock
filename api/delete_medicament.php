<?php
require_once "../includes/auth.php";
require_once "../includes/csrf.php";
require_once "../includes/validation.php";
require_once "../config/database.php";

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    http_response_code(403);
    die("Accès refusé : Seuls les administrateurs peuvent supprimer des médicaments.");
}

// Vérifier la méthode POST (plus sécurisé que GET pour les suppressions)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die("Méthode non autorisée.");
}

// Vérifier le token CSRF
requireCSRFToken();

// Valider l'ID
$id = validateId($_POST['id'] ?? null);
if (!$id) {
    header("Location: ../medicaments.php?error=ID invalide");
    exit;
}

try {
    // Vérifier que le médicament existe et n'a pas de ventes en cours
    $stmt = $pdo->prepare("
        SELECT m.id, m.nom, 
               (SELECT COUNT(*) FROM vente_items vi WHERE vi.medicament_id = m.id) as nb_ventes
        FROM medicaments m 
        WHERE m.id = ?
    ");
    $stmt->execute([$id]);
    $medicament = $stmt->fetch();
    
    if (!$medicament) {
        header("Location: ../medicaments.php?error=Médicament introuvable");
        exit;
    }
    
    // Optionnel : Empêcher la suppression si des ventes existent
    // Décommenter si nécessaire :
    // if ($medicament['nb_ventes'] > 0) {
    //     header("Location: ../medicaments.php?error=Impossible de supprimer : ce médicament a des ventes associées");
    //     exit;
    // }
    
    // Supprimer le médicament
    $stmt = $pdo->prepare("DELETE FROM medicaments WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: ../medicaments.php?success=Médicament supprimé avec succès");
    exit;
    
} catch (PDOException $e) {
    error_log("Erreur lors de la suppression du médicament : " . $e->getMessage());
    header("Location: ../medicaments.php?error=Erreur lors de la suppression");
    exit;
}
