<?php
$pageTitle = "Kelola Pesanan";
$isAdminPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/order_functions.php';

// Periksa apakah user adalah admin
if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$orderId = $_GET['id'] ?? null;

// Tangani aksi penghapusan pesanan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    if (deleteOrder($_POST['order_id'])) {
        $_SESSION['success'] = "Pesanan berhasil dihapus!";
        header('Location: orders.php');
        exit();
    } else {
        header("Location: orders.php?action=detail&id=".$_POST['order_id']);
        exit();
    }
}

// Tangani aksi yang berbeda
switch ($action) {
    case 'detail':
        showOrderDetail($orderId);
        break;
    case 'update-status':
        updateOrderStatus($orderId);
        break;
    case 'list':
    default:
        showOrderList();
        break;
}

function deleteOrder($orderId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Hapus item pesanan terlebih dahulu karena constraint foreign key
        $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Hapus pembayaran terkait
        $stmt = $pdo->prepare("DELETE FROM payments WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Hapus pengiriman terkait
        $stmt = $pdo->prepare("DELETE FROM shipments WHERE order_id = ?");
        $stmt->execute([$orderId]);
        
        // Hapus pesanan
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal menghapus pesanan: " . $e->getMessage();
        return false;
    }
}

function showOrderDetail($orderId) {
    global $pdo;
    
    $order = getOrderById($orderId);
    if (!$order) {
        $_SESSION['error'] = "Pesanan tidak ditemukan!";
        header('Location: orders.php');
        exit();
    }
    
    $orderItems = getOrderItems($orderId);
    $customer = getUserById($order['user_id']);
    $payment = getPaymentByOrderId($orderId);
    $shipment = getShipmentByOrderId($orderId);
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
                <li><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li class="active"><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Detail Pesanan #<?php echo $order['id']; ?></h1>
                <a href="orders.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="order-detail">
                <div class="order-section">
                    <h2>Informasi Pesanan</h2>
                    <div class="order-info-grid">
                        <div>
                            <p><strong>ID Pesanan:</strong> #<?php echo $order['id']; ?></p>
                            <p><strong>Tanggal Pesanan:</strong> <?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php 
                                        $statusMap = [
                                            'pending' => 'Menunggu Pembayaran',
                                            'processing' => 'Diproses',
                                            'shipped' => 'Dikirim',
                                            'delivered' => 'Sampai',
                                            'cancelled' => 'Dibatalkan'
                                        ];
                                        echo $statusMap[$order['status']] ?? $order['status']; 
                                    ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <p><strong>Total Pesanan:</strong> Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></p>
                            <p><strong>Metode Pembayaran:</strong> <?php echo $payment ? ucfirst($payment['method']) : '-'; ?></p>
                            <p><strong>Status Pembayaran:</strong> <?php echo $payment ? ucfirst($payment['status']) : '-'; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="order-section">
                    <h2>Informasi Pelanggan</h2>
                    <div class="order-info-grid">
                        <div>
                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                            <p><strong>Telepon:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                        </div>
                        <div>
                            <p><strong>Alamat Pengiriman:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="order-section">
                    <h2>Produk yang Dipesan</h2>
                    <table class="admin-table">
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
                            <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name'] ?? $item['name'] ?? 'Nama Produk Tidak Tersedia'); ?></td>
                                    <td>
                                        <?php if (!empty($item['color_name'])): ?>
                                            <span class="color-badge" style="background-color: <?= $item['color_code'] ?>" 
                                                  data-color="<?= $item['color_name'] ?>"></span>
                                            <?= $item['color_name'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="4" class="text-right"><strong>Total:</strong></td>
                                <td><strong>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($shipment): ?>
                <div class="order-section">
                    <h2>Informasi Pengiriman</h2>
                    <div class="order-info-grid">
                        <div>
                            <p><strong>Nomor Resi:</strong> <?php echo htmlspecialchars($shipment['tracking_number'] ?? '-'); ?></p>
                            <p><strong>Metode Pengiriman:</strong> <?php echo htmlspecialchars($shipment['shipping_method']); ?></p>
                            <p><strong>Status Pengiriman:</strong> 
                                <span class="status-badge <?php echo $shipment['status']; ?>">
                                    <?php 
                                        $statusMap = [
                                            'preparing' => 'Disiapkan',
                                            'shipped' => 'Dikirim',
                                            'in_transit' => 'Dalam Perjalanan',
                                            'delivered' => 'Terkirim'
                                        ];
                                        echo $statusMap[$shipment['status']] ?? $shipment['status']; 
                                    ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <p><strong>Tanggal Pengiriman:</strong> <?php echo $shipment['shipping_date'] ? date('d M Y', strtotime($shipment['shipping_date'])) : '-'; ?></p>
                            <p><strong>Estimasi Sampai:</strong> <?php echo $shipment['estimated_delivery'] ? date('d M Y', strtotime($shipment['estimated_delivery'])) : '-'; ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="order-actions">
                    <form action="orders.php?action=update-status&id=<?php echo $order['id']; ?>" method="post" class="order-status-form">
                        <div class="form-group">
                            <label for="status">Ubah Status Pesanan:</label>
                            <select name="status" id="status" required>
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Sampai</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <?php if ($order['status'] === 'shipped' || $order['status'] === 'delivered'): ?>
                        <div class="form-group" id="tracking-number-group">
                            <label for="tracking_number">Nomor Resi:</label>
                            <input type="text" name="tracking_number" id="tracking_number" value="<?php echo htmlspecialchars($shipment['tracking_number'] ?? ''); ?>">
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn">Perbarui Status</button>
                    </form>
                    
                    <div class="additional-actions">
                        <?php if ($order['status'] === 'pending' || $order['status'] === 'cancelled'): ?>
                            <form action="orders.php" method="post" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="delete_order" class="btn danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pesanan ini?')">
                                    <i class="fas fa-trash"></i> Hapus Pesanan
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="orders.php?action=print&id=<?php echo $order['id']; ?>" class="btn" target="_blank"><i class="fas fa-print"></i> Cetak Invoice</a>
                        <?php if (!$payment && $order['status'] === 'pending'): ?>
                            <a href="payments.php?action=add&order_id=<?php echo $order['id']; ?>" class="btn"><i class="fas fa-money-bill-wave"></i> Tambah Pembayaran</a>
                        <?php endif; ?>
                        <?php if (!$shipment && ($order['status'] === 'processing' || $order['status'] === 'shipped')): ?>
                            <a href="shipments.php?action=add&order_id=<?php echo $order['id']; ?>" class="btn"><i class="fas fa-truck"></i> Tambah Pengiriman</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tampilkan/menyembunyikan nomor resi berdasarkan status
        document.getElementById('status').addEventListener('change', function() {
            const trackingGroup = document.getElementById('tracking-number-group');
            if (this.value === 'shipped' || this.value === 'delivered') {
                if (!trackingGroup) {
                    const formGroup = document.createElement('div');
                    formGroup.className = 'form-group';
                    formGroup.id = 'tracking-number-group';
                    formGroup.innerHTML = `
                        <label for="tracking_number">Nomor Resi:</label>
                        <input type="text" name="tracking_number" id="tracking_number">
                    `;
                    document.querySelector('.order-status-form').insertBefore(formGroup, document.querySelector('.order-status-form button'));
                }
            } else if (trackingGroup) {
                trackingGroup.remove();
            }
        });
    </script>
    <?php
}

function updateOrderStatus($orderId) {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: orders.php');
        exit();
    }
    
    $status = $_POST['status'];
    $trackingNumber = $_POST['tracking_number'] ?? null;
    
    try {
        $pdo->beginTransaction();
        
        // Update status pesanan
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orderId]);
        
        // Jika status shipped/delivered dan ada nomor resi, update pengiriman
        if (($status === 'shipped' || $status === 'delivered') && $trackingNumber) {
            $shipment = getShipmentByOrderId($orderId);
            
            if ($shipment) {
                $stmt = $pdo->prepare("UPDATE shipments SET tracking_number = ?, status = ? WHERE order_id = ?");
                $stmt->execute([$trackingNumber, $status, $orderId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO shipments (order_id, tracking_number, shipping_method, status, shipping_date) VALUES (?, ?, 'JNE', ?, NOW())");
                $stmt->execute([$orderId, $trackingNumber, $status]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Status pesanan berhasil diperbarui!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal memperbarui status pesanan: " . $e->getMessage();
    }
    
    header("Location: orders.php?action=detail&id=$orderId");
    exit();
}

function showOrderList() {
    global $pdo;
    
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    $page = max(1, $_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id";
    $countQuery = "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id";
    $params = [];
    $where = [];
    
    if ($status) {
        $where[] = "o.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(u.name LIKE ? OR o.id = ?)";
        $params[] = "%$search%";
        $params[] = $search;
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
        $countQuery .= " WHERE " . implode(" AND ", $where);
    }
    
    $query .= " ORDER BY o.order_date DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalOrders = $stmt->fetchColumn();
    $totalPages = ceil($totalOrders / $limit);
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
                <li><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li class="active"><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Kelola Pesanan</h1>
                <div class="header-actions">
                    <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Cetak</button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="admin-filters">
                <form method="get" action="orders.php">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Cari nama pelanggan atau ID pesanan..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn-small"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="form-group">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                            <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                            <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Sampai</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada pesanan ditemukan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></td>
                                    <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $order['status']; ?>">
                                            <?php 
                                                $statusMap = [
                                                    'pending' => 'Menunggu Pembayaran',
                                                    'processing' => 'Diproses',
                                                    'shipped' => 'Dikirim',
                                                    'delivered' => 'Sampai',
                                                    'cancelled' => 'Dibatalkan'
                                                ];
                                                echo $statusMap[$order['status']] ?? $order['status']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="orders.php?action=detail&id=<?php echo $order['id']; ?>" class="btn-small"><i class="fas fa-eye"></i></a>
                                        <?php if ($order['status'] === 'pending' || $order['status'] === 'cancelled'): ?>
                                            <form action="orders.php" method="post" style="display:inline;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="delete_order" class="btn-small danger" onclick="return confirm('Apakah Anda yakin ingin menghapus pesanan ini?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="orders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">&laquo; Sebelumnya</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="orders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="orders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">Selanjutnya &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>