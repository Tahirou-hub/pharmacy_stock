<?php
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "includes/validation.php";

// Fonction pour obtenir les exigences du mot de passe
if (!function_exists('getPasswordRequirements')) {
    function getPasswordRequirements() {
        return "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.";
    }
}
require_once "config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {
    requireCSRFToken();
    
    $username = validateUsername($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_id = $_SESSION['user_id'];

    if (!$username) {
        $message = "❌ Nom d'utilisateur invalide (3-50 caractères alphanumériques).";
    } else {
        try {
            if (!empty($password)) {
                $validPassword = validatePassword($password);
                if (!$validPassword) {
                    $message = "❌ " . getPasswordRequirements();
                } else {
                    $hash = password_hash($validPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username=?, password_hash=? WHERE id=?");
                    $stmt->execute([$username, $hash, $user_id]);
                    $_SESSION['username'] = $username;
                    $message = "✅ Informations mises à jour avec succès.";
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username=? WHERE id=?");
                $stmt->execute([$username, $user_id]);
                $_SESSION['username'] = $username;
                $message = "✅ Informations mises à jour avec succès.";
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour du profil : " . $e->getMessage());
            $message = "❌ Erreur lors de la mise à jour.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_agent'])) {
    requireCSRFToken();
    
    $new_username = validateUsername($_POST['new_username'] ?? '');
    $new_password = validatePassword($_POST['new_password'] ?? '');
    $role = in_array($_POST['role'] ?? 'agent', ['admin', 'agent']) ? $_POST['role'] : 'agent';

    if (!$new_username) {
        $message = "❌ Nom d'utilisateur invalide (3-50 caractères alphanumériques).";
    } elseif (!$new_password) {
        $message = "❌ " . getPasswordRequirements();
    } else {
        try {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$new_username, $hash, $role]);
            $message = "✅ Nouvel utilisateur ajouté avec succès.";
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
            if ($e->getCode() == 23000) {
                $message = "❌ Ce nom d'utilisateur existe déjà.";
            } else {
                $message = "❌ Erreur lors de la création de l'utilisateur.";
            }
        }
    }
}

$users = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paramètres - Pharmacy Stock</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex">

<?php require_once "includes/sidebar.php"; ?>

<main class="flex-1 lg:ml-64 p-4 lg:p-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">⚙️ Paramètres</h1>
            <p class="text-gray-600">Gérez votre compte et les utilisateurs</p>
        </div>
        <a href="dashboard.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
            ← Retour
        </a>
    </div>

    <?php if(!empty($message)): ?>
        <div class="mb-6 p-4 <?= strpos($message, '✅') !== false ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' ?> border rounded-lg">
            <p class="text-sm <?= strpos($message, '✅') !== false ? 'text-green-700' : 'text-red-700' ?>"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">👤 Modifier mes informations</h2>
                <p class="text-sm text-gray-600 mt-1">Mettez à jour votre profil personnel</p>
            </div>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nom d'utilisateur</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nouveau mot de passe</label>
                    <input type="password" name="password" placeholder="Laisser vide pour ne pas changer" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <p class="text-sm text-gray-500 mt-1"><?= getPasswordRequirements() ?></p>
                </div>
                <button type="submit" name="update_profile" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                    💾 Mettre à jour
                </button>
            </form>
        </div>

        <?php if($_SESSION['role'] === 'admin'): ?>
        <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900">➕ Ajouter un utilisateur</h2>
                <p class="text-sm text-gray-600 mt-1">Créez un nouveau compte utilisateur</p>
            </div>
            <form method="POST" class="space-y-4">
                <?= csrfField() ?>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nom d'utilisateur</label>
                    <input type="text" name="new_username" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                           placeholder="Ex: nouvel_agent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe</label>
                    <input type="password" name="new_password" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" 
                           placeholder="8+ caractères, majuscule, minuscule, chiffre">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rôle</label>
                    <select name="role" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="agent">Agent</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>
                <button type="submit" name="create_agent" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    ➕ Créer
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <?php if($_SESSION['role'] === 'admin'): ?>
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6 mb-8">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">💾 Sauvegarde de la base de données</h2>
            <p class="text-sm text-gray-600 mt-1">Créez une sauvegarde manuelle de votre base de données</p>
        </div>
        <form action="scripts/backup_manual.php" method="POST" onsubmit="return confirm('⚠️ Voulez-vous créer une sauvegarde maintenant ?');">
            <?= csrfField() ?>
            <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                💾 Créer une sauvegarde maintenant
            </button>
        </form>
        <p class="text-xs text-gray-500 mt-3 text-center">
            💡 Les sauvegardes automatiques sont configurées via cron job
        </p>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-200 border border-gray-200 p-6">
        <div class="mb-6 pb-4 border-b border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">👥 Liste des utilisateurs</h2>
            <p class="text-sm text-gray-600 mt-1">Total : <?= count($users) ?> utilisateur(s)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nom d'utilisateur</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Rôle</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Créé le</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($users as $u): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-900"><?= htmlspecialchars($u['username']) ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold <?= $u['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' ?>">
                                <?= $u['role'] === 'admin' ? '👑 Admin' : '👤 Agent' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-gray-700"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</main>

</body>
</html>
