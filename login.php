<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Card principale avec effet de profondeur -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 border border-gray-100 relative overflow-hidden">
            <!-- Effet de fond décoratif -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-br from-blue-100 to-transparent rounded-bl-full opacity-50"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-gradient-to-tr from-purple-100 to-transparent rounded-tr-full opacity-50"></div>
            
            <div class="relative z-10">
                <!-- Header avec logo stylisé -->
                <div class="text-center mb-8">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl flex items-center justify-center text-white text-4xl mx-auto mb-4 shadow-lg transform hover:scale-105 transition-transform duration-200">
                        💊
                    </div>
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent mb-2">
                        Pharmacy Stock
                    </h1>
                    <p class="text-gray-600 font-medium">Gestion de stock pharmaceutique</p>
                </div>

                <!-- Message d'erreur stylisé -->
                <?php if(isset($_GET['error'])): ?>
                    <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg shadow-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-red-600 text-xl">❌</span>
                            <p class="text-sm text-red-700 font-semibold"><?= htmlspecialchars($_GET['error']) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulaire de connexion -->
                <form action="api/auth.php" method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <span class="flex items-center gap-2">
                                <span>👤</span>
                                <span>Nom d'utilisateur</span>
                            </span>
                        </label>
                        <input type="text" name="username" required autocomplete="username"
                               class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 bg-gray-50 hover:bg-white"
                               placeholder="Entrez votre nom d'utilisateur"/>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            <span class="flex items-center gap-2">
                                <span>🔒</span>
                                <span>Mot de passe</span>
                            </span>
                        </label>
                        <input type="password" name="password" required autocomplete="current-password"
                               class="w-full px-4 py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all duration-200 bg-gray-50 hover:bg-white"
                               placeholder="Entrez votre mot de passe"/>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-4 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:from-blue-700 hover:to-indigo-700 focus:ring-4 focus:ring-blue-300 focus:ring-offset-2 transition-all duration-200 transform hover:scale-[1.02]">
                        🔐 Se connecter
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <p class="text-center text-xs text-gray-500">
                        © <?= date('Y') ?> Pharmacy Stock - Tous droits réservés
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Message d'aide optionnel -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                💡 Besoin d'aide ? Contactez votre administrateur
            </p>
        </div>
    </div>
</body>
</html>
