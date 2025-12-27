<?php
// includes/errors.php
// Système de gestion d'erreurs centralisé

/**
 * Affiche un message d'erreur formaté
 */
function displayError($message) {
    if (!empty($message)) {
        echo '<div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">';
        echo '<p class="font-semibold">❌ ' . htmlspecialchars($message) . '</p>';
        echo '</div>';
    }
}

/**
 * Affiche un message de succès formaté
 */
function displaySuccess($message) {
    if (!empty($message)) {
        echo '<div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">';
        echo '<p class="font-semibold">✅ ' . htmlspecialchars($message) . '</p>';
        echo '</div>';
    }
}

/**
 * Affiche un message d'information formaté
 */
function displayInfo($message) {
    if (!empty($message)) {
        echo '<div class="mb-4 p-4 bg-blue-100 border-l-4 border-blue-500 text-blue-700 rounded">';
        echo '<p class="font-semibold">ℹ️ ' . htmlspecialchars($message) . '</p>';
        echo '</div>';
    }
}

/**
 * Affiche un message d'avertissement formaté
 */
function displayWarning($message) {
    if (!empty($message)) {
        echo '<div class="mb-4 p-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 rounded">';
        echo '<p class="font-semibold">⚠️ ' . htmlspecialchars($message) . '</p>';
        echo '</div>';
    }
}

/**
 * Récupère et affiche les messages depuis les paramètres GET
 */
function displayMessages() {
    if (isset($_GET['error'])) {
        displayError($_GET['error']);
    }
    if (isset($_GET['success'])) {
        displaySuccess($_GET['success']);
    }
    if (isset($_GET['info'])) {
        displayInfo($_GET['info']);
    }
    if (isset($_GET['warning'])) {
        displayWarning($_GET['warning']);
    }
}

/**
 * Log une erreur dans le fichier de log
 */
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);
    
    // Créer le dossier logs s'il n'existe pas
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[{$timestamp}] {$message}{$contextStr}\n";
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}


