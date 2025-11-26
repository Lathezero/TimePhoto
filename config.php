<?php
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3305');
define('DB_NAME', getenv('DB_NAME') ?: 'timephoto');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '123456');

define('SITE_URL', getenv('SITE_URL') ?: ((
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http'
) . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')));
define('UPLOAD_PATH', 'uploads/videos/');
define('COVER_PATH', 'uploads/covers/');

function getDB() {
    static $pdo;
    if ($pdo) return $pdo;
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw $e;
    }
}

if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(COVER_PATH)) {
    mkdir(COVER_PATH, 0755, true);
}
?>
