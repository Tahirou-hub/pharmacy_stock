<?php
/**
 * Script simple pour créer les utilisateurs admin et agent par défaut
 * Exécutez ce script directement : php create_default_users.php
 */

require_once 'config/database.php';

echo "👥 Création des utilisateurs par défaut\n";
echo "========================================\n\n";

// Fonction pour créer ou mettre à jour un utilisateur
function createOrUpdateUser($pdo, $username, $password, $role) {
    try {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $existing = $stmt->fetch();
        
        // Créer le hash du mot de passe
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        if ($existing) {
            // Mettre à jour le mot de passe
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = ? WHERE username = ?");
            $stmt->execute([$passwordHash, $role, $username]);
            return ['success' => true, 'action' => 'mis à jour', 'message' => "Utilisateur '$username' mis à jour avec succès (rôle: $role)."];
        } else {
            // Créer l'utilisateur
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $passwordHash, $role]);
            return ['success' => true, 'action' => 'créé', 'message' => "Utilisateur '$username' créé avec succès (rôle: $role)."];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
    }
}

// Créer l'admin
echo "🔐 Création/Mise à jour de l'utilisateur ADMIN...\n";
$result = createOrUpdateUser($pdo, 'admin', 'admin123', 'admin');
if ($result['success']) {
    echo "✅ " . $result['message'] . "\n";
} else {
    echo "❌ " . $result['message'] . "\n";
}

echo "\n";

// Créer l'agent
echo "👤 Création/Mise à jour de l'utilisateur AGENT...\n";
$result = createOrUpdateUser($pdo, 'agent', 'agent123', 'agent');
if ($result['success']) {
    echo "✅ " . $result['message'] . "\n";
} else {
    echo "❌ " . $result['message'] . "\n";
}

echo "\n";

// Afficher la liste des utilisateurs
echo "📋 Liste des utilisateurs existants\n";
echo str_repeat("=", 70) . "\n";
try {
    $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "Aucun utilisateur trouvé.\n";
    } else {
        printf("%-5s %-25s %-12s %-25s\n", "ID", "Nom d'utilisateur", "Rôle", "Créé le");
        echo str_repeat("-", 70) . "\n";
        foreach ($users as $user) {
            printf("%-5s %-25s %-12s %-25s\n", 
                $user['id'], 
                $user['username'], 
                strtoupper($user['role']),
                $user['created_at']
            );
        }
        echo str_repeat("=", 70) . "\n";
        echo "Total : " . count($users) . " utilisateur(s)\n";
    }
} catch (PDOException $e) {
    echo "❌ Erreur lors de la récupération des utilisateurs : " . $e->getMessage() . "\n";
}

echo "\n✅ Terminé !\n";
echo "\n📝 Identifiants par défaut :\n";
echo "   👑 Admin : admin / admin123\n";
echo "   👤 Agent : agent / agent123\n";
echo "\n⚠️  IMPORTANT : Changez ces mots de passe après la première connexion !\n";
echo "\n🌐 Vous pouvez maintenant vous connecter à :\n";
echo "   http://localhost/pharmacy-stock/login.php\n";


