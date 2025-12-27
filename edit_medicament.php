<?php
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "config/database.php";

$med = [
    'nom' => '',
    'description' => '',
    'prix' => '',
    'prix_achat' => '',
    'quantite' => '',
    'seuil_rupture' => 10
];

$edit = false;

if(isset($_GET['id'])) {
    $edit = true;
    $stmt = $pdo->prepare("SELECT * FROM medicaments WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $med = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$med) {
        die("Médicament introuvable !");
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $edit ? "Éditer" : "Ajouter" ?> Médicament - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="max-w-2xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= $edit ? "✏️ Modifier" : "➕ Ajouter" ?> un médicament</h1>
                <p class="text-gray-600"><?= $edit ? "Modifiez les informations du médicament" : "Ajoutez un nouveau médicament à l'inventaire" ?></p>
            </div>
            <a href="medicaments.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                ← Retour
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
            <form action="api/<?= $edit ? "edit_medicament.php" : "add_medicament.php" ?>" method="POST" class="space-y-6">
                <?= csrfField() ?>
                <?php if($edit): ?>
                    <input type="hidden" name="id" value="<?= $med['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Nom du médicament *</label>
                        <input type="text" name="nom" required value="<?= htmlspecialchars($med['nom']) ?>" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                               placeholder="Ex: Paracétamol 500mg">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                                  placeholder="Description du médicament"><?= htmlspecialchars($med['description']) ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Prix d'achat (F CFA) *</label>
                        <input type="number" name="prix_achat" step="0.01" min="0" required 
                               value="<?= $med['prix_achat'] ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                               placeholder="Ex: 5000">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Prix de vente (F CFA) *</label>
                        <input type="number" name="prix" step="0.01" min="0" required 
                               value="<?= $med['prix'] ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                               placeholder="Ex: 7500">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Quantité en stock *</label>
                        <input type="number" name="quantite" min="0" required 
                               value="<?= $med['quantite'] ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                               placeholder="Ex: 100">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Seuil de rupture *</label>
                        <input type="number" name="seuil_rupture" min="1" required 
                               value="<?= $med['seuil_rupture'] ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                               placeholder="Ex: 10">
                        <p class="text-sm text-gray-500 mt-2">⚠️ Alerte lorsque le stock atteint ce niveau</p>
                    </div>
                </div>

                <div class="flex justify-between items-center pt-6 border-t border-gray-200 mt-8">
                    <a href="medicaments.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                        Annuler
                    </a>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                        <?= $edit ? "💾 Mettre à jour" : "➕ Ajouter" ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>
