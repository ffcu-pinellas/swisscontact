<?php
require_once __DIR__ . '/config.php';

// Get request URI path
$request_uri = $_SERVER['REQUEST_URI'];
$parsed_path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($parsed_path, '/');

// On-the-fly Caching Proxy for missing static assets
if (preg_match('/^(\/_ari|\/_Resources|\/cdn-cgi)\//', $path)) {
    $local_path = __DIR__ . $parsed_path;
    if (!file_exists($local_path)) {
        // Download from parent site
        $remote_url = 'https://www.swisscontact.org' . $parsed_path;
        $ch = curl_init($remote_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $data) {
            $dir = dirname($local_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($local_path, $data);
            
            // Serve the newly downloaded file
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_buffer($finfo, $data);
            finfo_close($finfo);
            
            // Override mime types for specific extensions
            if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'js') $mime_type = 'application/javascript';
            if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'css') $mime_type = 'text/css';
            if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'woff2') $mime_type = 'font/woff2';
            if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'json') $mime_type = 'application/json';
            
            header('Content-Type: ' . $mime_type);
            echo $data;
            exit;
        }
    } else {
        // Serve existing file
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $local_path);
        finfo_close($finfo);
        if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'js') $mime_type = 'application/javascript';
        if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'css') $mime_type = 'text/css';
        if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'woff2') $mime_type = 'font/woff2';
        if (pathinfo($parsed_path, PATHINFO_EXTENSION) == 'json') $mime_type = 'application/json';
        header('Content-Type: ' . $mime_type);
        readfile($local_path);
        exit;
    }
}

if (empty($path)) {

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
        $html = $page['content_html'];
        
        // Fetch and apply global settings to replace hardcoded contact info
        try {
            $stmt = $pdo->query("SELECT * FROM settings");
            $settings_raw = $stmt->fetchAll();
            $settings = [];
            foreach ($settings_raw as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            // Replace Contact Information
            if (!empty($settings['contact_address'])) {
                $html = preg_replace('/Hardturmstrasse 134<br\s*\/?>CH-8005 Zurich/i', $settings['contact_address'], $html);
            }
            if (!empty($settings['contact_email'])) {
                // Find mailto: links and visible text
                $html = preg_replace('/mailto:info@swisscontact\.org/i', 'mailto:' . $settings['contact_email'], $html);
                $html = preg_replace('/info@swisscontact\.org/i', $settings['contact_email'], $html);
            }
            if (!empty($settings['contact_phone'])) {
                $html = preg_replace('/\+41 44 454 17 17/i', $settings['contact_phone'], $html);
            }
            if (!empty($settings['contact_map_url'])) {
                // Replace iframe src containing google.com/maps
                $html = preg_replace('/src="https:\/\/www\.google\.com\/maps\/embed[^"]+"/i', 'src="' . $settings['contact_map_url'] . '"', $html);
            }
        } catch (Exception $e) {}

        // Add polyfill for Usercentrics (uc) to prevent console errors
        $uc_polyfill = "<script>window.uc = window.uc || { reloadOnOptIn: function(){}, getServices: function(){return [];}, acceptAll: function(){} };</script></head>";
        $html = str_ireplace('</head>', $uc_polyfill, $html);

        // Output page content
        echo $html;
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
