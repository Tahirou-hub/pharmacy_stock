<?php
require_once "includes/auth.php";
require_once "config/database.php";

// --- Récupération des médicaments pour la nouvelle vente ---
$medicaments = $pdo->query("SELECT id, nom, prix, quantite FROM medicaments ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- Filtres pour l'historique ---
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

// --- Historique des ventes ---
$sql = "
SELECT v.id AS vente_id, v.date_vente, u.username AS agent,
       m.nom AS medicament, vi.quantite, vi.prix_unitaire, (vi.quantite * vi.prix_unitaire) AS total_produit
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

// --- Liste des médicaments pour filtre ---
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
<body class="bg-gray-50 text-gray-800">

<header class="bg-green-700 text-white shadow p-4">
  <div class="max-w-6xl mx-auto flex justify-between items-center">
    <h1 class="text-xl font-bold">💊 PHARMACY STOCK</h1>
    <nav class="space-x-4">
      <a href="index.php" class="hover:underline">Accueil</a>
      <a href="ventes.php" class="font-semibold underline">Ventes</a>
      <a href="rapport.php" class="hover:underline">Rapports</a>
      <a href="logout.php" class="hover:underline text-red-300">Déconnexion</a>
    </nav>
  </div>
</header>

<main class="max-w-6xl mx-auto mt-8 bg-white rounded-xl shadow-lg p-6">

<?php if(isset($_GET['success']) && isset($_GET['vente_id'])): ?>
  <!-- ✅ Message de succès -->
  <div id="successMsg" class="fixed top-6 right-6 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg text-lg font-semibold z-50 animate-fade-in">
    ✅ Vente effectuée avec succès !
  </div>
  <script>
    setTimeout(() => {
      const msg = document.getElementById('successMsg');
      if (msg) msg.remove();
    }, 5000);

    // Ouvrir automatiquement la facture PDF
    window.open('rapport.php?id=<?= (int)$_GET['vente_id'] ?>', '_blank');
  </script>
<?php elseif(isset($_GET['error'])): ?>
  <div class="mb-4 p-4 bg-red-600 text-white rounded-lg">
    ❌ <?= htmlspecialchars($_GET['error']) ?>
  </div>
<?php endif; ?>

<!-- Onglets et reste du code existant (Nouvelle vente + Historique) -->
<!-- ... Copie exactement ton code existant ici ... -->

</main>
<footer class="mt-10 bg-green-700 text-white text-center py-3 text-sm">
  &copy; <?= date('Y') ?> Pharmacy Stock — Tous droits réservés.
</footer>

<script>
// Onglets, recherche, récapitulatif dynamique
// ... Copie exactement ton code JS actuel ...
</script>
</body>
</html>
