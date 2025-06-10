<?php
$pageTitle = "Rekomendasi Produk";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/functions/auth_functions.php';
require_once __DIR__ . '/functions/recommendation_functions.php';

$userId = $_SESSION['user_id'] ?? null;
$recommendedProducts = get_recommended_products($pdo, $userId, 12);
?>

<div class="container">
    <div class="page-header">
        <h1>Rekomendasi Produk Untuk Anda</h1>
        <p>Produk-produk pilihan berdasarkan riwayat pembelian Anda</p>
    </div>
    
    <?php if (empty($recommendedProducts)): ?>
        <div class="alert info">
            <p>Belum ada rekomendasi spesifik untuk Anda. Berikut beberapa produk terpopuler:</p>
            <?php 
            $popularProducts = get_popular_products($pdo, 12);
            if (!empty($popularProducts)): ?>
                <div class="product-grid">
                    <?php foreach ($popularProducts as $product): ?>
                        <div class="product-card">
                            <img src="assets/images/products/<?= htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" 
                                 alt="<?= htmlspecialchars($product['nama_produk'] ?? 'Product Image'); ?>">
                            <h3><?= htmlspecialchars($product['nama_produk'] ?? 'No Name'); ?></h3>
                            <p class="price">Rp <?= number_format($product['harga'] ?? 0, 0, ',', '.'); ?></p>
                            <a href="product.php?id=<?= $product['id']; ?>" class="btn">Lihat Detail</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($recommendedProducts as $product): ?>
                <div class="product-card">
                    <img src="assets/images/products/<?= htmlspecialchars($product['gambar'] ?? 'default.jpg'); ?>" 
                         alt="<?= htmlspecialchars($product['nama_produk'] ?? 'Product Image'); ?>">
                    <h3><?= htmlspecialchars($product['nama_produk'] ?? 'No Name'); ?></h3>
                    <p class="price">Rp <?= number_format($product['harga'] ?? 0, 0, ',', '.'); ?></p>
                    <?php if (isset($product['recommendation_score'])): ?>
                        <div class="recommendation-badge">
                            <i class="fas fa-thumbs-up"></i> Rekomendasi
                            <small>(Skor: <?= number_format($product['recommendation_score'], 2); ?>)</small>
                        </div>
                    <?php endif; ?>
                    <a href="product.php?id=<?= $product['id']; ?>" class="btn">Lihat Detail</a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>