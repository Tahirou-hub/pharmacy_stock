<?php
/**
 * Script de gestion des utilisateurs pour Pharmacy Stock
 * Permet de créer, lister et réinitialiser les mots de passe
 */

require_once 'config/database.php';

echo "👥 Gestion des utilisateurs - Pharmacy Stock\n";
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
        
        // Valider le nom d'utilisateur
        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            return ['success' => false, 'message' => "Le nom d'utilisateur doit contenir 3-50 caractères alphanumériques ou underscore."];
        }
        
        // Valider le mot de passe
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => "Le mot de passe doit contenir au moins 6 caractères."];
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

// Fonction pour réinitialiser le mot de passe
function resetPassword($pdo, $username, $newPassword) {
    try {
        // Vérifier si l'utilisateur existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => "L'utilisateur '$username' n'existe pas."];
        }
        
        // Valider le mot de passe
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => "Le mot de passe doit contenir au moins 6 caractères."];
        }
        
        // Créer le hash du nouveau mot de passe
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Mettre à jour le mot de passe
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        $stmt->execute([$passwordHash, $username]);
        
        return ['success' => true, 'message' => "Mot de passe de '$username' réinitialisé avec succès."];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => "Erreur : " . $e->getMessage()];
    }
}

// Afficher le menu
function showMenu() {
    echo "\n📋 MENU\n";
    echo "-------\n";
    echo "1. Créer un nouvel utilisateur\n";
    echo "2. Réinitialiser le mot de passe d'un utilisateur\n";
    echo "3. Lister tous les utilisateurs\n";
    echo "4. Créer admin et agent par défaut\n";
    echo "0. Quitter\n";
    echo "\nVotre choix : ";
}

// Afficher la liste des utilisateurs
function listUsers($pdo) {
    echo "\n📋 Liste des utilisateurs\n";
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
        echo "❌ Erreur : " . $e->getMessage() . "\n";
    }
}

// Menu principal
$continue = true;
while ($continue) {
    showMenu();
    $choice = trim(readline());
    
    switch ($choice) {
        case '1':
            echo "\n➕ Création d'un nouvel utilisateur\n";
            echo "-----------------------------------\n";
            echo "Nom d'utilisateur : ";
            $username = trim(readline());
            echo "Mot de passe : ";
            $password = trim(readline());
            echo "Rôle (admin/agent) : ";
            $role = trim(readline());
            
            if (!in_array(strtolower($role), ['admin', 'agent'])) {
                echo "❌ Rôle invalide. Utilisation de 'agent' par défaut.\n";
                $role = 'agent';
            }
            
            $result = createUser($pdo, $username, $password, strtolower($role));
            echo ($result['success'] ? "✅ " : "❌ ") . $result['message'] . "\n";
            break;
            
        case '2':
            echo "\n🔑 Réinitialisation du mot de passe\n";
            echo "-----------------------------------\n";
            echo "Nom d'utilisateur : ";
            $username = trim(readline());
            echo "Nouveau mot de passe : ";
            $password = trim(readline());
            
            $result = resetPassword($pdo, $username, $password);
            echo ($result['success'] ? "✅ " : "❌ ") . $result['message'] . "\n";
            break;
            
        case '3':
            listUsers($pdo);
            break;
            
        case '4':
            echo "\n🔐 Création des utilisateurs par défaut\n";
            echo "--------------------------------------\n";
            
            // Créer admin
            echo "Création de l'admin...\n";
            $result = createUser($pdo, 'admin', 'admin123', 'admin');
            echo ($result['success'] ? "✅ " : "⚠️  ") . $result['message'] . "\n";
            
            // Créer agent
            echo "Création de l'agent...\n";
            $result = createUser($pdo, 'agent', 'agent123', 'agent');
            echo ($result['success'] ? "✅ " : "⚠️  ") . $result['message'] . "\n";
            
            echo "\n📝 Identifiants par défaut :\n";
            echo "   Admin : admin / admin123\n";
            echo "   Agent : agent / agent123\n";
            echo "\n⚠️  IMPORTANT : Changez ces mots de passe après la première connexion !\n";
            break;
            
        case '0':
            $continue = false;
            echo "\n👋 Au revoir !\n";
            break;
            
        default:
            echo "❌ Choix invalide. Veuillez réessayer.\n";
            break;
    }
}

// Afficher la liste finale
echo "\n";
listUsers($pdo);


