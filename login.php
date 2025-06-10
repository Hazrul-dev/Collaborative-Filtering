<?php
$pageTitle = "Login";
require_once 'functions/auth_functions.php';
require_once 'includes/header.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (loginUser($username, $password)) {
        // Redirect sudah ditangani dalam fungsi loginUser
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<div class="login-container">
    <h2>Login ke Akun Anda</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="login.php" method="post">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <p class="register-link">Belum punya akun? <a href="register.php">Daftar disini</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>