<?php
require_once "includes/auth.php";
require_once "config/database.php";

if (!isAdmin()) {
    header("Location: dashboard.php?error=Accès refusé : Seuls les administrateurs peuvent consulter les ruptures de stock.");
    exit;
}

try {
    $stmt = $pdo->query("
        SELECT id, nom, description, prix, prix_achat, quantite, seuil_rupture
        FROM medicaments
        WHERE quantite <= seuil_rupture
        ORDER BY quantite ASC
    ");
    $ruptures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rupture de Stock - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">⚠️ Ruptures de Stock</h1>
            <p class="text-gray-600">Médicaments nécessitant un réapprovisionnement urgent</p>
        </div>
        <div class="flex gap-2">
            <a href="achats.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                🛒 Passer un achat
            </a>
            <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                ← Retour
            </a>
        </div>
    </div>

    <?php if(!empty($ruptures)): ?>
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h2 class="text-lg font-bold text-yellow-900 mb-1">⚠️ <?= count($ruptures) ?> médicament(s) nécessitent un réapprovisionnement</h2>
            <p class="text-sm text-yellow-700">Action requise : Réapprovisionnez ces produits rapidement</p>
        </div>
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">Liste des ruptures</h2>
                <p class="text-sm text-gray-600 mt-1">Détails des médicaments en rupture ou proche de rupture</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nom</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix Achat</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix Vente</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Stock</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Seuil</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($ruptures as $m): 
                            if($m['quantite'] == 0) {
                                $status = 'Rupture totale';
                                $statusClass = 'bg-red-100 text-red-700';
                                $rowClass = 'bg-red-50';
                            } elseif($m['quantite'] <= $m['seuil_rupture'] / 2) {
                                $status = 'Critique';
                                $statusClass = 'bg-orange-100 text-orange-700';
                                $rowClass = 'bg-orange-50';
                            } else {
                                $status = 'Attention';
                                $statusClass = 'bg-yellow-100 text-yellow-700';
                                $rowClass = 'bg-yellow-50';
                            }
                        ?>
                            <tr class="<?= $rowClass ?> hover:bg-opacity-80">
                                <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($m['nom']) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars(substr($m['description'] ?? '', 0, 50)) ?><?= strlen($m['description'] ?? '') > 50 ? '...' : '' ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-700"><?= number_format($m['prix_achat'], 0, ',', ' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                                <td class="px-4 py-3 text-right font-bold text-green-700"><?= number_format($m['prix'], 0, ',', ' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?= $m['quantite'] == 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                        <?= $m['quantite'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700 font-semibold"><?= $m['seuil_rupture'] ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?= $statusClass ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-12 text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">Aucune rupture de stock</h2>
            <p class="text-gray-600">Tous les médicaments sont bien approvisionnés !</p>
        </div>
    <?php endif; ?>
</main>

</body>
</html>
