<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /en", true, 301);
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$name = trim($_POST['name'] ?? trim($first_name . ' ' . $last_name));
$company = trim($_POST['company'] ?? '');
$country = trim($_POST['country'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    die("Error: Name, email, and message are required.");
}

try {
    // 1. Ensure inquiries table exists
    $schema = "CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        first_name VARCHAR(255) NOT NULL,
        last_name VARCHAR(255) NOT NULL,
        company VARCHAR(255),
        email VARCHAR(255) NOT NULL,
        country VARCHAR(100),
        phone VARCHAR(50),
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($schema);

    // 2. Save inquiry to database
    $stmt = $pdo->prepare("INSERT INTO inquiries (name, first_name, last_name, company, email, country, phone, message) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $first_name, $last_name, $company, $email, $country, $phone, $message]);

require_once __DIR__ . '/notification_helper.php';

    // 3. Send email to donations@swisscontact.online
    $stmt_email = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'contact_email'");
    $admin_email = $stmt_email->fetchColumn() ?: "donations@swisscontact.online";
    
    $subject = "New Donation / Support Inquiry";
    
    $body = "You have received a new inquiry from the swisscontact.online website footer form:\n\n";
    $body .= "Name: " . $name . "\n";
    $body .= "Company: " . $company . "\n";
    $body .= "Email: " . $email . "\n";
    $body .= "Phone: " . $phone . "\n";
    $body .= "Country: " . $country . "\n\n";
    $body .= "Message / Inquiry details:\n" . $message . "\n";
    
    send_smtp_email($admin_email, $subject, $body, $email);
    
    // 4. Send Telegram Notification
    $telegram_msg = "<b>🚨 New Inquiry Received!</b>\n\n";
    $telegram_msg .= "<b>Name:</b> " . htmlspecialchars($name) . "\n";
    $telegram_msg .= "<b>Email:</b> " . htmlspecialchars($email) . "\n";
    $telegram_msg .= "<b>Company:</b> " . htmlspecialchars($company) . "\n";
    $telegram_msg .= "<b>Phone:</b> " . htmlspecialchars($phone) . "\n";
    $telegram_msg .= "<b>Country:</b> " . htmlspecialchars($country) . "\n\n";
    $telegram_msg .= "<b>Message:</b>\n" . htmlspecialchars($message);
    
    send_telegram_notification($telegram_msg);

} catch (Exception $e) {
    // Fallback if DB insert fails
}

// Render a beautiful success page using Swisscontact styles
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Thank You - Inquiry Sent</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/_Resources/Static/Packages/Internezzo.Neos/Css/Styles.css?bust=16667492" media="all" />
    <style>
        body { background-color: #f7f7f7; font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .success-box { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%; border-top: 4px solid #F47B20; }
        h1 { color: #F47B20; font-size: 24px; margin-bottom: 15px; }
        p { color: #666; font-size: 16px; line-height: 1.5; margin-bottom: 25px; }
        .btn-home { display: inline-block; background: #F47B20; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 4px; font-weight: bold; transition: background 0.2s; }
        .btn-home:hover { background: #d36413; }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>Thank You!</h1>
        <p>Your donation inquiry has been successfully sent. A member of our team will contact you shortly.</p>
        <a href="/en" class="btn-home">Return to Homepage</a>
    </div>
</body>
</html>
