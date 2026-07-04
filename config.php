<?php
// Hostinger MySQL database configuration settings
define('DB_HOST', 'localhost');
define('DB_USER', 'u664663598_swisscontact');
define('DB_PASS', 'Messenger@0090');
define('DB_NAME', 'u664663598_swisscontact');


try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // If DB is not configured yet (e.g. during local testing without MySQL), load SQLite fallback or error gracefully
    if (file_exists(__DIR__ . '/local_fallback.db')) {
        $pdo = new PDO("sqlite:" . __DIR__ . '/local_fallback.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } else {
        die("Database connection failed. Please check config.php credentials.");
    }
}
