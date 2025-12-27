<?php
/**
 * Script de sauvegarde manuelle de la base de données
 * Accessible via l'interface web (admin uniquement)
 */

require_once __DIR__ . '/../includes/auth.php';

if (!isAdmin()) {
    http_response_code(403);
    die("Accès refusé : Seuls les administrateurs peuvent effectuer des sauvegardes.");
}

// Charger les paramètres de connexion depuis .env (sans inclure database.php pour éviter le conflit)
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return [
                'DB_HOST' => 'localhost',
                'DB_NAME' => 'pharmacy_stock',
                'DB_USER' => 'root',
                'DB_PASS' => ''
            ];
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }
        return $env;
    }
}

$env = loadEnv(__DIR__ . '/../.env');
$dbHost = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? 'pharmacy_stock';
$dbUser = $env['DB_USER'] ?? 'root';
$dbPass = $env['DB_PASS'] ?? '';

// Configuration
$backupDir = __DIR__ . '/../backups';
$dateFormat = 'Y-m-d_H-i-s';

// Créer le dossier de sauvegarde s'il n'existe pas
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Nom du fichier de sauvegarde
$backupFile = $backupDir . '/backup_' . $dbName . '_' . date($dateFormat) . '.sql';

// Fonction pour trouver mysqldump sur Windows
function findMysqldump() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $wampPaths = [
            'C:/wamp64/bin/mysql',
            'C:/wamp/bin/mysql'
        ];
        
        foreach ($wampPaths as $basePath) {
            if (is_dir($basePath)) {
                $mysqlDirs = glob($basePath . '/mysql*', GLOB_ONLYDIR);
                foreach ($mysqlDirs as $mysqlDir) {
                    $mysqldumpPath = $mysqlDir . '/bin/mysqldump.exe';
                    if (file_exists($mysqldumpPath)) {
                        return $mysqldumpPath;
                    }
                }
            }
        }
        
        // Essayer dans le PATH
        $paths = explode(';', getenv('PATH'));
        foreach ($paths as $path) {
            $mysqldumpPath = $path . '/mysqldump.exe';
            if (file_exists($mysqldumpPath)) {
                return $mysqldumpPath;
            }
        }
    }
    
    return 'mysqldump';
}

$mysqldumpPath = findMysqldump();

// Méthode 1 : Essayer avec mysqldump
$command = '';
if (empty($dbPass)) {
    $command = sprintf(
        '"%s" -h %s -u %s %s > %s 2>&1',
        $mysqldumpPath,
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        escapeshellarg($dbName),
        escapeshellarg($backupFile)
    );
} else {
    $command = sprintf(
        '"%s" -h %s -u %s -p%s %s > %s 2>&1',
        $mysqldumpPath,
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        escapeshellarg($dbPass),
        escapeshellarg($dbName),
        escapeshellarg($backupFile)
    );
}

// Exécuter la sauvegarde
exec($command, $output, $returnVar);

// Si mysqldump échoue, utiliser la méthode PDO
if ($returnVar !== 0 || !file_exists($backupFile) || filesize($backupFile) == 0) {
    try {
        // Connexion PDO pour sauvegarde alternative
        $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "-- Sauvegarde de la base de données {$dbName}\n";
        $sql .= "-- Date : " . date('Y-m-d H:i:s') . "\n\n";
        
        // Récupérer toutes les tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $sql .= "\n-- Structure de la table `{$table}`\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $sql .= $createTable['Create Table'] . ";\n\n";
            
            // Données
            $sql .= "-- Données de la table `{$table}`\n";
            $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $sql .= "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $rowValues[] = 'NULL';
                        } else {
                            $rowValues[] = $pdo->quote($value);
                        }
                    }
                    $values[] = "(" . implode(', ', $rowValues) . ")";
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        file_put_contents($backupFile, $sql);
        $returnVar = 0;
    } catch (PDOException $e) {
        $logFile = __DIR__ . '/../logs/backup.log';
        $logMessage = date('Y-m-d H:i:s') . " - ERREUR PDO de sauvegarde : " . $e->getMessage() . "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        header("Location: ../parametres.php?error=Erreur lors de la création de la sauvegarde : " . urlencode($e->getMessage()));
        exit;
    }
}

if ($returnVar === 0 && file_exists($backupFile) && filesize($backupFile) > 0) {
    // Compresser la sauvegarde
    $compressedFile = $backupFile . '.gz';
    $fp_in = fopen($backupFile, 'rb');
    $fp_out = gzopen($compressedFile, 'wb9');
    
    if ($fp_in && $fp_out) {
        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 8192));
        }
        fclose($fp_in);
        gzclose($fp_out);
        unlink($backupFile);
    }
    
    // Logger le succès
    $logFile = __DIR__ . '/../logs/backup.log';
    $logMessage = date('Y-m-d H:i:s') . " - Sauvegarde manuelle réussie par " . $_SESSION['username'] . " : " . basename($compressedFile) . " (" . number_format(filesize($compressedFile) / 1024, 2) . " KB)\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Rediriger vers la page de téléchargement
    header("Location: ../parametres.php?success=Sauvegarde créée avec succès : " . basename($compressedFile));
    exit;
} else {
    // Logger l'erreur
    $logFile = __DIR__ . '/../logs/backup.log';
    $errorMsg = !empty($output) ? implode("\n", $output) : "Erreur inconnue";
    $logMessage = date('Y-m-d H:i:s') . " - ERREUR de sauvegarde manuelle : " . $errorMsg . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    header("Location: ../parametres.php?error=Erreur lors de la création de la sauvegarde. Vérifiez les logs.");
    exit;
}
