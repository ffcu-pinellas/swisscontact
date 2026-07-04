<?php
require_once __DIR__ . '/config.php';

// Get request URI path
$request_uri = $_SERVER['REQUEST_URI'];
$parsed_path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($parsed_path, '/');

if (empty($path)) {
    // Redirect to default language
    header("Location: /en", true, 301);
    exit;
}

// Fetch page content from database
try {
    // 1. Try matching the exact requested URI (useful for AJAX teasers with query parameters)
    $stmt = $pdo->prepare("SELECT content_html FROM pages WHERE url_path = :path LIMIT 1");
    $stmt->execute(['path' => $request_uri]);
    $page = $stmt->fetch();
    
    // 2. If not found, try matching just the path (for normal pages)
    // CRITICAL: Do NOT fallback to path-matching if the request is an AJAX teaser request (contains ajaxDestination),
    // otherwise it will return the full parent page and duplicate the content on the frontend.
    if (!$page && strpos($request_uri, 'ajaxDestination') === false) {
        $stmt->execute(['path' => $path]);
        $page = $stmt->fetch();
    }
    
    if ($page) {
        // Output page content
        echo $page['content_html'];
        exit;
    }
} catch (Exception $e) {
    // Fallback if table doesn't exist yet
}

// Serve 404 page
http_response_code(404);
try {
    // Try to find a custom 404 page in the database
    $lang = 'en';
    if (strpos($path, '/de') === 0) $lang = 'de';
    elseif (strpos($path, '/fr') === 0) $lang = 'fr';
    elseif (strpos($path, '/es') === 0) $lang = 'es';
    
    $path404 = '/' . $lang . '/404';
    $stmt = $pdo->prepare("SELECT content_html FROM pages WHERE url_path = :path LIMIT 1");
    $stmt->execute(['path' => $path404]);
    $page404 = $stmt->fetch();
    if ($page404) {
        echo $page404['content_html'];
        exit;
    }
} catch (Exception $e) {}

echo "<h1>404 Not Found</h1><p>The requested page could not be found.</p>";
