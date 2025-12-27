<?php
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "config/database.php";

// Pagination
$limit = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Compter le total
$totalCount = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
$totalPages = ceil($totalCount / $limit);

// Récupérer les médicaments avec pagination
$stmt = $pdo->prepare("SELECT * FROM medicaments ORDER BY date_creation DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$medicaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Médicaments - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Médicaments</h1>
            <p class="text-gray-600">Gérez votre inventaire de médicaments</p>
        </div>
        <a href="edit_medicament.php" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
            ➕ Ajouter un médicament
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Liste des Médicaments</h2>
            <p class="text-sm text-gray-600 mt-1">Total : <?= $totalCount ?> médicament(s) - Page <?= $page ?>/<?= $totalPages ?></p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nom</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Description</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix Achat</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix Vente</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Stock</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Seuil</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if(!empty($medicaments)): ?>
                        <?php foreach($medicaments as $med): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-mono text-gray-500">#<?= str_pad($med['id'], 4, '0', STR_PAD_LEFT) ?></td>
                            <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($med['nom']) ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars(substr($med['description'] ?? '', 0, 60)) ?><?= strlen($med['description'] ?? '') > 60 ? '...' : '' ?></td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-700"><?= number_format($med['prix_achat'] ?? 0, 0, ',', ' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                            <td class="px-4 py-3 text-right font-bold text-green-700"><?= number_format($med['prix'] ?? 0, 0, ',', ' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?= $med['quantite'] <= $med['seuil_rupture'] ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                                    <?= $med['quantite'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-700 font-semibold"><?= $med['seuil_rupture'] ?></td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex gap-2 justify-center">
                                    <a href="edit_medicament.php?id=<?= $med['id'] ?>" class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm font-semibold">
                                        ✏️ Éditer
                                    </a>
                                    <?php if(isAdmin()): ?>
                                        <form action="api/delete_medicament.php" method="POST" style="display:inline;" onsubmit="return confirm('⚠️ Voulez-vous vraiment supprimer ce médicament ?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= $med['id'] ?>">
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-semibold">
                                                🗑️ Supprimer
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="text-6xl mb-4">📭</div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Aucun médicament trouvé</h3>
                                <p class="text-gray-600 mb-4">Commencez par ajouter votre premier médicament</p>
                                <a href="edit_medicament.php" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                                    ➕ Ajouter un médicament
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex justify-center items-center gap-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                    ← Précédent
                </a>
            <?php endif; ?>
            
            <span class="px-4 py-2 text-gray-700 font-semibold">
                Page <?= $page ?> sur <?= $totalPages ?>
            </span>
            
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                    Suivant →
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
