<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT
    )");

    $settings = [
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',
        'smtp_host' => 'smtp.hostinger.com',
        'smtp_port' => '465',
        'smtp_user' => '',
        'smtp_pass' => ''
    ];
    
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $stmt_insert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $key => $val) {
        try {
            $stmt_check->execute([$key]);
            if ($stmt_check->fetchColumn() == 0) {
                $stmt_insert->execute([$key, $val]);
            }
        } catch (Exception $e) {
            // Might exist or fail, ignore
        }
    }
    echo "Schema updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
