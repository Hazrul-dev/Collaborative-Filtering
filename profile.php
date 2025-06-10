<?php
$pageTitle = "Profil Pengguna";
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/user_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Ambil data user
$userId = $_SESSION['user_id'];
$user = getUserById($userId);

// Tangani form update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    if (updateUserProfile($userId, $name, $email, $phone, $address)) {
        $_SESSION['success'] = "Profil berhasil diperbarui!";
        $_SESSION['name'] = $name; // Update session
        header('Location: profile.php');
        exit();
    } else {
        $_SESSION['error'] = "Gagal memperbarui profil.";
    }
}
?>

<div class="profile-container">
    <div class="sidebar">
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Produk</a></li>
            <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi</a></li>
            <li><a href="preorder.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Pesanan Saya</a></li>
            <li><a href="payment.php"><i class="fas fa-credit-card"></i> Pembayaran</a></li>
            <li><a href="shipping.php"><i class="fas fa-truck"></i> Pengiriman</a></li>
            <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h2>Profil Saya</h2>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="profile-form">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nomor Telepon</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label>Alamat</label>
                <textarea name="address"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>
            
            <button type="submit" class="btn">Simpan Perubahan</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>