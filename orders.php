<?php
$pageTitle = "Pesanan Saya";
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/order_functions.php';
require_once 'functions/recommendation_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$orders = getOrdersByUser($userId);
?>

<div class="orders-container">
    <div class="sidebar">
        <!-- Sidebar content -->
    </div>
    
    <div class="main-content">
        <h2>Pesanan Saya</h2>
        
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <p>Anda belum memiliki pesanan</p>
                <a href="products.php" class="btn">Lihat Produk</a>
            </div>
        <?php else: ?>
            <div class="orders-list">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>#INV-<?php echo $order['id']; ?></h3>
                            <span class="status <?php echo $order['status']; ?>">
                                <?php 
                                $statusMap = [
                                    'pending' => 'Menunggu Pembayaran',
                                    'processing' => 'Diproses',
                                    'shipped' => 'Dikirim',
                                    'delivered' => 'Selesai',
                                    'cancelled' => 'Dibatalkan'
                                ];
                                echo $statusMap[$order['status']] ?? ucfirst($order['status']); 
                                ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Warna</th>
                                        <th>Harga</th>
                                        <th>Jumlah</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (getOrderItems($order['id']) as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td>
                                                <?php if (!empty($item['color_name'])): ?>
                                                    <span class="color-badge" style="background-color: <?php echo $item['color_code']; ?>">
                                                        <?php echo $item['color_name']; ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right">Total</td>
                                        <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <div class="order-actions">
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn">Detail Pesanan</a>
                            <?php if ($order['status'] == 'pending'): ?>
                                <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">Bayar Sekarang</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>