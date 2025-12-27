<?php
// includes/rate_limit.php
// Système de rate limiting simple basé sur les sessions

/**
 * Vérifie si une action est autorisée selon le rate limiting
 * @param string $action Nom de l'action (ex: 'login', 'api_call')
 * @param int $maxAttempts Nombre maximum de tentatives
 * @param int $timeWindow Fenêtre de temps en secondes
 * @return bool True si autorisé, False si limité
 */
function checkRateLimit($action, $maxAttempts = 5, $timeWindow = 300) {
    session_start();
    
    $key = 'rate_limit_' . $action;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'reset_time' => $now + $timeWindow
        ];
    }
    
    $data = $_SESSION[$key];
    
    // Réinitialiser si la fenêtre de temps est expirée
    if ($now > $data['reset_time']) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'reset_time' => $now + $timeWindow
        ];
        return true;
    }
    
    // Vérifier si le nombre de tentatives est dépassé
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }
    
    // Incrémenter le compteur
    $_SESSION[$key]['attempts']++;
    return true;
}

/**
 * Réinitialise le rate limit pour une action
 */
function resetRateLimit($action) {
    session_start();
    $key = 'rate_limit_' . $action;
    unset($_SESSION[$key]);
}

/**
 * Retourne le temps restant avant réinitialisation
 */
function getRateLimitRemainingTime($action) {
    session_start();
    $key = 'rate_limit_' . $action;
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $data = $_SESSION[$key];
    $remaining = $data['reset_time'] - time();
    return $remaining > 0 ? $remaining : 0;
}


