<?php
session_start();

// Make sure config is available
require_once __DIR__ . '/../config.php';

// Check if user is logged in
function require_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}
