<?php
require_once __DIR__ . '/auth.php';
require_login();

// Count metrics
$inquiries_count = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$pages_count = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
            <a href="index.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="pages.php"><i class="bi bi-file-earmark-text me-2"></i> Manage Pages</a>
            <a href="inquiries.php"><i class="bi bi-envelope me-2"></i> Inquiries</a>
            <a href="settings.php"><i class="bi bi-gear me-2"></i> Contact Settings</a>
            <a href="logout.php" class="mt-5 text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>
        
        <!-- Content -->
        <div class="content flex-grow-1">
            <h2>Dashboard</h2>
            <hr>
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Pages</h5>
                            <p class="card-text fs-2"><?= $pages_count ?></p>
                            <a href="pages.php" class="text-white">View Pages &raquo;</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Inquiries</h5>
                            <p class="card-text fs-2"><?= $inquiries_count ?></p>
                            <a href="inquiries.php" class="text-white">View Inquiries &raquo;</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
