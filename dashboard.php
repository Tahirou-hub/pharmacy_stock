<?php
require_once "includes/auth.php";
require_once "config/database.php";

try {
    $totalMedicaments = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
    
    if (isAdmin()) {
        $totalVentes = $pdo->query("SELECT COUNT(*) FROM ventes")->fetchColumn();
        $totalAchats = $pdo->query("SELECT COUNT(*) FROM achats")->fetchColumn();
        $beneficeJour = $pdo->query("
            SELECT SUM(vi.quantite*(vi.prix_unitaire-vi.prix_achat)) 
            FROM vente_items vi 
            JOIN ventes v ON v.id=vi.vente_id 
            WHERE DATE(v.date_vente)=CURDATE()
        ")->fetchColumn() ?: 0;
        $ruptures = $pdo->query("
            SELECT COUNT(*) FROM medicaments WHERE quantite <= seuil_rupture
        ")->fetchColumn();
    } else {
        $totalVentes = 0;
        $totalAchats = 0;
        $beneficeJour = 0;
        $ruptures = 0;
    }
    
    $medicamentsFaibleStock = $pdo->query("
        SELECT id, nom, quantite, seuil_rupture 
        FROM medicaments 
        WHERE quantite <= seuil_rupture 
        ORDER BY quantite ASC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Tableau de bord</h1>
        <p class="text-gray-600">Bienvenue, <span class="font-semibold text-gray-900"><?= htmlspecialchars($_SESSION['username']) ?></span></p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl shadow-lg border border-blue-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Médicaments</p>
                    <p class="text-3xl font-bold text-gray-900"><?= $totalMedicaments ?></p>
                    <p class="text-xs text-gray-500 mt-1">Total en stock</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">💼</span>
                </div>
            </div>
        </div>
        
        <?php if (isAdmin()): ?>
        <div class="bg-gradient-to-br from-green-50 to-white rounded-xl shadow-lg border border-green-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Ventes</p>
                    <p class="text-3xl font-bold text-gray-900"><?= $totalVentes ?></p>
                    <p class="text-xs text-gray-500 mt-1">Total réalisées</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">🛒</span>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl shadow-lg border border-purple-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Achats</p>
                    <p class="text-3xl font-bold text-gray-900"><?= $totalAchats ?></p>
                    <p class="text-xs text-gray-500 mt-1">Total effectués</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">📦</span>
                </div>
            </div>
        </div>
        
        <div class="bg-gradient-to-br from-yellow-50 to-white rounded-xl shadow-lg border border-yellow-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Bénéfice Aujourd'hui</p>
                    <p class="text-3xl font-bold text-gray-900"><?= number_format($beneficeJour, 0, ',', ' ') ?></p>
                    <p class="text-xs text-gray-500 mt-1">F CFA</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">💰</span>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-gradient-to-br from-indigo-50 to-white rounded-xl shadow-lg border border-indigo-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Mes Ventes</p>
                    <p class="text-3xl font-bold text-gray-900"><?= $pdo->query("SELECT COUNT(*) FROM ventes WHERE agent_id = " . $_SESSION['user_id'])->fetchColumn() ?></p>
                    <p class="text-xs text-gray-500 mt-1">Ventes réalisées</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">📊</span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8 hover:shadow-xl transition-shadow duration-200">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Actions rapides</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="ventes.php" class="flex flex-col items-center justify-center p-6 border-2 border-green-200 rounded-xl bg-gradient-to-br from-green-50 to-white hover:from-green-100 hover:to-green-50 hover:border-green-300 hover:shadow-lg transition-all duration-200">
                <span class="text-4xl mb-2">🛒</span>
                <span class="font-semibold text-gray-900">Nouvelle Vente</span>
            </a>
            <?php if (isAdmin()): ?>
            <a href="achats.php" class="flex flex-col items-center justify-center p-6 border-2 border-blue-200 rounded-xl bg-gradient-to-br from-blue-50 to-white hover:from-blue-100 hover:to-blue-50 hover:border-blue-300 hover:shadow-lg transition-all duration-200">
                <span class="text-4xl mb-2">📦</span>
                <span class="font-semibold text-gray-900">Nouvel Achat</span>
            </a>
            <?php endif; ?>
            <a href="edit_medicament.php" class="flex flex-col items-center justify-center p-6 border-2 border-blue-200 rounded-xl bg-gradient-to-br from-blue-50 to-white hover:from-blue-100 hover:to-blue-50 hover:border-blue-300 hover:shadow-lg transition-all duration-200">
                <span class="text-4xl mb-2">➕</span>
                <span class="font-semibold text-gray-900">Ajouter Médicament</span>
            </a>
            <a href="index.php" class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-xl bg-gradient-to-br from-gray-50 to-white hover:from-gray-100 hover:to-gray-50 hover:border-gray-300 hover:shadow-lg transition-all duration-200">
                <span class="text-4xl mb-2">📈</span>
                <span class="font-semibold text-gray-900">Historique</span>
            </a>
        </div>
    </div>
    
    <?php if (isAdmin() && $ruptures > 0): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-bold text-yellow-900 mb-1">⚠️ <?= $ruptures ?> médicament(s) en rupture</h3>
                <p class="text-sm text-yellow-700">Action requise : Réapprovisionner ces produits</p>
            </div>
            <a href="rupture_stock.php" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold text-sm">
                Voir les ruptures
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($medicamentsFaibleStock)): ?>
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl transition-shadow duration-200">
        <h2 class="text-xl font-bold text-gray-900 mb-4">⚠️ Médicaments à faible stock</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Médicament</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Stock actuel</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Seuil</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($medicamentsFaibleStock as $med): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($med['nom']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?= $med['quantite'] == 0 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' ?>">
                                <?= $med['quantite'] ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 font-semibold"><?= $med['seuil_rupture'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if($med['quantite'] == 0): ?>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-700">🔴 Rupture</span>
                            <?php else: ?>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-700">⚠️ Faible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (isAdmin()): ?>
        <div class="mt-6 text-center">
            <a href="rupture_stock.php" class="inline-block px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-semibold">
                Voir toutes les ruptures
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-12 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Tout est en ordre !</h2>
        <p class="text-gray-600">Aucun médicament en rupture de stock</p>
    </div>
    <?php endif; ?>
</main>

</body>
</html>
