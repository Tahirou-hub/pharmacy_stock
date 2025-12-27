<?php
require_once "includes/auth.php";
require_once "config/database.php";

$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$conditions = [];
$params = [];
if(!empty($_GET['date_from'])){
    $conditions[] = "v.date_vente >= ?";
    $params[] = $_GET['date_from']." 00:00:00";
}
if(!empty($_GET['date_to'])){
    $conditions[] = "v.date_vente <= ?";
    $params[] = $_GET['date_to']." 23:59:59";
}
if(!empty($_GET['medicament_id'])){
    $conditions[] = "vi.medicament_id = ?";
    $params[] = $_GET['medicament_id'];
}
$where = $conditions ? "WHERE ".implode(" AND ", $conditions) : "";

try {
    $totalMedicaments = $pdo->query("SELECT COUNT(*) FROM medicaments")->fetchColumn();
    $totalVentes = $pdo->query("SELECT COUNT(*) FROM ventes")->fetchColumn();
    $totalAchats = $pdo->query("SELECT COUNT(*) FROM achats")->fetchColumn();

    if (!isAdmin() && isset($_SESSION['user_id'])) {
        $conditions[] = "v.agent_id = ?";
        $params[] = $_SESSION['user_id'];
        $where = $conditions ? "WHERE ".implode(" AND ", $conditions) : "";
    }
    
    $countSql = "SELECT COUNT(*) FROM ventes v JOIN vente_items vi ON v.id=vi.vente_id $where";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRows = $stmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    if (!isAdmin() && isset($_SESSION['user_id'])) {
        $conditions[] = "v.agent_id = ?";
        $params[] = $_SESSION['user_id'];
        $where = $conditions ? "WHERE ".implode(" AND ", $conditions) : "";
    }
    
    $sql = "
        SELECT v.date_vente, u.username AS agent, m.nom AS medicament,
               vi.quantite, vi.prix_unitaire, vi.prix_achat,
               (vi.quantite * (vi.prix_unitaire - vi.prix_achat)) AS benefice,
               (vi.quantite * vi.prix_unitaire) AS total_produit
        FROM ventes v
        JOIN vente_items vi ON vi.vente_id = v.id
        JOIN medicaments m ON m.id = vi.medicament_id
        JOIN users u ON u.id = v.agent_id
        $where
        ORDER BY v.date_vente DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $whereBenefice = [];
    $whereParams = [];
    if(!empty($_GET['date_from'])){
        $whereBenefice[] = "v.date_vente >= ?";
        $whereParams[] = $_GET['date_from']." 00:00:00";
    }
    if(!empty($_GET['date_to'])){
        $whereBenefice[] = "v.date_vente <= ?";
        $whereParams[] = $_GET['date_to']." 23:59:59";
    }
    if(!empty($_GET['medicament_id'])){
        $whereBenefice[] = "vi.medicament_id = ?";
        $whereParams[] = $_GET['medicament_id'];
    }
    $whereBeneficeSql = $whereBenefice ? "WHERE ".implode(" AND ", $whereBenefice) : "";
    $totalBeneficePeriodeSql = "SELECT SUM(vi.quantite*(vi.prix_unitaire-vi.prix_achat)) 
                                FROM vente_items vi 
                                JOIN ventes v ON v.id=vi.vente_id 
                                $whereBeneficeSql";
    $stmt = $pdo->prepare($totalBeneficePeriodeSql);
    $stmt->execute($whereParams);
    $totalBeneficePeriode = $stmt->fetchColumn() ?: 0;

    $dateDebutText = !empty($_GET['date_from']) ? $_GET['date_from'] : 'début';
    $dateFinText = !empty($_GET['date_to']) ? $_GET['date_to'] : 'fin';
    if(!empty($_GET['medicament_id'])){
        $stmtProd = $pdo->prepare("SELECT nom FROM medicaments WHERE id=?");
        $stmtProd->execute([$_GET['medicament_id']]);
        $produitText = $stmtProd->fetchColumn() ?: 'Produit inconnu';
    } else {
        $produitText = 'Tous les produits';
    }

    $med_list = $pdo->query("SELECT id, nom FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

    $benefices = [
        'jour' => $pdo->query("SELECT SUM(vi.quantite*(vi.prix_unitaire-vi.prix_achat)) 
                               FROM vente_items vi 
                               JOIN ventes v ON v.id=vi.vente_id 
                               WHERE DATE(v.date_vente)=CURDATE()")->fetchColumn() ?: 0,
        'mois' => $pdo->query("SELECT SUM(vi.quantite*(vi.prix_unitaire-vi.prix_achat)) 
                               FROM vente_items vi 
                               JOIN ventes v ON v.id=vi.vente_id 
                               WHERE MONTH(v.date_vente)=MONTH(CURDATE()) AND YEAR(v.date_vente)=YEAR(CURDATE())")->fetchColumn() ?: 0,
        'annee' => $pdo->query("SELECT SUM(vi.quantite*(vi.prix_unitaire-vi.prix_achat)) 
                                FROM vente_items vi 
                                JOIN ventes v ON v.id=vi.vente_id 
                                WHERE YEAR(v.date_vente)=YEAR(CURDATE())")->fetchColumn() ?: 0,
    ];

    $topProduits = $pdo->query("
        SELECT m.nom, SUM(vi.quantite) AS total_vendu
        FROM vente_items vi
        JOIN medicaments m ON m.id = vi.medicament_id
        GROUP BY m.nom
        ORDER BY total_vendu DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur SQL : ".$e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rapports - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">📊 Rapports & Statistiques</h1>
            <p class="text-gray-600"><?= isAdmin() ? "Analysez toutes les ventes" : "Consultez vos ventes personnelles" ?></p>
        </div>
        <div class="flex gap-2">
            <a href="ventes.php" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                🛒 Ventes
            </a>
            <?php if (isAdmin()): ?>
            <a href="achats.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                📦 Achats
            </a>
            <?php endif; ?>
            <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                🏠 Dashboard
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6 mb-8">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">🔍 Filtres de recherche</h2>
            <p class="text-sm text-gray-600 mt-1">Affinez vos résultats selon vos critères</p>
        </div>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-<?= isAdmin() ? '5' : '4' ?> gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date début</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Date fin</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Produit</label>
                <select name="medicament_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">Tous</option>
                    <?php foreach($med_list as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (!empty($_GET['medicament_id']) && $_GET['medicament_id']==$m['id'])?'selected':'' ?>><?= htmlspecialchars($m['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    🔍 Filtrer
                </button>
            </div>
            <?php if (isAdmin()): ?>
            <div class="flex items-end">
                <a href="export_rapport.php?<?= http_build_query($_GET) ?>" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-center">
                    ⬇️ Exporter CSV
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">💰 Aujourd'hui</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($benefices['jour'],0,',',' ') ?></p>
                    <p class="text-xs text-gray-500 mt-1">F CFA</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">💰</span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">📅 Ce mois</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($benefices['mois'],0,',',' ') ?></p>
                    <p class="text-xs text-gray-500 mt-1">F CFA</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📅</span>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">📆 Cette année</p>
                    <p class="text-2xl font-bold text-gray-900"><?= number_format($benefices['annee'],0,',',' ') ?></p>
                    <p class="text-xs text-gray-500 mt-1">F CFA</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <span class="text-2xl">📆</span>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6 mb-8">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">🏆 Top 5 Produits les plus vendus</h2>
            <p class="text-sm text-gray-600 mt-1">Les produits les plus populaires</p>
        </div>
        <div class="space-y-3">
            <?php foreach($topProduits as $index => $p): ?>
                <div class="flex justify-between items-center p-5 bg-gray-50 rounded-lg border-l-4 <?= $index === 0 ? 'border-yellow-500' : ($index === 1 ? 'border-gray-400' : 'border-gray-300') ?> hover:bg-gray-100">
                    <div class="flex items-center gap-4">
                        <span class="text-2xl font-bold text-gray-400">#<?= $index + 1 ?></span>
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($p['nom']) ?></span>
                    </div>
                    <span class="font-bold text-blue-700"><?= number_format($p['total_vendu'], 0, ',', ' ') ?> <span class="text-sm font-semibold">unités</span></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">🧾 Historique des ventes</h2>
            <p class="text-sm text-gray-600 mt-1">Détails de toutes les transactions</p>
        </div>

        <?php if (isAdmin()): ?>
        <div class="mb-8 p-6 bg-green-50 border-l-4 border-green-600 rounded-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <p class="text-sm font-semibold text-green-800 mb-2">💰 Bénéfice réalisé</p>
                <p class="text-sm text-gray-700">
                    Période : <span class="font-semibold"><?= $dateDebutText ?></span> au <span class="font-semibold"><?= $dateFinText ?></span>
                </p>
                <p class="text-sm text-gray-600 mt-1">
                    Produit : <span class="font-semibold"><?= htmlspecialchars($produitText) ?></span>
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-green-800 mb-1">Total</p>
                <p class="text-3xl font-bold text-green-700"><?= number_format($totalBeneficePeriode,0,',',' ') ?></p>
                <p class="text-sm font-semibold text-green-800">F CFA</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Agent</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produit</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Qte</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix U.</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
                        <?php if (isAdmin()): ?>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Bénéfice</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if($ventes): foreach($ventes as $v): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-700 text-sm"><?= date('d/m/Y', strtotime($v['date_vente'])) ?><br><span class="text-xs text-gray-500"><?= date('H:i', strtotime($v['date_vente'])) ?></span></td>
                        <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($v['agent']) ?></td>
                        <td class="px-4 py-3 font-semibold text-gray-800"><?= htmlspecialchars($v['medicament']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700"><?= $v['quantite'] ?></span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-700"><?= number_format($v['prix_unitaire'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                        <td class="px-4 py-3 text-right font-bold text-blue-700"><?= number_format($v['total_produit'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                        <?php if (isAdmin()): ?>
                        <td class="px-4 py-3 text-right font-bold text-green-700"><?= number_format($v['benefice'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="<?= isAdmin() ? '7' : '6' ?>" class="px-4 py-12 text-center">
                            <div class="text-6xl mb-4">📭</div>
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Aucune vente trouvée</h3>
                            <p class="text-gray-600">Ajustez les filtres pour voir les résultats</p>
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
