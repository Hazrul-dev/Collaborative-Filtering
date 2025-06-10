<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/functions/auth_functions.php';
require_once __DIR__ . '/functions/order_functions.php';
require_once __DIR__ . '/functions/interaction_functions.php';
require_once __DIR__ . '/functions/recommendation_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$orderId = $_GET['id'] ?? null;
$userId = $_SESSION['user_id'];

// Dapatkan detail pesanan
$order = $orderId ? getOrderDetails($orderId, $userId) : null;

if (!$order) {
    $_SESSION['error'] = "Pesanan tidak ditemukan";
    header('Location: orders.php');
    exit();
}

// Proses konfirmasi penerimaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delivery'])) {
    if (confirmOrderDelivery($orderId, $userId)) {
        recordProductDeliveryInteractions($orderId, $userId);
        $_SESSION['success'] = "Pesanan telah dikonfirmasi diterima";
        header("Location: order_detail.php?id=$orderId");
        exit();
    } else {
        $_SESSION['error'] = "Gagal mengkonfirmasi pesanan";
    }
}

// Dapatkan rekomendasi produk berdasarkan pesanan ini
$recommendations = [];
if (!empty($order['items'])) {
    $firstProductId = $order['items'][0]['product_id'];
    $recommendations = getProductRecommendations($firstProductId, $userId, 4);
}
?>

<div class="order-detail-container">
    <div class="sidebar">
        <!-- Sidebar content -->
    </div>
    
    <div class="main-content">
        <div class="order-header">
            <h2>Detail Pesanan #INV-<?php echo $order['id']; ?></h2>
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
        
        <div class="order-meta">
            <div class="meta-item">
                <h4>Tanggal Pesanan</h4>
                <p><?php echo date('d F Y H:i', strtotime($order['order_date'])); ?></p>
            </div>
            
            <div class="meta-item">
                <h4>Total Pembayaran</h4>
                <p>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></p>
            </div>
            
            <?php if (!empty($order['payment_method'])): ?>
                <div class="meta-item">
                    <h4>Metode Pembayaran</h4>
                    <p>
                        <?php 
                        $methodMap = [
                            'bank_transfer' => 'Transfer Bank',
                            'e_wallet' => 'E-Wallet',
                            'cod' => 'COD (Bayar di Tempat)'
                        ];
                        echo $methodMap[$order['payment_method']] ?? ucfirst($order['payment_method']); 
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="order-items">
            <h3>Produk Dipesan</h3>
            <table class="order-items-table">
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
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td class="product-info">
                                <img src="assets/images/products/<?php echo htmlspecialchars($item['image'] ?? 'default.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="product-thumb">
                                <div>
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>SKU: <?php echo $item['product_id']; ?></p>
                                </div>
                            </td>
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
                        <td colspan="4" class="text-right">Subtotal</td>
                        <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-right">Ongkos Kirim</td>
                        <td>Rp <?php echo number_format($order['shipping_cost'] ?? 0, 0, ',', '.'); ?></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="4" class="text-right"><strong>Total</strong></td>
                        <td><strong>Rp <?php echo number_format($order['total'] + ($order['shipping_cost'] ?? 0), 0, ',', '.'); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="order-actions">
            <a href="orders.php" class="btn">Kembali ke Daftar Pesanan</a>
            
            <?php if ($order['status'] === 'pending'): ?>
                <a href="payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">Bayar Sekarang</a>
                <button class="btn btn-danger" onclick="confirmCancel(<?php echo $order['id']; ?>)">Batalkan Pesanan</button>
            <?php elseif ($order['status'] === 'shipped'): ?>
                <form method="POST" onsubmit="return confirm('Apakah Anda yakin pesanan sudah diterima?')">
                    <button type="submit" name="confirm_delivery" class="btn btn-success">
                        <i class="fas fa-check"></i> Konfirmasi Pesanan Diterima
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($order['status'] === 'delivered'): ?>
            <div class="alert alert-success">
                Pesanan telah dikonfirmasi diterima
                <?php if (isset($order['delivered_at']) && !empty($order['delivered_at'])): ?>
                    pada: <?php echo date('d M Y H:i', strtotime($order['delivered_at'])); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($order['tracking_number'])): ?>
            <div class="shipping-info">
                <h3>Informasi Pengiriman</h3>
                <div class="shipping-details">
                    <p><strong>Nomor Resi:</strong> <?php echo $order['tracking_number']; ?></p>
                    <p><strong>Jasa Pengiriman:</strong> <?php echo $order['shipping_service']; ?></p>
                    <?php if (!empty($order['estimated_delivery'])): ?>
                        <p><strong>Estimasi Tiba:</strong> <?php echo date('d F Y', strtotime($order['estimated_delivery'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($order['tracking_url'])): ?>
                        <a href="<?php echo $order['tracking_url']; ?>" target="_blank" class="btn btn-small">Lacak Pengiriman</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Rekomendasi Produk -->
        <?php if (!empty($recommendations)): ?>
            <div class="recommendations-section">
                <h3>Anda Mungkin Juga Suka</h3>
                <div class="recommended-products">
                    <?php foreach ($recommendations as $product): ?>
                        <div class="product-card">
                            <img src="assets/images/products/<?= htmlspecialchars($product['image'] ?? 'default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($product['name']) ?>">
                            <h4><?= htmlspecialchars($product['name']) ?></h4>
                            <p>Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                            <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn">Lihat Detail</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Validasi client-side
document.querySelector('form')?.addEventListener('submit', function(e) {
    if (!confirm('Apakah Anda yakin pesanan sudah diterima?')) {
        e.preventDefault();
    }
});

function confirmCancel(orderId) {
    if (confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) {
        fetch('api/cancel_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ order_id: orderId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Pesanan berhasil dibatalkan');
                location.reload();
            } else {
                alert('Gagal membatalkan pesanan: ' + data.message);
            }
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>