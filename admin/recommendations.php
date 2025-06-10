<?php
$pageTitle = "Rekomendasi Produk";
$isAdminPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/recommendation_functions.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

// Hitung ulang rekomendasi produk
if (isset($_GET['recalculate'])) {
    try {
        $success = calculate_product_similarity($pdo);
        
        if ($success) {
            log_activity($_SESSION['user_id'], "Menghitung ulang rekomendasi produk");
            $_SESSION['success'] = "Rekomendasi produk berhasil dihitung ulang!";
        } else {
            $_SESSION['error'] = "Gagal menghitung rekomendasi. Pastikan ada pesanan yang sudah selesai dan produk aktif.";
        }
        header("Location: recommendations.php");
        exit();
    } catch (Exception $e) {
        error_log("Error in recommendations.php: " . $e->getMessage());
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: recommendations.php");
        exit();
    }
}

// Dapatkan semua produk aktif
$products = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY name")->fetchAll();

// Dapatkan rekomendasi untuk setiap produk
$recommendations = [];
foreach ($products as $product) {
    $stmt = $pdo->prepare("
        SELECT p.*, pr.similarity_score 
        FROM product_recommendations pr
        JOIN products p ON pr.recommended_product_id = p.id
        WHERE pr.product_id = ? AND p.stock > 0
        ORDER BY pr.similarity_score DESC
        LIMIT 3
    ");
    $stmt->execute([$product['id']]);
    $recommendations[$product['id']] = $stmt->fetchAll();
}

// Dapatkan produk terpopuler sebagai fallback
$popularProducts = get_popular_products($pdo, 10);
?>

<div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-profile">
            <img src="../assets/images/IF.jpg" alt="Admin Avatar">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Admin</p>
            </div>
            
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="active"><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
    
    <div class="admin-content">
        <div class="admin-header">
            <h1>Rekomendasi Produk</h1>
            <a href="recommendations.php?recalculate=1" class="btn" onclick="return confirm('Proses ini mungkin memakan waktu. Apakah Anda yakin ingin menghitung ulang rekomendasi produk?')">
                <i class="fas fa-sync-alt"></i> Hitung Ulang Rekomendasi
            </a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <div class="admin-card">
            <div class="card-header">
                <h2>Sistem Rekomendasi Produk</h2>
            </div>
            <div class="card-body">
                <p>Sistem ini menggunakan <strong>Item-Based Collaborative Filtering</strong> dengan alur kerja:</p>
                <ol>
                    <li>Menganalisis pola pembelian dari pesanan yang sudah selesai</li>
                    <li>Menghitung similaritas antar produk menggunakan Cosine Similarity</li>
                    <li>Menyimpan 5 rekomendasi teratas untuk setiap produk</li>
                    <li>Untuk pengguna baru/tanpa riwayat, menampilkan produk terpopuler</li>
                </ol>
                
                <div class="metrics">
                    <div class="metric">
                        <h4><?= count($products); ?></h4>
                        <p>Produk Aktif</p>
                    </div>
                    <div class="metric">
                        <h4><?= $pdo->query("SELECT COUNT(DISTINCT order_id) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status = 'delivered'")->fetchColumn(); ?></h4>
                        <p>Pesanan Selesai</p>
                    </div>
                    <div class="metric">
                        <h4><?= $pdo->query("SELECT COUNT(*) FROM product_recommendations")->fetchColumn(); ?></h4>
                        <p>Rekomendasi Tersimpan</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="admin-card mt-4">
            <div class="card-header">
                <h2>Daftar Rekomendasi Produk</h2>
            </div>
            <div class="card-body">
                <?php if (empty($products)): ?>
                    <div class="no-data">
                        <p>Tidak ada produk aktif yang ditemukan.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Rekomendasi</th>
                                    <th>Skor Similarity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <?php if (!empty($product['gambar'])): ?>
                                                    <img src="../assets/images/products/<?= htmlspecialchars($product['gambar']); ?>" 
                                                        alt="<?= htmlspecialchars($product['nama_produk'] ?? 'Product Image'); ?>" 
                                                        class="product-thumb">
                                                <?php endif; ?>
                                                <div>
                                                    <h5><?= htmlspecialchars($product['name'] ?? 'No Name'); ?></h5>
                                                    <small>Stok: <?= $product['stock']; ?> | Rp <?= number_format($product['harga'] ?? 0, 0, ',', '.'); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($recommendations[$product['id']])): ?>
                                                <ul class="recommendation-list">
                                                    <?php foreach ($recommendations[$product['id']] as $rec): ?>
                                                        <li>
                                                            <div class="d-flex align-items-center">
                                                                <?php if (!empty($rec['gambar'])): ?>
                                                                    <img src="../assets/images/products/<?= htmlspecialchars($rec['gambar']); ?>" 
                                                                        alt="<?= htmlspecialchars($rec['nama_produk'] ?? 'Recommended Product'); ?>" 
                                                                        class="product-thumb-sm">
                                                                <?php endif; ?>
                                                                <div>
                                                                    <?= htmlspecialchars($rec['nama_produk'] ?? 'No Name'); ?>
                                                                    <small>Rp <?= number_format($rec['harga'] ?? 0, 0, ',', '.'); ?></small>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <div class="no-recommendations">
                                                    <p>Belum ada rekomendasi</p>
                                                    <?php if (!empty($popularProducts)): ?>
                                                        <small>Produk populer:</small>
                                                        <ul>
                                                            <?php foreach (array_slice($popularProducts, 0, 3) as $pop): ?>
                                                                <li><?= htmlspecialchars($pop['nama_produk'] ?? 'No Name'); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($recommendations[$product['id']])): ?>
                                                <ul class="similarity-scores">
                                                    <?php foreach ($recommendations[$product['id']] as $rec): ?>
                                                        <li>
                                                            <div class="progress">
                                                                <div class="progress-bar" role="progressbar" 
                                                                    style="width: <?= ($rec['similarity_score'] ?? 0) * 100; ?>%;" 
                                                                    aria-valuenow="<?= ($rec['similarity_score'] ?? 0) * 100; ?>" 
                                                                    aria-valuemin="0" aria-valuemax="100">
                                                                    <?= number_format($rec['similarity_score'] ?? 0, 3); ?>
                                                                </div>
                                                            </div>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php else: ?>
                                                <p class="text-muted">-</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
    $(document).ready(function() {
        // Toggle sidebar
        $('.menu-toggle').click(function() {
            $('.admin-sidebar').toggleClass('active');
        });
    });
    
    function recalculateRecommendations() {
        if (confirm('Proses ini mungkin memakan waktu. Apakah Anda yakin ingin menghitung ulang rekomendasi produk?')) {
            fetch('api/recalculate_recommendations.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rekomendasi berhasil dihitung ulang!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }
    }
</script>
</body>
</html>