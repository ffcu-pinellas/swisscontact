<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /en", true, 301);
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    die("Error: All fields are required.");
}

try {
    // 1. Ensure inquiries table exists
    $schema = "CREATE TABLE IF NOT EXISTS inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($schema);

    // 2. Insert inquiry into database
    $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, message) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $message]);

    // 3. Try sending email to donations@swisscontact.online
    $to = "donations@swisscontact.online";
    $subject = "New Donation / Support Inquiry";
    $headers = "From: webmaster@swisscontact.online\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    $body = "You have received a new inquiry from the swisscontact.online website footer form:\n\n";
    $body .= "Name: " . $name . "\n";
    $body .= "Email: " . $email . "\n\n";
    $body .= "Message / Inquiry details:\n" . $message . "\n";
    
    @mail($to, $subject, $body, $headers);

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
