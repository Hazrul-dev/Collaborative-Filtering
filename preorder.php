<?php
$pageTitle = "Pre Order";
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/order_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$preOrders = getPreOrdersByUser($userId);
?>

<div class="preorder-container">
    <div class="sidebar">
        <!-- Sama seperti dashboard -->
    </div>
    
    <div class="main-content">
        <h2>Pre Order Saya</h2>
        
        <?php if (empty($preOrders)): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-check"></i>
                <p>Anda belum memiliki pre order</p>
                <a href="products.php" class="btn">Lihat Produk</a>
            </div>
        <?php else: ?>
            <div class="preorder-list">
                <?php foreach ($preOrders as $order): ?>
                    <div class="preorder-card">
                        <div class="preorder-header">
                            <h3>#PO-<?php echo $order['id']; ?></h3>
                            <span class="status <?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                        
                        <div class="preorder-details">
                            <div class="product-info">
                                <img src="assets/images/products/<?php echo htmlspecialchars($order['image']); ?>" alt="<?php echo htmlspecialchars($order['product_name']); ?>">
                                <div>
                                    <h4><?php echo htmlspecialchars($order['product_name']); ?></h4>
                                    <p>Jumlah: <?php echo $order['quantity']; ?></p>
                                    <p>Total: Rp <?php echo number_format($order['quantity'] * $order['price'], 0, ',', '.'); ?></p>
                                </div>
                            </div>
                            
                            <div class="preorder-meta">
                                <p><i class="fas fa-calendar"></i> Tanggal Pre Order: <?php echo date('d M Y', strtotime($order['created_at'])); ?></p>
                                <p><i class="fas fa-clock"></i> Estimasi Tersedia: <?php echo date('d M Y', strtotime($order['expected_date'])); ?></p>
                            </div>
                        </div>
                        
                        <?php if ($order['status'] == 'pending'): ?>
                            <div class="preorder-actions">
                                <a href="cancel_preorder.php?id=<?php echo $order['id']; ?>" class="btn btn-danger">Batalkan</a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>