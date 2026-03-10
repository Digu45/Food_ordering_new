<?php
require_once __DIR__ . '/config.php';

$port = defined('DB_PORT') ? DB_PORT : '3306';
$type = defined('DB_TYPE') ? DB_TYPE : 'mysql';

// Build DSN based on database type
if($type == 'pgsql'){
    $dsn = 'pgsql:host='.DB_HOST.';port='.$port.';dbname='.DB_NAME;
} else {
    $dsn = 'mysql:host='.DB_HOST.';port='.$port.';dbname='.DB_NAME.';charset=utf8mb4';
}

try {
    $pdo = new PDO(
        $dsn,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    die(json_encode(['error' => 'Database connection failed']));
}
?>