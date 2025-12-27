<?php
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "includes/validation.php";
require_once "config/database.php";

// Tous les utilisateurs connectés peuvent effectuer des ventes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    requireCSRFToken();
    
    $produits = $_POST['produits'] ?? [];
    $quantites = $_POST['quantite'] ?? [];
    $prix = $_POST['prix'] ?? [];

    if (empty($produits) || !is_array($produits)) {
        header("Location: ventes.php?error=Aucun produit sélectionné");
        exit;
    }
    
    // Valider qu'au moins un produit a une quantité > 0
    $hasValidProduct = false;
    foreach ($produits as $id_medicament) {
        $id_medicament = validateId($id_medicament);
        if ($id_medicament && isset($quantites[$id_medicament])) {
            $qte = validatePositiveInt($quantites[$id_medicament], 1);
            if ($qte) {
                $hasValidProduct = true;
                break;
            }
        }
    }
    
    if (!$hasValidProduct) {
        header("Location: ventes.php?error=Veuillez sélectionner au moins un produit avec une quantité valide");
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Créer la vente principale avec total temporaire à 0
        $stmt = $pdo->prepare("INSERT INTO ventes (agent_id, date_vente, total) VALUES (?, NOW(), 0)");
        $stmt->execute([$_SESSION['user_id']]);
        $vente_id = $pdo->lastInsertId();

        $total_general = 0;

        foreach ($produits as $id_medicament) {
            $id_medicament = validateId($id_medicament);
            if (!$id_medicament) continue;
            
            $qte = validatePositiveInt($quantites[$id_medicament] ?? 0, 1);
            if (!$qte) continue;
            
            $prix_unitaire = validatePositiveFloat($prix[$id_medicament] ?? 0);
            if ($prix_unitaire === null) {
                throw new Exception("Prix invalide pour un produit.");
            }

            // Vérifier le stock et récupérer le prix d'achat (avec verrouillage pour éviter les race conditions)
            $stmt = $pdo->prepare("SELECT quantite, prix_achat, nom FROM medicaments WHERE id = ? FOR UPDATE");
            $stmt->execute([$id_medicament]);
            $med = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$med) {
                throw new Exception("Médicament introuvable (ID: {$id_medicament}).");
            }
            
            if ($med['quantite'] < $qte) {
                throw new Exception("Stock insuffisant pour '{$med['nom']}'. Stock disponible: {$med['quantite']}, demandé: {$qte}.");
            }

            $prix_achat = (float)($med['prix_achat'] ?? 0);

            // Enregistrer les produits de la vente avec prix unitaire et prix d'achat
            $stmt = $pdo->prepare("
                INSERT INTO vente_items (vente_id, medicament_id, quantite, prix_unitaire, prix_achat)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$vente_id, $id_medicament, $qte, $prix_unitaire, $prix_achat]);

            // Mettre à jour le stock
            $stmt = $pdo->prepare("UPDATE medicaments SET quantite = quantite - ? WHERE id = ?");
            $stmt->execute([$qte, $id_medicament]);

            // Calculer le total de la vente
            $total_general += $qte * $prix_unitaire;
        }
        
        if ($total_general <= 0) {
            throw new Exception("Le total de la vente doit être supérieur à zéro.");
        }

        // Mettre à jour le total de la vente
        $stmt = $pdo->prepare("UPDATE ventes SET total = ? WHERE id = ?");
        $stmt->execute([$total_general, $vente_id]);

        $pdo->commit();

        // Redirection vers ventes avec succès et id facture
        header("Location: ventes.php?success=1&vente_id={$vente_id}");
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erreur SQL lors de l'enregistrement de la vente : " . $e->getMessage());
        header("Location: ventes.php?error=" . urlencode("Erreur lors de l'enregistrement de la vente. Veuillez réessayer."));
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        header("Location: ventes.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ventes.php");
    exit;
}
