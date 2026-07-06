<?php
require_once __DIR__ . '/config.php';

try {
    $stmt = $pdo->prepare("SELECT content_html FROM pages WHERE url_path = '/en/footer/contact' LIMIT 1");
    $stmt->execute();
    $page = $stmt->fetch();
    
    if ($page) {
        $html = $page['content_html'];
        
        // Remove the original Neos form which has action containing '#form-'
        // It's wrapped in a div. We will use a regex to match the entire <form>...</form> block.
        // And also any wrapper div that might say "Contact Us" or similar.
        // Actually, matching the <form>...</form> is easiest.
        $html = preg_replace('/<form\s+[^>]*action="[^"]*#form-[^"]*"[^>]*>.*?<\/form>/is', '', $html);
        
        // Let's also check if there's a footer form the user complained about.
        // The user said "and even the footer own too present".
        // They might mean the footer contact link or the newsletter signup.
        // Let's remove any forms in the footer just for this page to keep it clean.
        // We can just hide all forms inside the footer via CSS on this page.
        $css_hide_footer_forms = "<style>footer form { display: none !important; }</style>";
        $html = str_replace('</head>', $css_hide_footer_forms . '</head>', $html);
        
        $stmt = $pdo->prepare("UPDATE pages SET content_html = :html WHERE url_path = '/en/footer/contact'");
        $stmt->execute(['html' => $html]);
        
        echo "Old form removed successfully.";
    } else {
        echo "Page not found.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
