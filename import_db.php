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
    // 1. Create table structure if not exists
    echo "Ensuring pages table structure exists...\n";
    $schema = "CREATE TABLE IF NOT EXISTS pages (
        url_path VARCHAR(255) PRIMARY KEY,
        lang VARCHAR(10),
        title VARCHAR(255),
        meta_description TEXT,
        content_html LONGTEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($schema);
    echo "Table structure ready.\n\n";

    // 2. Read and parse SQL file
    echo "Reading swisscontact_db.sql file...\n";
    $sqlContent = file_get_contents($sqlFile);
    
    // We split statements by ";\n" or ";\r\n"
    $queries = preg_split("/;[ \t]*\r?\n/", $sqlContent);
    $total = count($queries);
    $successful = 0;
    
    echo "Executing " . $total . " queries...\n";
    
    // Begin transaction for speed
    $pdo->beginTransaction();
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) {
            continue;
        }
        
        try {
            $pdo->exec($query);
            $successful++;
        } catch (PDOException $ex) {
            echo "Warning - Query failed: " . substr($query, 0, 100) . "...\n";
            echo "Error details: " . $ex->getMessage() . "\n\n";
        }
    }
    
    $pdo->commit();
    echo "\nDatabase import complete!\n";
    echo "Successfully executed " . $successful . " of " . $total . " queries.\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("\nCritical Error: " . $e->getMessage() . "\n");
}
