<?php
session_start();

// Configuration du timeout de session (30 minutes d'inactivité)
define('SESSION_TIMEOUT', 1800); // 30 minutes en secondes

// Vérifier le timeout de session
if (isset($_SESSION['last_activity'])) {
    $inactive = time() - $_SESSION['last_activity'];
    if ($inactive > SESSION_TIMEOUT) {
        // Session expirée
        session_unset();
        session_destroy();
        header("Location: login.php?error=Votre session a expiré. Veuillez vous reconnecter.");
        exit;
    }
}

// Mettre à jour l'activité de session
$_SESSION['last_activity'] = time();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isAgent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'agent';
}
