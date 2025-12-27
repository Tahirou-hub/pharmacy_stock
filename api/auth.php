<?php
session_start();
require_once "../config/database.php";
require_once "../includes/rate_limit.php";
require_once "../includes/validation.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le rate limiting (5 tentatives par 5 minutes)
    if (!checkRateLimit('login', 5, 300)) {
        $remaining = getRateLimitRemainingTime('login');
        $minutes = ceil($remaining / 60);
        header("Location: ../login.php?error=Trop de tentatives. Veuillez réessayer dans {$minutes} minute(s).");
        exit;
    }
    
    // Valider et nettoyer les entrées
    $username = sanitizeString($_POST['username'] ?? '', 50);
    $password = $_POST['password'] ?? '';
    
    // Validation basique
    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=Nom d'utilisateur et mot de passe requis");
        exit;
    }
    
    if (strlen($username) < 3 || strlen($username) > 50) {
        header("Location: ../login.php?error=Nom d'utilisateur invalide");
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Auth réussie - réinitialiser le rate limit
            resetRateLimit('login');
            
            // Régénérer l'ID de session pour prévenir la fixation de session
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            header("Location: ../dashboard.php");
            exit;
        } else {
            // Échec d'authentification
            header("Location: ../login.php?error=Nom d'utilisateur ou mot de passe incorrect");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de l'authentification : " . $e->getMessage());
        header("Location: ../login.php?error=Erreur de connexion. Veuillez réessayer.");
        exit;
    }
} else {
    header("Location: ../login.php");
    exit;
}
