<?php
$pageTitle = "Pengiriman";
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/shipping_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$shipments = getShipmentsByUser($userId);
?>

<div class="shipping-container">
    <div class="sidebar">
        <!-- Sama seperti dashboard -->
    </div>
    
    <div class="main-content">
        <h2>Status Pengiriman</h2>
        
        <?php if (empty($shipments)): ?>
            <div class="empty-state">
                <i class="fas fa-truck"></i>
                <p>Tidak ada data pengiriman</p>
                <a href="orders.php" class="btn">Lihat Pesanan Saya</a>
            </div>
        <?php else: ?>
            <div class="shipments-list">
                <?php foreach ($shipments as $shipment): ?>
                    <div class="shipment-card">
                        <div class="shipment-header">
                            <h3>Pesanan #INV-<?php echo $shipment['order_id']; ?></h3>
                            <span class="status <?php echo $shipment['status']; ?>">
                                <?php echo ucfirst($shipment['status']); ?>
                            </span>
                        </div>
                        
                        <div class="shipment-details">
                        <div class="detail-row">
                            <div class="detail-label">Jasa Pengiriman</div>
                            <div class="detail-value"><?php echo htmlspecialchars($shipment['shipping_method']); ?></div>
                        </div>
                        
                        <?php if ($shipment['tracking_number']): ?>
                            <div class="detail-row">
                                <div class="detail-label">Nomor Resi</div>
                                <div class="detail-value"><?php echo htmlspecialchars($shipment['tracking_number']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                        <div class="detail-row">
                        <div class="detail-label">Alamat Pengiriman</div>
                        <div class="detail-value">
                            <?php if (!empty($shipment['shipping_address'])): ?>
                                <?php echo nl2br(htmlspecialchars($shipment['shipping_address'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Alamat tidak tersedia</span>
                            <?php endif; ?>
                        </div>
                    </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Tanggal Pengiriman</div>
                            <div class="detail-value">
                                <?php echo $shipment['shipping_date'] ? date('d M Y', strtotime($shipment['shipping_date'])) : '-'; ?>
                            </div>
                        </div>
                        
                        <?php if ($shipment['estimated_delivery']): ?>
                            <div class="detail-row">
                                <div class="detail-label">Estimasi Tiba</div>
                                <div class="detail-value"><?php echo date('d M Y', strtotime($shipment['estimated_delivery'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                        
                        <?php if ($shipment['status'] == 'shipped' || $shipment['status'] == 'in_transit'): ?>
                            <div class="tracking-progress">
                                <div class="progress-step <?php echo $shipment['status'] == 'preparing' ? 'active' : 'completed'; ?>">
                                    <div class="step-icon"><i class="fas fa-box"></i></div>
                                    <div class="step-label">Pesanan Diproses</div>
                                </div>
                                <div class="progress-step <?php echo $shipment['status'] == 'shipped' ? 'active' : ($shipment['status'] == 'in_transit' || $shipment['status'] == 'delivered' ? 'completed' : ''); ?>">
                                    <div class="step-icon"><i class="fas fa-shipping-fast"></i></div>
                                    <div class="step-label">Dikirim</div>
                                </div>
                                <div class="progress-step <?php echo $shipment['status'] == 'in_transit' ? 'active' : ($shipment['status'] == 'delivered' ? 'completed' : ''); ?>">
                                    <div class="step-icon"><i class="fas fa-truck-moving"></i></div>
                                    <div class="step-label">Dalam Perjalanan</div>
                                </div>
                                <div class="progress-step <?php echo $shipment['status'] == 'delivered' ? 'active' : ''; ?>">
                                    <div class="step-icon"><i class="fas fa-home"></i></div>
                                    <div class="step-label">Terkirim</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>