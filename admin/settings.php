<?php
require_once __DIR__ . '/auth.php';
require_login();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    $msg = "Settings updated successfully.";
}

// Fetch settings
$stmt = $pdo->query("SELECT * FROM settings");
$settings_raw = $stmt->fetchAll();
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd8dc; text-decoration: none; display: block; padding: 10px 15px; }
        .sidebar a:hover { background-color: #495057; color: white; }
        .sidebar a.active { background-color: #F47B20; color: white; font-weight: bold; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3" style="width: 250px;">
            <h4 class="mb-4">Admin Portal</h4>
            <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="pages.php"><i class="bi bi-file-earmark-text me-2"></i> Manage Pages</a>
            <a href="inquiries.php"><i class="bi bi-envelope me-2"></i> Inquiries</a>
            <a href="settings.php" class="active"><i class="bi bi-gear me-2"></i> Contact Settings</a>
            <a href="logout.php" class="mt-5 text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>
        
        <!-- Content -->
        <div class="content flex-grow-1">
            <h2>Contact Settings</h2>
            <hr>
            
            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Contact Email</label>
                            <input type="email" name="settings[contact_email]" class="form-control" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="text" name="settings[contact_phone]" class="form-control" value="<?= htmlspecialchars($settings['contact_phone'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Head Office Address (HTML allowed)</label>
                            <textarea name="settings[contact_address]" class="form-control" rows="3"><?= htmlspecialchars($settings['contact_address'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Google Maps Embed URL</label>
                            <input type="text" name="settings[contact_map_url]" class="form-control" value="<?= htmlspecialchars($settings['contact_map_url'] ?? '') ?>">
                            <small class="text-muted">Extract the <code>src="..."</code> URL from a Google Maps embed code.</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="background-color: #F47B20; border-color: #F47B20;">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
