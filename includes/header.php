<?php
session_start();
require_once __DIR__ . '/../functions/auth_functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/images/IF.jpg" type="image/x-icon">
    <title>Toko Izra Fashion - <?php echo $pageTitle ?? 'E-commerce Hijab'; ?></title>
    <?php if (isset($isAdminPage) && $isAdminPage): ?>
        <link rel="stylesheet" href="../assets/css/admin.css">
    <?php else: ?>
        <link rel="stylesheet" href="assets/css/style.css">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <?php if (!(isLoggedIn() && (isAdmin() || isOwner()))): ?>
                <div class="logo">
                    <h1><a href="<?php echo isset($isAdminPage) ? '../index.php' : 'index.php'; ?>">Izra Fashion</a></h1>
                    <p>E-commerce Hijab Berkualitas</p>
                </div>
            <?php endif; ?>
            
            <nav>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin() || isOwner()): ?>
                        <a href="<?php echo isset($isAdminPage) ? 'dashboard.php' : 'admin/dashboard.php'; ?>">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="<?php echo isset($isAdminPage) ? '../dashboard.php' : 'dashboard.php'; ?>">Dashboard</a>
                    <a href="<?php echo isset($isAdminPage) ? '../products.php' : 'products.php'; ?>">Produk</a>
                    <a href="<?php echo isset($isAdminPage) ? '../logout.php' : 'logout.php'; ?>">Logout</a>
                    <span class="user-greeting">Halo, <?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'Pengguna'); ?></span>
                <?php else: ?>
                    <a href="<?php echo isset($isAdminPage) ? '../login.php' : 'login.php'; ?>">Login</a>
                    <a href="<?php echo isset($isAdminPage) ? '../register.php' : 'register.php'; ?>">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">