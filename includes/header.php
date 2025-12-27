<nav class="bg-white shadow px-6 py-4 flex justify-between items-center">
    <a href="index.php" class="text-xl font-bold text-blue-600">Pharmacy Stock</a>
    <div class="space-x-4">
        <span class="text-gray-700">Bonjour, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="logout.php" class="text-red-500 hover:underline">Déconnexion</a>
    </div>
</nav>
