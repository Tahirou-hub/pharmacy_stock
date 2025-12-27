<?php
require_once "includes/auth.php";
require_once "config/database.php";

if(!isset($_GET['id'])) {
    die("ID de vente manquant.");
}

$vente_id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT v.id, v.date_vente, v.total, u.username AS agent
    FROM ventes v
    LEFT JOIN users u ON u.id = v.agent_id
    WHERE v.id = ?
");
$stmt->execute([$vente_id]);
$vente = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$vente) {
    die("Vente introuvable.");
}

$stmt = $pdo->prepare("
    SELECT m.nom, vi.quantite, vi.prix_unitaire, (vi.quantite * vi.prix_unitaire) AS total_produit
    FROM vente_items vi
    JOIN medicaments m ON m.id = vi.medicament_id
    WHERE vi.vente_id = ?
");
$stmt->execute([$vente_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Facture #<?= $vente['id'] ?> - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
@media print {
    .no-print { display: none !important; }
    body { background: white; padding: 0; }
}
</style>
</head>
<body class="bg-gray-50 p-8 min-h-screen">
<div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg border border-gray-200 p-8">
    <div class="text-center border-b border-gray-200 pb-6 mb-6">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">💊 PHARMACY STOCK</h1>
        <p class="text-gray-600">Gestion de Stock Pharmacie</p>
        <div class="mt-6 flex flex-col md:flex-row justify-between gap-4 text-sm">
            <div class="text-left space-y-2">
                <p class="font-semibold text-gray-800">
                    <span class="text-gray-600">Facture N°:</span> 
                    <span class="text-blue-700 text-lg"><?= str_pad($vente['id'], 6, '0', STR_PAD_LEFT) ?></span>
                </p>
                <p class="text-gray-700">
                    <span class="text-gray-600">Date:</span> 
                    <?= date('d/m/Y', strtotime($vente['date_vente'])) ?> 
                    <span class="text-gray-500">à</span> 
                    <?= date('H:i', strtotime($vente['date_vente'])) ?>
                </p>
            </div>
            <div class="text-right space-y-2">
                <p class="font-semibold text-gray-800">
                    <span class="text-gray-600">Agent:</span> 
                    <span class="text-blue-700"><?= htmlspecialchars($vente['agent']) ?></span>
                </p>
            </div>
        </div>
    </div>

    <table class="w-full mb-6">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Produit</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Quantité</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Prix Unitaire</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php foreach($items as $item): ?>
            <tr>
                <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($item['nom']) ?></td>
                <td class="px-4 py-3 text-center">
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700"><?= $item['quantite'] ?></span>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-700"><?= number_format($item['prix_unitaire'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
                <td class="px-4 py-3 text-right font-bold text-blue-700"><?= number_format($item['total_produit'],0,',',' ') ?> <span class="text-xs text-gray-500">F CFA</span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="bg-green-50">
                <td colspan="3" class="px-4 py-4 text-right font-bold text-lg text-gray-800 uppercase">TOTAL</td>
                <td class="px-4 py-4 text-right font-bold text-3xl text-green-700"><?= number_format($vente['total'],0,',',' ') ?> <span class="text-lg text-green-800">F CFA</span></td>
            </tr>
        </tfoot>
    </table>

    <div class="text-center border-t border-gray-200 pt-6 mt-6 bg-gray-50 rounded-lg p-6">
        <p class="text-lg font-bold text-gray-800 mb-2">Merci de votre confiance !</p>
        <p class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Ticket de caisse - Pharmacy Stock</p>
    </div>

    <div class="flex gap-4 justify-end no-print mt-8">
        <a href="ventes.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
            ← Retour
        </a>
        <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
            🖨 Imprimer
        </button>
        <a href="facture_pdf.php?id=<?= $vente['id'] ?>" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold" target="_blank">
            💾 Télécharger PDF
        </a>
    </div>
</div>

</body>
</html>
