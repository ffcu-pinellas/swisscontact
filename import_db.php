<?php
/**
 * Database Migration Script
 * Automatically imports swisscontact_db.sql into Hostinger MySQL
 */
header('Content-Type: text/plain; charset=utf-8');

// Disable execution time limit
set_time_limit(0);

// Check if config.php exists
if (!file_exists(__DIR__ . '/config.php')) {
    die("Error: config.php not found!");
}

require_once __DIR__ . '/config.php';

$sqlFile = __DIR__ . '/swisscontact_db.sql';
if (!file_exists($sqlFile)) {
    die("Error: swisscontact_db.sql not found!");
}

echo "Starting database import...\n";
echo "Host: " . DB_HOST . "\n";
echo "Database: " . DB_NAME . "\n\n";

try {
    // Read SQL file content
    echo "Reading swisscontact_db.sql file...\n";
    $sqlContent = file_get_contents($sqlFile);
    
    // Execute all queries in one go (supports multi-queries by default in PDO MySQL)
    echo "Executing database dump...\n";
    $pdo->exec($sqlContent);
    
    echo "\nDatabase import complete!\n";
    echo "Successfully updated all page records.\n";
    
} catch (Exception $e) {
    die("\nCritical Error: " . $e->getMessage() . "\n");
}
