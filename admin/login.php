<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../notification_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Browser';

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            send_telegram_notification("<b>✅ Admin Login Successful</b>\nUsername: $username\nIP: $ip\nBrowser: $agent");
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
            send_telegram_notification("<b>⚠️ Failed Admin Login Attempt</b>\nUsername tried: $username\nIP: $ip");
        }
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Swisscontact Admin - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .btn-primary { background-color: #F47B20; border-color: #F47B20; }
        .btn-primary:hover { background-color: #d36413; border-color: #d36413; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="text-center mb-4">
            <h2 style="color: #F47B20; font-weight: bold;">Admin Login</h2>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>
</html>
