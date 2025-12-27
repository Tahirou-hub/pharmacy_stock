<?php
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "includes/validation.php";
require_once "config/database.php";

if (!isAdmin()) {
    header("Location: dashboard.php?error=Accès refusé : Seuls les administrateurs peuvent gérer les achats.");
    exit;
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['medicament_id'])){
    requireCSRFToken();
    
    $med_id = validateId($_POST['medicament_id'] ?? null);
    $quantite = validatePositiveInt($_POST['quantite'] ?? 0, 1);
    $prix_unitaire = validatePositiveFloat($_POST['prix_unitaire'] ?? 0);

    if(!$med_id){
        $error = "Médicament invalide.";
    } elseif(!$quantite){
        $error = "Quantité invalide (doit être supérieure à 0).";
    } elseif($prix_unitaire === null){
        $error = "Prix unitaire invalide.";
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT id, nom FROM medicaments WHERE id = ?");
            $stmt->execute([$med_id]);
            $med = $stmt->fetch();
            
            if(!$med){
                throw new Exception("Médicament introuvable.");
            }

            $stmt = $pdo->prepare("INSERT INTO achats (medicament_id, quantite, prix_unitaire, date_achat) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$med_id, $quantite, $prix_unitaire]);

            // Mettre à jour le stock et le prix d'achat du médicament
            $stmt = $pdo->prepare("UPDATE medicaments SET quantite = quantite + ?, prix_achat = ? WHERE id = ?");
            $stmt->execute([$quantite, $prix_unitaire, $med_id]);

            $pdo->commit();
            $success = "Achat enregistré et stock mis à jour ✅";
        } catch(PDOException $e){
            $pdo->rollBack();
            error_log("Erreur lors de l'enregistrement de l'achat : " . $e->getMessage());
            $error = "Erreur lors de l'enregistrement de l'achat.";
        } catch(Exception $e){
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

$medicaments = $pdo->query("SELECT id, nom, quantite FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

try {
    $achats = $pdo->query("
        SELECT a.id, a.date_achat, m.nom AS medicament, a.quantite, a.prix_unitaire,
               (a.quantite * a.prix_unitaire) AS total_achat
        FROM achats a
        JOIN medicaments m ON m.id = a.medicament_id
        ORDER BY a.date_achat DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Si la colonne n'existe pas, l'ajouter automatiquement
    if (strpos($e->getMessage(), 'prix_unitaire') !== false || $e->getCode() == '42S22') {
        try {
            $pdo->exec("ALTER TABLE achats ADD COLUMN prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite");
            // Réessayer la requête
            $achats = $pdo->query("
                SELECT a.id, a.date_achat, m.nom AS medicament, a.quantite, a.prix_unitaire,
                       (a.quantite * a.prix_unitaire) AS total_achat
                FROM achats a
                JOIN medicaments m ON m.id = a.medicament_id
                ORDER BY a.date_achat DESC
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            error_log("Erreur lors de l'ajout de la colonne prix_unitaire : " . $e2->getMessage());
            $achats = [];
            $error = "Erreur de structure de base de données. Veuillez exécuter le script de migration.";
        }
    } else {
        error_log("Erreur lors de la récupération des achats : " . $e->getMessage());
        $achats = [];
        $error = "Erreur lors de la récupération des achats.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Achats - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Achats</h1>
            <p class="text-gray-600">Réapprovisionnez votre stock de médicaments</p>
        </div>
        <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
            ← Retour
        </a>
    </div>

    <?php if($success): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700"><?= $success ?></p>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700"><?= $error ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6 mb-8">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Nouvel achat</h2>
            <p class="text-sm text-gray-600 mt-1">Enregistrez un nouveau réapprovisionnement</p>
        </div>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?= csrfField() ?>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Médicament</label>
                <select name="medicament_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                    <option value="">Sélectionnez un médicament</option>
                    <?php foreach($medicaments as $m): ?>
                        <option value="<?= $m['id'] ?>">
                            <?= htmlspecialchars($m['nom']) ?> (Stock : <?= $m['quantite'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Quantité</label>
                <input type="number" name="quantite" min="1" step="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Ex: 10" required>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Prix unitaire (F CFA)</label>
                <input type="number" name="prix_unitaire" min="0" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Ex: 5000" required>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                    💾 Enregistrer
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Historique des achats</h2>
            <p class="text-sm text-gray-600 mt-1">Consultez tous les réapprovisionnements</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Médicament</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Quantité</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix unitaire</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if($achats): ?>
                        <?php foreach($achats as $a): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-semibold text-gray-700 text-sm"><?= date('d/m/Y', strtotime($a['date_achat'])) ?><br><span class="text-xs text-gray-500"><?= date('H:i', strtotime($a['date_achat'])) ?></span></td>
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($a['medicament']) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700"><?= $a['quantite'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-700"><?= number_format($a['prix_unitaire'], 0, ',', ' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                                <td class="px-4 py-3 text-right font-bold text-green-700"><?= number_format($a['total_achat'], 0, ',', ' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <div class="text-6xl mb-4">📭</div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Aucun achat enregistré</h3>
                                <p class="text-gray-600">Commencez par enregistrer un nouvel achat ci-dessus</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>
