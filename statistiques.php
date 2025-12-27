<?php
require_once "includes/auth.php";
require_once "config/database.php";

try {
    $totalMedicaments = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
    $totalVentes = $pdo->query("SELECT COUNT(*) FROM ventes")->fetchColumn();
    $totalAchats = $pdo->query("SELECT COUNT(*) FROM achats")->fetchColumn();
    
    $montantVentes = $pdo->query("
        SELECT SUM(vi.quantite * vi.prix_unitaire) 
        FROM vente_items vi
    ")->fetchColumn() ?: 0;
    
    $montantAchats = $pdo->query("
        SELECT SUM(a.quantite * a.prix_unitaire) 
        FROM achats a
    ")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    die("Erreur SQL : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistiques - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">📊 Statistiques Globales</h1>
        <p class="text-gray-600">Vue d'ensemble de votre activité</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl shadow-lg border border-blue-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Total des médicaments</p>
                    <p class="text-3xl font-bold text-gray-900"><?= $totalMedicaments ?></p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">💼</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-white rounded-xl shadow-lg border border-green-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Total des ventes</p>
                    <p class="text-3xl font-bold text-gray-900"><?= $totalVentes ?></p>
                    <p class="text-xs text-gray-500 mt-1">Nombre de transactions</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">🛒</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl shadow-lg border border-purple-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Total des achats</p>
                    <p class="text-3xl font-bold text-gray-900"><?= $totalAchats ?></p>
                    <p class="text-xs text-gray-500 mt-1">Nombre de réapprovisionnements</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">📦</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-50 to-white rounded-xl shadow-lg border border-green-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Montant total des ventes</p>
                    <p class="text-3xl font-bold text-green-700"><?= number_format($montantVentes, 0, ',', ' ') ?></p>
                    <p class="text-xs text-gray-500 mt-1">F CFA</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">💰</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl shadow-lg border border-purple-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Montant total des achats</p>
                    <p class="text-3xl font-bold text-purple-700"><?= number_format($montantAchats, 0, ',', ' ') ?></p>
                    <p class="text-xs text-gray-500 mt-1">F CFA</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">💳</span>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-50 to-white rounded-xl shadow-lg border border-yellow-100 p-6 hover:shadow-xl transition-shadow duration-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1 font-medium">Bénéfice net</p>
                    <p class="text-3xl font-bold <?= ($montantVentes - $montantAchats) >= 0 ? 'text-green-700' : 'text-red-700' ?>">
                        <?= number_format($montantVentes - $montantAchats, 0, ',', ' ') ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">F CFA</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-md">
                    <span class="text-2xl">📈</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Actions rapides</h2>
        <div class="flex gap-4">
            <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                ← Retour au Dashboard
            </a>
            <a href="index.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                📊 Voir les rapports détaillés
            </a>
        </div>
    </div>
</main>

</body>
</html>
