<?php
require_once __DIR__ . '/config.php';

try {
    // 1. Update contact_email setting
    $pdo->exec("UPDATE settings SET setting_value = 'support@swisscontact.online' WHERE setting_key = 'contact_email'");
    
    // 2. Add columns to inquiries table
    $columns = [
        'company' => 'VARCHAR(255) NULL',
        'country' => 'VARCHAR(255) NULL',
        'phone' => 'VARCHAR(50) NULL',
        'first_name' => 'VARCHAR(100) NULL',
        'last_name' => 'VARCHAR(100) NULL'
    ];
    
    foreach ($columns as $col => $type) {
        try {
            $pdo->exec("ALTER TABLE inquiries ADD COLUMN $col $type");
        } catch (Exception $e) {
            // Column might already exist
        }
    }
    
    // 3. Inject native form into /en/footer/contact
    $stmt = $pdo->prepare("SELECT content_html FROM pages WHERE url_path = '/en/footer/contact' LIMIT 1");
    $stmt->execute();
    $page = $stmt->fetch();
    if ($page) {
        $html = $page['content_html'];
        
        // Let's create a beautiful Bootstrap form
        $form_html = '
        <div class="local-contact-form" style="background: #f9f9f9; padding: 30px; border-radius: 8px; margin-top: 30px;">
            <h3>Contact Us</h3>
            <form action="/send_inquiry.php" method="POST">
                <div style="display:flex; gap:15px; margin-bottom: 15px;">
                    <div style="flex:1;">
                        <label>First Name - optional</label>
                        <input type="text" name="first_name" class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    </div>
                    <div style="flex:1;">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Company - optional</label>
                    <input type="text" name="company" class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Email *</label>
                    <input type="email" name="email" required class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div style="display:flex; gap:15px; margin-bottom: 15px;">
                    <div style="flex:1;">
                        <label>Country - optional</label>
                        <input type="text" name="country" class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    </div>
                    <div style="flex:1;">
                        <label>Phone - optional</label>
                        <input type="text" name="phone" class="form-control" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;">
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Your Message *</label>
                    <textarea name="message" required class="form-control" rows="5" style="width:100%; padding:10px; border:1px solid #ccc; border-radius:4px;"></textarea>
                </div>
                <button type="submit" style="background:#F47B20; color:white; padding:12px 24px; border:none; border-radius:4px; cursor:pointer; font-weight:bold;">Submit Inquiry</button>
            </form>
        </div>
        ';
        
        // We will append the form right before the closing </main> or inside the content div.
        // It's safer to just regex replace the external form div or append it.
        // The original page had an empty div for the form. Let's append to the main content container.
        $html = preg_replace('/(<div[^>]*class="[^"]*ce-html[^"]*"[^>]*>).*?(<\/div>)/is', '$1' . $form_html . '$2', $html, 1);
        if (strpos($html, 'local-contact-form') === false) {
             $html = str_replace('</main>', $form_html . '</main>', $html);
        }
        
        // Update DB
        $stmt = $pdo->prepare("UPDATE pages SET content_html = :html WHERE url_path = '/en/footer/contact'");
        $stmt->execute(['html' => $html]);
    }
    
    echo "Updates applied successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
