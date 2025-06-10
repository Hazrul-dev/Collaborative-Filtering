<?php

$pageTitle = "Kelola Pengiriman";
$isAdminPage = true;

// Include files with absolute paths
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/order_functions.php';
require_once __DIR__ . '/../functions/shipping_functions.php';

// Periksa apakah user adalah admin
if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$orderId = $_GET['order_id'] ?? null;

// Handle different actions
switch ($action) {
    case 'add':
        handleAddShipment($orderId);
        break;
    case 'edit':
        handleEditShipment();
        break;
    case 'list':
    default:
        showShipmentList();
        break;
}

function handleAddShipment($orderId) {
    global $pdo;
    
    // Get order details
    $order = getOrderById($orderId);
    if (!$order) {
        $_SESSION['error'] = "Pesanan tidak ditemukan!";
        header('Location: shipments.php');
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $shippingMethod = $_POST['shipping_method'];
        $trackingNumber = $_POST['tracking_number'];
        $estimatedDelivery = $_POST['estimated_delivery'];
        $address = $_POST['address'];
        $status = 'shipped';

        try {
            $pdo->beginTransaction();
            
            // Insert shipment record
            $stmt = $pdo->prepare("
                INSERT INTO shipments 
                (order_id, shipping_method, tracking_number, address, estimated_delivery, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId,
                $shippingMethod,
                $trackingNumber,
                $address,
                $estimatedDelivery,
                $status
            ]);

            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET status = 'shipped' WHERE id = ?");
            $stmt->execute([$orderId]);

            $pdo->commit();
            
            $_SESSION['success'] = "Data pengiriman berhasil ditambahkan!";
            header('Location: shipments.php');
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Gagal menambahkan pengiriman: " . $e->getMessage();
        }
    }

    // Display add shipment form
    ?>
    <div class="admin-container">
        <div class="admin-sidebar">
        <div class="admin-profile">
                <img src="../assets/images/default-avatar.png" alt="Admin Avatar">
                <h3><?php echo htmlspecialchars($_SESSION['name']); ?></h3>
                <p>Admin</p>
            </div>
            
            <ul class="admin-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li class="active"><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Tambah Data Pengiriman</h1>
                <a href="shipments.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="shipment-form">
                <form method="post">
                    <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
                    
                    <div class="form-group">
                        <label for="shipping_method">Jasa Pengiriman</label>
                        <select name="shipping_method" id="shipping_method" class="form-control" required>
                            <option value="">Pilih Jasa Pengiriman</option>
                            <option value="JNE">JNE</option>
                            <option value="J&T">J&T</option>
                            <option value="SiCepat">SiCepat</option>
                            <option value="Ninja Express">Ninja Express</option>
                            <option value="POS Indonesia">POS Indonesia</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tracking_number">Nomor Resi</label>
                        <input type="text" name="tracking_number" id="tracking_number" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="estimated_delivery">Estimasi Tiba</label>
                        <input type="date" name="estimated_delivery" id="estimated_delivery" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Alamat Pengiriman</label>
                        <textarea name="address" id="address" class="form-control" rows="4" required><?php 
                            echo htmlspecialchars($order['shipping_address'] ?? '');
                        ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Simpan Pengiriman</button>
                </form>
            </div>
        </div>
    </div>
    <?php
}

function handleEditShipment($shipmentId) {
    global $pdo;
    
    // Get shipment details
    $stmt = $pdo->prepare("
        SELECT s.*, o.order_date, u.name as customer_name, u.email, u.phone
        FROM shipments s
        JOIN orders o ON s.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$shipmentId]);
    $shipment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$shipment) {
        $_SESSION['error'] = "Data pengiriman tidak ditemukan!";
        header('Location: shipments.php');
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $shippingMethod = $_POST['shipping_method'];
        $trackingNumber = $_POST['tracking_number'];
        $estimatedDelivery = $_POST['estimated_delivery'];
        $address = $_POST['address'];
        $status = $_POST['status'];

        try {
            $pdo->beginTransaction();
            
            // Update shipment record
            $stmt = $pdo->prepare("
                UPDATE shipments 
                SET shipping_method = ?, 
                    tracking_number = ?, 
                    address = ?, 
                    estimated_delivery = ?, 
                    status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $shippingMethod,
                $trackingNumber,
                $address,
                $estimatedDelivery,
                $status,
                $shipmentId
            ]);

            // Update order status if shipment is delivered
            if ($status === 'delivered') {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
                $stmt->execute([$shipment['order_id']]);
            }

            $pdo->commit();
            
            $_SESSION['success'] = "Data pengiriman berhasil diperbarui!";
            header('Location: shipments.php?action=edit&id='.$shipmentId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Gagal memperbarui pengiriman: " . $e->getMessage();
        }
    }

    // Display edit shipment form
    ?>
    <div class="admin-container">
        <div class="admin-sidebar">
            <!-- Sidebar content same as before -->
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Edit Data Pengiriman #<?php echo $shipment['id']; ?></h1>
                <a href="shipments.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="shipment-form">
                <form method="post">
                    <div class="form-section">
                        <h3>Informasi Pesanan</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>ID Pesanan</label>
                                <p class="form-control-static">#<?php echo $shipment['order_id']; ?></p>
                            </div>
                            <div class="form-group">
                                <label>Tanggal Pesanan</label>
                                <p class="form-control-static"><?php echo date('d M Y', strtotime($shipment['order_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Informasi Pelanggan</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nama Pelanggan</label>
                                <p class="form-control-static"><?php echo htmlspecialchars($shipment['customer_name']); ?></p>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <p class="form-control-static"><?php echo htmlspecialchars($shipment['email']); ?></p>
                            </div>
                            <div class="form-group">
                                <label>Telepon</label>
                                <p class="form-control-static"><?php echo htmlspecialchars($shipment['phone']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3>Detail Pengiriman</h3>
                        <div class="form-group">
                            <label for="shipping_method">Jasa Pengiriman</label>
                            <select name="shipping_method" id="shipping_method" class="form-control" required>
                                <option value="">Pilih Jasa Pengiriman</option>
                                <option value="JNE" <?php echo $shipment['shipping_method'] === 'JNE' ? 'selected' : ''; ?>>JNE</option>
                                <option value="J&T" <?php echo $shipment['shipping_method'] === 'J&T' ? 'selected' : ''; ?>>J&T</option>
                                <option value="SiCepat" <?php echo $shipment['shipping_method'] === 'SiCepat' ? 'selected' : ''; ?>>SiCepat</option>
                                <option value="Ninja Express" <?php echo $shipment['shipping_method'] === 'Ninja Express' ? 'selected' : ''; ?>>Ninja Express</option>
                                <option value="POS Indonesia" <?php echo $shipment['shipping_method'] === 'POS Indonesia' ? 'selected' : ''; ?>>POS Indonesia</option>
                                <option value="Lainnya" <?php echo empty($shipment['shipping_method']) || !in_array($shipment['shipping_method'], ['JNE','J&T','SiCepat','Ninja Express','POS Indonesia']) ? 'selected' : ''; ?>>Lainnya</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tracking_number">Nomor Resi</label>
                            <input type="text" name="tracking_number" id="tracking_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($shipment['tracking_number']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status Pengiriman</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="preparing" <?php echo $shipment['status'] === 'preparing' ? 'selected' : ''; ?>>Diproses</option>
                                <option value="shipped" <?php echo $shipment['status'] === 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                                <option value="in_transit" <?php echo $shipment['status'] === 'in_transit' ? 'selected' : ''; ?>>Dalam Perjalanan</option>
                                <option value="delivered" <?php echo $shipment['status'] === 'delivered' ? 'selected' : ''; ?>>Terkirim</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="estimated_delivery">Estimasi Tiba</label>
                            <input type="date" name="estimated_delivery" id="estimated_delivery" class="form-control" 
                                   value="<?php echo $shipment['estimated_delivery'] ? date('Y-m-d', strtotime($shipment['estimated_delivery'])) : ''; ?>" 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Alamat Pengiriman</label>
                            <textarea name="address" id="address" class="form-control" rows="4" required><?php 
                                echo htmlspecialchars($shipment['address']); 
                            ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="shipments.php" class="btn btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
}

function showShipmentList() {
    global $pdo;
    
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    $page = max(1, $_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build base query
    $query = "SELECT s.*, o.order_date, u.name as customer_name 
              FROM shipments s
              JOIN orders o ON s.order_id = o.id
              JOIN users u ON o.user_id = u.id";
    
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "s.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(u.name LIKE ? OR s.tracking_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    // Add pagination - CAST to ensure integers
    $query .= " ORDER BY s.shipping_date DESC LIMIT :limit OFFSET :offset";
    
    try {
        $stmt = $pdo->prepare($query);
        
        // Bind parameters
        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param);
        }
        
        // Bind limit/offset as integers
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $shipments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count total shipments
        $countQuery = "SELECT COUNT(*) FROM shipments s 
                      JOIN orders o ON s.order_id = o.id 
                      JOIN users u ON o.user_id = u.id";
        
        if (!empty($where)) {
            $countQuery .= " WHERE " . implode(" AND ", $where);
        }
        
        $stmt = $pdo->prepare($countQuery);
        $stmt->execute($params);
        $totalShipments = $stmt->fetchColumn();
        $totalPages = ceil($totalShipments / $limit);
        
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan saat mengambil data pengiriman";
        $shipments = [];
        $totalPages = 1;
    }
    
    // Rest of your HTML display code remains the same...
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
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li class="active"><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Kelola Pengiriman</h1>
                <div class="header-actions">
                    <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Cetak</button>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <div class="admin-filters">
                <form method="get" action="shipments.php">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Cari nama pelanggan atau nomor resi..." 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn-small"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="form-group">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="preparing" <?php echo $status === 'preparing' ? 'selected' : ''; ?>>Diproses</option>
                            <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Dikirim</option>
                            <option value="in_transit" <?php echo $status === 'in_transit' ? 'selected' : ''; ?>>Dalam Perjalanan</option>
                            <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Terkirim</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Tanggal Pesanan</th>
                            <th>Jasa Kirim</th>
                            <th>No. Resi</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shipments)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data pengiriman</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($shipments as $shipment): ?>
                                <tr>
                                    <td>#<?php echo $shipment['id']; ?></td>
                                    <td><?php echo htmlspecialchars($shipment['customer_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($shipment['order_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($shipment['shipping_method']); ?></td>
                                    <td><?php echo $shipment['tracking_number'] ? htmlspecialchars($shipment['tracking_number']) : '-'; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $shipment['status']; ?>">
                                            <?php 
                                                $statusMap = [
                                                    'preparing' => 'Diproses',
                                                    'shipped' => 'Dikirim',
                                                    'in_transit' => 'Dalam Perjalanan',
                                                    'delivered' => 'Terkirim'
                                                ];
                                                echo $statusMap[$shipment['status']] ?? $shipment['status']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="shipments.php?action=edit&id=<?php echo $shipment['id']; ?>" class="btn-small">
                                            <i class="fas fa-edit"></i>
                                        </a>
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
                        <a href="shipments.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">&laquo; Sebelumnya</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="shipments.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="shipments.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">Selanjutnya &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

require_once __DIR__ . '/../includes/footer.php';
?>