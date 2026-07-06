<?php
require_once __DIR__ . '/auth.php';
require_login();

$msg = '';
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    $id = intval($_POST['id']);
    $content_html = $_POST['content_html'];
    $title = $_POST['title'];
    
    $stmt = $pdo->prepare("UPDATE pages SET title = ?, content_html = ? WHERE id = ?");
    $stmt->execute([$title, $content_html, $id]);
    $msg = "Page updated successfully.";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Pages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- TinyMCE for HTML editing -->
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .sidebar { min-height: 100vh; background-color: #343a40; color: white; }
        .sidebar a { color: #cfd8dc; text-decoration: none; display: block; padding: 10px 15px; }
        .sidebar a:hover { background-color: #495057; color: white; }
        .sidebar a.active { background-color: #F47B20; color: white; font-weight: bold; }
        .content { padding: 20px; }
    </style>
    <script>
      tinymce.init({
        selector: '#content_editor',
        plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount code',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | code',
        height: 600,
        extended_valid_elements: 'script[src|async|defer|type|charset],style,div[*],span[*],a[*],img[*],iframe[*]',
        verify_html: false,
        valid_children: '+body[style|script],+div[style|script]'
      });
    </script>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar p-3" style="width: 250px;">
            <h4 class="mb-4">Admin Portal</h4>
            <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="pages.php" class="active"><i class="bi bi-file-earmark-text me-2"></i> Manage Pages</a>
            <a href="inquiries.php"><i class="bi bi-envelope me-2"></i> Inquiries</a>
            <a href="settings.php"><i class="bi bi-gear me-2"></i> Contact Settings</a>
            <a href="logout.php" class="mt-5 text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
        </div>
        
        <!-- Content -->
        <div class="content flex-grow-1">
            <h2>Manage Pages</h2>
            <hr>
            
            <?php if ($msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if ($action === 'list'): ?>
                <?php
                $stmt = $pdo->query("SELECT id, url_path, title, lang FROM pages ORDER BY url_path ASC");
                $pages = $stmt->fetchAll();
                ?>
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>URL Path</th>
                                    <th>Language</th>
                                    <th>Title</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pages as $row): ?>
                                    <tr>
                                        <td><a href="<?= htmlspecialchars($row['url_path']) ?>" target="_blank"><?= htmlspecialchars($row['url_path']) ?></a></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['lang']) ?></span></td>
                                        <td><?= htmlspecialchars($row['title']) ?></td>
                                        <td>
                                            <a href="?action=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" style="background-color: #F47B20; border-color: #F47B20;">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
            <?php elseif ($action === 'edit' && isset($_GET['id'])): ?>
                <?php
                $id = intval($_GET['id']);
                $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
                $stmt->execute([$id]);
                $page = $stmt->fetch();
                if (!$page) {
                    echo "<div class='alert alert-danger'>Page not found.</div>";
                    exit;
                }
                ?>
                <div class="mb-3">
                    <a href="pages.php" class="btn btn-secondary">&laquo; Back to Pages</a>
                    <a href="<?= htmlspecialchars($page['url_path']) ?>" target="_blank" class="btn btn-outline-primary float-end">View Live Page</a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="?action=edit&id=<?= $id ?>">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <div class="mb-3">
                                <label class="form-label">URL Path</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($page['url_path']) ?>" readonly disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Page Title</label>
                                <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($page['title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Page Content (HTML)</label>
                                <textarea name="content_html" id="content_editor" class="form-control"><?= htmlspecialchars($page['content_html']) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg px-5">Save Changes</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
</body>
</html>
