<?php
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "config/database.php";

$medicaments = $pdo->query("SELECT id, nom, prix, quantite FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

$conditions = [];
$params = [];
if(!empty($_GET['date_from'])) {
    $conditions[] = "v.date_vente >= ?";
    $params[] = $_GET['date_from'] . " 00:00:00";
}
if(!empty($_GET['date_to'])) {
    $conditions[] = "v.date_vente <= ?";
    $params[] = $_GET['date_to'] . " 23:59:59";
}
if(!empty($_GET['medicament_id'])) {
    $conditions[] = "vi.medicament_id = ?";
    $params[] = $_GET['medicament_id'];
}
$where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

$sql = "
SELECT v.id AS vente_id, v.date_vente, u.username AS agent,
       m.nom AS medicament, vi.quantite, vi.prix_unitaire, vi.prix_achat,
       (vi.quantite * vi.prix_unitaire) AS total_produit,
       (vi.quantite * (vi.prix_unitaire - vi.prix_achat)) AS benefice
FROM ventes v
LEFT JOIN users u ON u.id = v.agent_id
LEFT JOIN vente_items vi ON vi.vente_id = v.id
LEFT JOIN medicaments m ON m.id = vi.medicament_id
$where
ORDER BY v.date_vente DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$med_list = $pdo->query("SELECT id, nom FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventes - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Ventes</h1>
            <p class="text-gray-600">Enregistrez vos ventes et consultez l'historique</p>
        </div>
        <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
            ← Retour
        </a>
    </div>

    <?php if(isset($_GET['success']) && isset($_GET['vente_id'])): ?>
        <div id="successMsg" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700">✅ Vente effectuée avec succès ! La facture s'ouvre dans un nouvel onglet.</p>
        </div>
        <script>
            setTimeout(() => { document.getElementById('successMsg')?.remove(); }, 5000);
            window.open('facture.php?id=<?= (int)$_GET['vente_id'] ?>', '_blank');
        </script>
    <?php elseif(isset($_GET['error'])): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700">❌ <?= htmlspecialchars($_GET['error']) ?></p>
        </div>
    <?php endif; ?>

    <div class="flex gap-2 mb-6 bg-white p-2 rounded-lg border border-gray-200 inline-flex">
        <button id="tabNew" class="px-6 py-2 rounded-lg font-semibold bg-green-600 text-white">
            ➕ Nouvelle Vente
        </button>
        <button id="tabHistory" class="px-6 py-2 rounded-lg font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300">
            📊 Historique
        </button>
    </div>

    <div id="newSaleSection" class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-8 hover:shadow-xl transition-shadow duration-200">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Nouvelle Vente</h2>
            <p class="text-sm text-gray-600 mt-1">Sélectionnez les produits et quantités</p>
        </div>
        <div class="mb-6">
            <input type="text" id="searchInput" placeholder="🔍 Rechercher un médicament..." 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
        </div>

        <form action="add_vente.php" method="POST">
            <?= csrfField() ?>
            <div class="flex flex-col lg:flex-row gap-6">
                <div class="flex-1 overflow-x-auto">
                    <table id="medicamentsTable" class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produit</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Prix (F CFA)</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Stock</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Quantité</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Sélection</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($medicaments as $m): ?>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900 med-name"><?= htmlspecialchars($m['nom']) ?></td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-800"><?= number_format($m['prix'],0,',',' ') ?> F CFA</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?= $m['quantite'] > 10 ? 'bg-green-100 text-green-700' : ($m['quantite'] > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') ?>">
                                        <?= $m['quantite'] ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="number" name="quantite[<?= $m['id'] ?>]" value="1" min="1" max="<?= $m['quantite'] ?>" 
                                           class="w-20 px-2 py-2 border border-gray-300 rounded-lg text-center font-semibold quantityInput">
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="produits[]" value="<?= $m['id'] ?>" 
                                           class="w-5 h-5 rounded border-gray-300 text-green-600 focus:ring-green-500 selectProd cursor-pointer">
                                    <input type="hidden" name="prix[<?= $m['id'] ?>]" value="<?= $m['prix'] ?>">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="w-full lg:w-80 p-6 bg-green-50 border-2 border-green-200 rounded-lg sticky top-6 h-fit">
                    <h3 class="font-bold text-lg mb-4 text-green-900 flex items-center gap-2">
                        <span>📝</span> Récapitulatif
                    </h3>
                    <ul id="recapList" class="space-y-2 mb-6 min-h-[150px] max-h-[400px] overflow-y-auto">
                        <li class="text-gray-500 text-sm text-center py-8">Aucun produit sélectionné</li>
                    </ul>
                    <div class="border-t-2 border-green-300 pt-4 bg-white rounded-lg p-4">
                        <p class="text-sm font-semibold text-right text-green-900 mb-2">Total :</p>
                        <p class="text-right">
                            <span id="totalAmount" class="text-3xl font-bold text-green-700">0</span>
                            <span class="text-sm font-semibold text-green-800 ml-2">F CFA</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-6">
                <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                    ← Retour
                </a>
                <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    💾 Valider la vente
                </button>
            </div>
        </form>
    </div>

    <div id="historySection" class="hidden bg-white rounded-xl shadow-lg border border-gray-200 p-6 hover:shadow-xl transition-shadow duration-200">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">Historique des ventes</h2>
            <p class="text-sm text-gray-600 mt-1">Consultez toutes vos transactions</p>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                    <option value="">Tous les produits</option>
                    <?php foreach($med_list as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (isset($_GET['medicament_id']) && $_GET['medicament_id']==$m['id'])?'selected':'' ?>><?= htmlspecialchars($m['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    🔍 Filtrer
                </button>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Agent</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produit</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Quantité</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix unitaire</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total produit</th>
                        <?php if (isAdmin()): ?>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Bénéfice</th>
                        <?php endif; ?>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if(!empty($ventes)): ?>
                        <?php foreach($ventes as $v): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-700 text-sm"><?= date('d/m/Y', strtotime($v['date_vente'])) ?><br><span class="text-xs text-gray-500"><?= date('H:i', strtotime($v['date_vente'])) ?></span></td>
                            <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($v['agent'] ?? '—') ?></td>
                            <td class="px-4 py-3 font-semibold text-gray-800"><?= htmlspecialchars($v['medicament']) ?></td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700"><?= $v['quantite'] ?></span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-700"><?= number_format($v['prix_unitaire'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                            <td class="px-4 py-3 text-right font-bold text-blue-700"><?= number_format($v['total_produit'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                            <?php if (isAdmin()): ?>
                            <td class="px-4 py-3 text-right font-bold text-green-700"><?= number_format($v['benefice'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                            <?php endif; ?>
                            <td class="px-4 py-3 text-center">
                                <a href="facture.php?id=<?= $v['vente_id'] ?>" target="_blank" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-semibold">
                                    🧾 Facture
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= isAdmin() ? '8' : '7' ?>" class="px-4 py-12 text-center">
                                <div class="text-6xl mb-4">📭</div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Aucune vente trouvée</h3>
                                <p class="text-gray-600">Ajustez les filtres ou effectuez une première vente</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
const tabNew = document.getElementById('tabNew');
const tabHistory = document.getElementById('tabHistory');
const newSale = document.getElementById('newSaleSection');
const history = document.getElementById('historySection');

tabNew.addEventListener('click', () => {
    newSale.classList.remove('hidden');
    history.classList.add('hidden');
    tabNew.className = 'px-6 py-2 rounded-lg font-semibold bg-green-600 text-white';
    tabHistory.className = 'px-6 py-2 rounded-lg font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300';
});

tabHistory.addEventListener('click', () => {
    newSale.classList.add('hidden');
    history.classList.remove('hidden');
    tabHistory.className = 'px-6 py-2 rounded-lg font-semibold bg-green-600 text-white';
    tabNew.className = 'px-6 py-2 rounded-lg font-semibold bg-gray-200 text-gray-700 hover:bg-gray-300';
});

document.getElementById('searchInput')?.addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#medicamentsTable tbody tr').forEach(tr => {
        const name = tr.querySelector('.med-name').textContent.toLowerCase();
        tr.style.display = name.includes(filter) ? '' : 'none';
    });
});

function updateRecap() {
    const recap = document.getElementById('recapList');
    const totalAmount = document.getElementById('totalAmount');
    recap.innerHTML = '';
    let total = 0;
    let anySelected = false;

    document.querySelectorAll('.selectProd').forEach(cb => {
        if(cb.checked) {
            anySelected = true;
            const row = cb.closest('tr');
            const name = row.querySelector('.med-name').textContent;
            const qty = parseInt(row.querySelector('.quantityInput').value) || 0;
            const price = parseFloat(row.querySelector('input[name^="prix"]').value);
            const subTotal = qty * price;
            total += subTotal;

            const li = document.createElement('li');
            li.className = 'text-sm text-gray-700';
            li.textContent = `${name} : ${qty} x ${price.toLocaleString('fr-FR')} = ${subTotal.toLocaleString('fr-FR')} F CFA`;
            recap.appendChild(li);
        }
    });

    if(!anySelected) recap.innerHTML = '<li class="text-gray-500 text-sm text-center py-8">Aucun produit sélectionné</li>';
    totalAmount.textContent = total.toLocaleString('fr-FR');
}

document.querySelectorAll('.selectProd').forEach(cb => cb.addEventListener('change', updateRecap));
document.querySelectorAll('.quantityInput').forEach(inp => inp.addEventListener('input', updateRecap));
</script>
</body>
</html>
