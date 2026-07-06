import re
import os

db_path = r'c:\Users\USER\Downloads\swissconnect\swisscontact_db.sql'

with open(db_path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Replace absolute URLs with relative URLs for assets
content = content.replace('https://www.swisscontact.online/_Resources/', '/_Resources/')
content = content.replace('https://swisscontact.online/_Resources/', '/_Resources/')
content = content.replace('https://www.swisscontact.online/cdn-cgi/', '/cdn-cgi/')
content = content.replace('https://swisscontact.online/cdn-cgi/', '/cdn-cgi/')

# Also catch relative to domain root but we want to make sure it's just /
# The crawler downloaded files to `_Resources/`, `_ari/`, etc.
content = content.replace('https://www.swisscontact.online/_ari/', '/_ari/')
content = content.replace('https://swisscontact.online/_ari/', '/_ari/')
content = content.replace('https://www.swisscontact.online/', '/')
content = content.replace('https://swisscontact.online/', '/')

# 2. Remove the Cookie Consent banner HTML and Scripts
# It looks like it is powered by Usercentrics / Smart Data Protector.
# We will use regex to remove the specific script tags and HTML block.
cookie_script_regex = r'<script[^>]*src=["\'][^"\']*usercentrics[^"\']*["\'][^>]*>[\s\S]*?</script>'
content = re.sub(cookie_script_regex, '', content, flags=re.IGNORECASE)

cookie_html_regex = r'<div[^>]*class=["\'][^"\']*uc-block[^"\']*["\'][^>]*>[\s\S]*?</div>'
content = re.sub(cookie_html_regex, '', content, flags=re.IGNORECASE)

# The user mentioned this specific text: "We need your consent to load this content. This form uses cookies to check whether data is entered by a human or by an automated programme."
# There might be custom HTML wrappers around it.
consent_regex = r'<div[^>]*class="uc-embed[^>]*>[\s\S]*?Cookie Settings[\s\S]*?</div>'
content = re.sub(consent_regex, '', content, flags=re.IGNORECASE)

# Also remove smart data protector text
text_regex = r'We need your consent to load this content\.[\s\S]*?Cookie Settings'
content = re.sub(text_regex, '', content, flags=re.IGNORECASE)

# Append new tables for admin portal
new_tables = """

-- Admin Portal Tables
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) UNIQUE NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `admin_users` (`username`, `password_hash`) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- Password: password

CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES 
('contact_address', 'Hardturmstrasse 134<br>CH-8005 Zurich'),
('contact_email', 'donations@swisscontact.online'),
('contact_phone', '+41 44 454 17 17'),
('contact_map_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2701.554030799478!2d8.5135111156227!3d47.39121987917027!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47900a12e15cb9d5%3A0x6b87cf074c679b33!2sHardturmstrasse%20134%2C%208005%20Z%C3%BCrich%2C%20Switzerland!5e0!3m2!1sen!2sus!4v1614080123456!5m2!1sen!2sus');

CREATE TABLE IF NOT EXISTS `inquiries` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

"""
if "CREATE TABLE IF NOT EXISTS `admin_users`" not in content:
    content += new_tables

with open(db_path, 'w', encoding='utf-8') as f:
    f.write(content)

print("Database dump updated successfully.")
