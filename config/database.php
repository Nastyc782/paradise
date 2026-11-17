<?php
/**
 * Digital Smart Class - Database Connection
 * Uses PDO for secure, prepared SQL execution.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if ($secureCookie) {
        ini_set('session.cookie_secure', '1');
    }
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Environment-aware configuration. A server-only local override may provide
// DB_HOST, DB_NAME, DB_USER, DB_PASS, and DB_PORT without entering Git.
$localConfig = __DIR__ . '/database.local.php';
$localSettings = [];
if (is_file($localConfig)) {
    $configuredSettings = require $localConfig;
    if (is_array($configuredSettings)) {
        $localSettings = $configuredSettings;
    }
}

// Prefer the server-only local file directly so shared hosts do not need
// putenv() to be enabled.
$db_host = $localSettings['DB_HOST'] ?? getenv('DB_HOST');
$db_name = $localSettings['DB_NAME'] ?? getenv('DB_NAME');
$db_user = $localSettings['DB_USER'] ?? getenv('DB_USER');
$db_pass = $localSettings['DB_PASS'] ?? getenv('DB_PASS');
$db_port = $localSettings['DB_PORT'] ?? getenv('DB_PORT');

if ($localSettings === []) {
    $db_url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
    $parts = ($db_url && preg_match('#^mysql#i', $db_url)) ? parse_url($db_url) : false;

    if ($parts) {
        $db_host = $parts['host'] ?? null;
        $db_port = isset($parts['port']) ? (string) $parts['port'] : $db_port;
        $db_user = isset($parts['user']) ? urldecode($parts['user']) : $db_user;
        $db_pass = isset($parts['pass']) ? urldecode($parts['pass']) : $db_pass;
        if (!empty($parts['path'])) {
            $db_name = ltrim($parts['path'], '/');
        }
    } else {
        $db_host = getenv('MYSQLHOST') ?: $db_host;
        $db_port = getenv('MYSQLPORT') ?: $db_port;
        $db_user = getenv('MYSQLUSER') ?: $db_user;
        $db_pass = getenv('MYSQLPASSWORD') ?: $db_pass;
        $db_name = getenv('MYSQLDATABASE') ?: $db_name;
    }
}

// Local development defaults (XAMPP).
$db_host = ($db_host === false || $db_host === '') ? 'localhost' : $db_host;
$db_name = ($db_name === false || $db_name === '') ? 'digital_smart_class' : $db_name;
$db_user = ($db_user === false || $db_user === '') ? 'root' : $db_user;
if ($db_pass === false) {
    $db_pass = ''; // Default XAMPP password is empty
}
$db_port = ($db_port === false || $db_port === '') ? '3306' : $db_port;

$pdo = null;
$db_connected = false;
$db_error = null;

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    $db_connected = true;
} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}

/**
 * Helper to get DB connection safely
 */
function getDB() {
    global $pdo;
    return $pdo;
}

/**
 * Base site URL configuration
 */
function site_url($path = '') {
    // Detect folder dynamically, including reverse proxies/load balancers
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine relative directory
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // Strip subdirectories like /student, /admin, /teacher, /accountant
    $baseDir = preg_replace('/(\/(student|teacher|admin|accountant|config|includes|assets))$/', '', $scriptDir);
    $baseDir = rtrim($baseDir, '/');

    return $protocol . $host . $baseDir . '/' . ltrim($path, '/');
}
