<?php
// includes/validation.php
// Fonctions de validation réutilisables

/**
 * Nettoie et valide une chaîne de caractères
 */
function sanitizeString($input, $maxLength = 255) {
    $cleaned = trim($input);
    $cleaned = htmlspecialchars($cleaned, ENT_QUOTES, 'UTF-8');
    if (strlen($cleaned) > $maxLength) {
        $cleaned = substr($cleaned, 0, $maxLength);
    }
    return $cleaned;
}

/**
 * Valide un entier positif
 */
function validatePositiveInt($value, $min = 1, $max = PHP_INT_MAX) {
    $int = filter_var($value, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max]
    ]);
    return $int !== false ? $int : null;
}

/**
 * Valide un nombre décimal positif
 */
function validatePositiveFloat($value, $min = 0) {
    $float = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($float !== false && $float >= $min) {
        return round($float, 2);
    }
    return null;
}

/**
 * Valide un nom d'utilisateur
 */
function validateUsername($username) {
    // Entre 3 et 50 caractères, alphanumériques et underscore
    if (preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        return sanitizeString($username, 50);
    }
    return null;
}

/**
 * Valide un mot de passe avec exigences de sécurité
 * Minimum 8 caractères, au moins une majuscule, une minuscule, un chiffre
 */
function validatePassword($password) {
    // Minimum 8 caractères
    if (strlen($password) < 8) {
        return null;
    }
    
    // Au moins une majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        return null;
    }
    
    // Au moins une minuscule
    if (!preg_match('/[a-z]/', $password)) {
        return null;
    }
    
    // Au moins un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        return null;
    }
    
    return $password;
}

/**
 * Retourne les exigences du mot de passe pour affichage
 */
function getPasswordRequirements() {
    return "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.";
}

/**
 * Valide une date
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date ? $date : null;
}

/**
 * Valide un ID (entier positif)
 */
function validateId($id) {
    return validatePositiveInt($id, 1);
}

