<?php
require_once "../includes/auth.php";
require_once "../includes/csrf.php";
require_once "../includes/validation.php";
require_once "../config/database.php";

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    http_response_code(403);
    die("Accès refusé : Seuls les administrateurs peuvent modifier des médicaments.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    requireCSRFToken();
    
    // Valider l'ID
    $id = validateId($_POST['id'] ?? null);
    if (!$id) {
        header("Location: ../medicaments.php?error=ID invalide");
        exit;
    }
    
    // Valider et nettoyer les données
    $nom = sanitizeString($_POST['nom'] ?? '', 255);
    $description = sanitizeString($_POST['description'] ?? '', 1000);
    $prix = validatePositiveFloat($_POST['prix'] ?? 0);
    $prix_achat = validatePositiveFloat($_POST['prix_achat'] ?? 0);
    $quantite = validatePositiveInt($_POST['quantite'] ?? 0, 0);
    $seuil = validatePositiveInt($_POST['seuil_rupture'] ?? 10, 1);
    
    // Validation des champs requis
    if (empty($nom)) {
        header("Location: ../edit_medicament.php?id={$id}&error=Le nom est requis");
        exit;
    }
    
    if ($prix === null || $prix < 0) {
        header("Location: ../edit_medicament.php?id={$id}&error=Prix de vente invalide");
        exit;
    }
    
    if ($prix_achat === null || $prix_achat < 0) {
        header("Location: ../edit_medicament.php?id={$id}&error=Prix d'achat invalide");
        exit;
    }
    
    if ($quantite === null || $quantite < 0) {
        header("Location: ../edit_medicament.php?id={$id}&error=Quantité invalide");
        exit;
    }
    
    if ($seuil === null || $seuil < 1) {
        header("Location: ../edit_medicament.php?id={$id}&error=Seuil de rupture invalide");
        exit;
    }
    
    try {
        // Vérifier que le médicament existe
        $stmt = $pdo->prepare("SELECT id FROM medicaments WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            header("Location: ../medicaments.php?error=Médicament introuvable");
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE medicaments SET nom=?, description=?, prix=?, prix_achat=?, quantite=?, seuil_rupture=? WHERE id=?");
        $stmt->execute([$nom, $description, $prix, $prix_achat, $quantite, $seuil, $id]);
        
        header("Location: ../medicaments.php?success=Médicament modifié avec succès");
        exit;
    } catch (PDOException $e) {
        error_log("Erreur lors de la modification du médicament : " . $e->getMessage());
        header("Location: ../edit_medicament.php?id={$id}&error=Erreur lors de la modification");
        exit;
    }
} else {
    header("Location: ../medicaments.php");
    exit;
}
