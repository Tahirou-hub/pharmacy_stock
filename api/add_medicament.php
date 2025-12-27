<?php
require_once "../includes/auth.php";
require_once "../includes/csrf.php";
require_once "../includes/validation.php";
require_once "../config/database.php";

// Vérifier que l'utilisateur est admin
if (!isAdmin()) {
    http_response_code(403);
    die("Accès refusé : Seuls les administrateurs peuvent ajouter des médicaments.");
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    requireCSRFToken();
    
    // Valider et nettoyer les données
    $nom = sanitizeString($_POST['nom'] ?? '', 255);
    $description = sanitizeString($_POST['description'] ?? '', 1000);
    $prix = validatePositiveFloat($_POST['prix'] ?? 0);
    $prix_achat = validatePositiveFloat($_POST['prix_achat'] ?? 0);
    $quantite = validatePositiveInt($_POST['quantite'] ?? 0, 0);
    $seuil = validatePositiveInt($_POST['seuil_rupture'] ?? 10, 1);
    
    // Validation des champs requis
    if (empty($nom)) {
        header("Location: ../edit_medicament.php?error=Le nom est requis");
        exit;
    }
    
    if ($prix === null || $prix < 0) {
        header("Location: ../edit_medicament.php?error=Prix de vente invalide");
        exit;
    }
    
    if ($prix_achat === null || $prix_achat < 0) {
        header("Location: ../edit_medicament.php?error=Prix d'achat invalide");
        exit;
    }
    
    if ($quantite === null || $quantite < 0) {
        header("Location: ../edit_medicament.php?error=Quantité invalide");
        exit;
    }
    
    if ($seuil === null || $seuil < 1) {
        header("Location: ../edit_medicament.php?error=Seuil de rupture invalide");
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO medicaments (nom, description, prix, prix_achat, quantite, seuil_rupture) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $description, $prix, $prix_achat, $quantite, $seuil]);
        
        header("Location: ../medicaments.php?success=Médicament ajouté avec succès");
        exit;
    } catch (PDOException $e) {
        error_log("Erreur lors de l'ajout du médicament : " . $e->getMessage());
        header("Location: ../edit_medicament.php?error=Erreur lors de l'ajout du médicament");
        exit;
    }
} else {
    header("Location: ../medicaments.php");
    exit;
}
