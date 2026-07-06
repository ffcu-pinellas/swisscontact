<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/libs/Exception.php';
require_once __DIR__ . '/libs/PHPMailer.php';
require_once __DIR__ . '/libs/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_telegram_notification($message) {
    global $pdo;
    
    // Fetch settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('telegram_bot_token', 'telegram_chat_id')");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $token = $settings['telegram_bot_token'] ?? '';
    $chat_id = $settings['telegram_chat_id'] ?? '';
    
    if (empty($token) || empty($chat_id)) {
        return false; // Not configured
    }
    
    $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result !== false;
}

function send_smtp_email($to, $subject, $body, $reply_to = null) {
    global $pdo;
    
    // Fetch SMTP settings
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%'");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $host = $settings['smtp_host'] ?? 'smtp.hostinger.com';
    $port = $settings['smtp_port'] ?? 465;
    $user = $settings['smtp_user'] ?? '';
    $pass = $settings['smtp_pass'] ?? '';
    
    if (empty($user) || empty($pass)) {
        // Fallback to regular mail if SMTP not configured
        $headers = "From: webmaster@swisscontact.online\r\n";
        if ($reply_to) {
            $headers .= "Reply-To: " . $reply_to . "\r\n";
        }
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return @mail($to, $subject, $body, $headers);
    }
    
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        if ($port == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port       = $port;

        // Recipients
        $mail->setFrom($user, 'Swisscontact Website');
        $mail->addAddress($to);
        if ($reply_to) {
            $mail->addReplyTo($reply_to);
        }

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log or handle error if needed
        return false;
    }
}
