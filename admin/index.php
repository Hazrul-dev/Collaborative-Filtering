<?php
require_once __DIR__ . '/../functions/auth_functions.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

// Redirect ke dashboard admin
header('Location: dashboard.php');
exit();
?>