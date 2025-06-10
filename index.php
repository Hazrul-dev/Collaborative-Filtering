<?php
$pageTitle = "Beranda";
require_once 'includes/header.php';
require_once 'functions/product_functions.php';


// Dapatkan produk terbaru dan produk terlaris
$newProducts = getNewProducts(4);
$popularProducts = getPopularProducts(4);
?>

<div class="hero-section">
    <div class="container">
    <img src="assets/images/IF.jpg" alt="Logo Izra Fashion" class="company-logo">
        <h1>Selamat Datang di Toko Izra Fashion</h1>
        <p>Temukan koleksi hijab terbaru dengan kualitas terbaik</p>
        <a href="products.php" class="btn btn-large">Lihat Produk</a>
    </div>
</div>

<div class="container">
    <section class="section">
        <h2>Produk Terbaru</h2>
        <div class="products-grid">
            <?php foreach ($newProducts as $product): ?>
                <div class="product-card">
                    <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                    <a href="products.php?action=detail&id=<?php echo $product['id']; ?>" class="btn">Lihat Detail</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <section class="section">
        <h2>Produk Terlaris</h2>
        <div class="products-grid">
            <?php foreach ($popularProducts as $product): ?>
                <div class="product-card">
                    <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                    <a href="products.php?action=detail&id=<?php echo $product['id']; ?>" class="btn">Lihat Detail</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    
    <section class="section features">
        <div class="feature">
            <i class="fas fa-truck"></i>
            <h3>Gratis Ongkir</h3>
            <p>Untuk pembelian di atas Rp 200.000</p>
        </div>
        <div class="feature">
            <i class="fas fa-undo"></i>
            <h3>Pengembalian Mudah</h3>
            <p>Garansi 7 hari pengembalian</p>
        </div>
        <div class="feature">
            <i class="fas fa-lock"></i>
            <h3>Pembayaran Aman</h3>
            <p>Sistem pembayaran yang terjamin</p>
        </div>
        <div class="feature">
            <i class="fas fa-headset"></i>
            <h3>Dukungan 24/7</h3>
            <p>Layanan pelanggan siap membantu</p>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>