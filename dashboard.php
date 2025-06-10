<?php
$pageTitle = "Dashboard";
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/product_functions.php';
require_once 'functions/order_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Dapatkan rekomendasi produk untuk pengguna
$recommendedProducts = getRecommendedProducts($_SESSION['user_id'], 4);
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li class="active"><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Produk</a></li>
            <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi</a></li>
            <li><a href="preorder.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Pesanan Saya</a></li>
            <li><a href="payment.php"><i class="fas fa-credit-card"></i> Pembayaran</a></li>
            <li><a href="shipping.php"><i class="fas fa-truck"></i> Pengiriman</a></li>
            <li><a href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="welcome-header">
            <h2>Selamat Datang, 
                <div class="profile-dropdown">
                    <span class="profile-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <div class="dropdown-content">
                        <a href="profile.php"><i class="fas fa-user"></i> Profil Saya</a>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </h2>
        </div>
        
        <div class="stats">
        <div class="card p-3 shadow-sm">
                <h3>Total Pesanan</h3>
                <p><?php echo getOrderCount($_SESSION['user_id']); ?></p>
            </div>
            <div class="card p-3 shadow-sm">

                <h3>Pre Order Aktif</h3>
                <p><?php echo getActivePreOrderCount($_SESSION['user_id']); ?></p>
            </div>
            <div class="card p-3 shadow-sm">

                <h3>Pesanan Dikirim</h3>
                <p><?php echo getShippedOrderCount($_SESSION['user_id']); ?></p>
            </div>
        </div>
        
        <div class="recommendations-section">
            <h3>Rekomendasi Untuk Anda</h3>
            <div class="products-grid">
                <?php foreach ($recommendedProducts as $product): ?>
                    <div class="product-card">
                        <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn">Lihat Detail</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>