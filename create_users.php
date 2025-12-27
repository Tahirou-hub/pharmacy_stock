<?php
/**
 * Script de création d'utilisateurs pour Pharmacy Stock
 * Crée un utilisateur admin et un agent
 */

require_once 'config/database.php';

echo "👥 Création d'utilisateurs - Pharmacy Stock\n";
echo "==========================================\n\n";

// Fonction pour créer un utilisateur
function createUser($pdo, $username, $password, $role) {
    try {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => "L'utilisateur '$username' existe déjà."];
        }
        
        // Créer le hash du mot de passe
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insérer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $passwordHash, $role]);
        
        return ['success' => true, 'message' => "Utilisateur '$username' créé avec succès (rôle: $role)."];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
    }
}

// Mode interactif
echo "Mode interactif\n";
echo "---------------\n\n";

// Créer l'admin
echo "🔐 Création de l'utilisateur ADMIN\n";
echo "Entrez le nom d'utilisateur pour l'admin (ou appuyez sur Entrée pour 'admin') : ";
$adminUsername = trim(readline()) ?: 'admin';

echo "Entrez le mot de passe pour l'admin (ou appuyez sur Entrée pour 'admin123') : ";
$adminPassword = trim(readline()) ?: 'admin123';

if (strlen($adminPassword) < 6) {
    echo "⚠️  Le mot de passe doit contenir au moins 6 caractères. Utilisation de 'admin123'.\n";
    $adminPassword = 'admin123';
}

$result = createUser($pdo, $adminUsername, $adminPassword, 'admin');
if ($result['success']) {
    echo "✅ " . $result['message'] . "\n";
} else {
    echo "❌ " . $result['message'] . "\n";
}

echo "\n";

// Créer l'agent
echo "👤 Création de l'utilisateur AGENT\n";
echo "Entrez le nom d'utilisateur pour l'agent (ou appuyez sur Entrée pour 'agent') : ";
$agentUsername = trim(readline()) ?: 'agent';

echo "Entrez le mot de passe pour l'agent (ou appuyez sur Entrée pour 'agent123') : ";
$agentPassword = trim(readline()) ?: 'agent123';

if (strlen($agentPassword) < 6) {
    echo "⚠️  Le mot de passe doit contenir au moins 6 caractères. Utilisation de 'agent123'.\n";
    $agentPassword = 'agent123';
}

$result = createUser($pdo, $agentUsername, $agentPassword, 'agent');
if ($result['success']) {
    echo "✅ " . $result['message'] . "\n";
} else {
    echo "❌ " . $result['message'] . "\n";
}

echo "\n";

// Afficher la liste des utilisateurs
echo "📋 Liste des utilisateurs existants\n";
echo "-----------------------------------\n";
try {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "Aucun utilisateur trouvé.\n";
    } else {
        printf("%-5s %-20s %-10s %-20s\n", "ID", "Nom d'utilisateur", "Rôle", "Créé le");
        echo str_repeat("-", 60) . "\n";
        foreach ($users as $user) {
            printf("%-5s %-20s %-10s %-20s\n", 
                $user['id'], 
                $user['username'], 
                $user['role'],
                $user['created_at']
            );
        }
    }
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des utilisateurs : " . $e->getMessage() . "\n";
}

echo "\n✅ Terminé !\n";
echo "\n📝 Identifiants créés :\n";
echo "   Admin : $adminUsername / $adminPassword\n";
echo "   Agent : $agentUsername / $agentPassword\n";
echo "\n⚠️  IMPORTANT : Changez ces mots de passe après la première connexion !\n";


