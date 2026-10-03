<?php
/**
 * Copy this file to db.php and enter your database credentials.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdoOptions);
} catch (PDOException $e) {
    error_log('DB CONNECTION FAILED: ' . $e->getMessage());
    http_response_code(500);
    if (defined('APP_DEBUG') && APP_DEBUG === true) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    die('Something went wrong. Please try again later.');
}