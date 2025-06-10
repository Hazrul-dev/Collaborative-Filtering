<?php
$pageTitle = "Register";
require_once 'includes/header.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'functions/auth_functions.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    if (registerUser($username, $password, $name, $email, $phone, $address)) {
        $_SESSION['success'] = "Pendaftaran berhasil! Silakan login.";
        header('Location: login.php');
        exit();
    } else {
        $error = "Gagal mendaftar. Username atau email mungkin sudah digunakan.";
    }
}
?>

<div class="register-container">
    <h2>Buat Akun Baru</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form action="register.php" method="post">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="name">Nama Lengkap:</label>
            <input type="text" id="name" name="name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="phone">Nomor Telepon:</label>
            <input type="text" id="phone" name="phone" required>
        </div>
        
        <div class="form-group">
            <label for="address">Alamat:</label>
            <textarea id="address" name="address" required></textarea>
        </div>
        
        <button type="submit" class="btn">Daftar</button>
    </form>
    
    <p class="login-link">Sudah punya akun? <a href="login.php">Login disini</a></p>
</div>

<?php require_once 'includes/footer.php'; ?>