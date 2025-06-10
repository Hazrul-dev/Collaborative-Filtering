<?php

function requireAdminSession() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: /login.php');
        exit();
    }
    
    // Pastikan semua session variable yang diperlukan ada
    $_SESSION['name'] = $_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin';
}