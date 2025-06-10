<?php
$pageTitle = "Pre Order";
$isAdminPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/order_functions.php';
require_once __DIR__ . '/../functions/product_functions.php';

// Periksa apakah user adalah admin
if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$preOrderId = $_GET['id'] ?? null;

// Tangani aksi yang berbeda
switch ($action) {
    case 'detail':
        showPreOrderDetail($preOrderId);
        break;
    case 'update-status':
        updatePreOrderStatus($preOrderId);
        break;
    case 'list':
    default:
        showPreOrderList();
        break;
}

function showPreOrderDetail($preOrderId) {
    global $pdo;
    
    $preOrder = getPreOrderById($preOrderId);
    if (!$preOrder) {
        $_SESSION['error'] = "Pre Order tidak ditemukan!";
        header('Location: preorders.php');
        exit();
    }
    
    $product = getProductById($preOrder['product_id']);
    $customer = getUserById($preOrder['user_id']);
    ?>
    <style>
    .product-image img {
        max-width: 100%;
        height: auto;
        max-height: 200px;
        object-fit: contain;
        display: block;
        margin: 0 auto;
        border-radius: 8px;
    }
    </style>
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
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li class="active"><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Detail Pre Order #<?php echo $preOrder['id']; ?></h1>
                <a href="preorders.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="preorder-detail">
                <div class="preorder-section">
                    <h2>Informasi Pre Order</h2>
                    <div class="preorder-info-grid">
                        <div>
                            <p><strong>ID Pre Order:</strong> #<?php echo $preOrder['id']; ?></p>
                            <p><strong>Tanggal Pre Order:</strong> <?php echo date('d M Y H:i', strtotime($preOrder['created_at'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge <?php echo $preOrder['status']; ?>">
                                    <?php 
                                        $statusMap = [
                                            'pending' => 'Menunggu Konfirmasi',
                                            'confirmed' => 'Dikonfirmasi',
                                            'cancelled' => 'Dibatalkan'
                                        ];
                                        echo $statusMap[$preOrder['status']] ?? $preOrder['status']; 
                                    ?>
                                </span>
                            </p>
                        </div>
                        <div>
                            <p><strong>Jumlah:</strong> <?php echo $preOrder['quantity']; ?></p>
                            <p><strong>Tanggal Pengiriman Estimasi:</strong> <?php echo $preOrder['expected_date'] ? date('d M Y', strtotime($preOrder['expected_date'])) : '-'; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="preorder-section">
                    <h2>Informasi Produk</h2>
                    <div class="product-info">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-details">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                            <p><strong>Kategori:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                            <p><strong>Stok Tersedia:</strong> <?php echo $product['stock']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="preorder-section">
                    <h2>Informasi Pelanggan</h2>
                    <div class="customer-info-grid">
                        <div>
                            <p><strong>Nama:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                            <p><strong>Telepon:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                        </div>
                        <div>
                            <p><strong>Alamat:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($customer['address'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="preorder-actions">
                    <form action="preorders.php?action=update-status&id=<?php echo $preOrder['id']; ?>" method="post" class="preorder-status-form">
                        <div class="form-group">
                            <label for="status">Ubah Status Pre Order:</label>
                            <select name="status" id="status" required>
                                <option value="pending" <?php echo $preOrder['status'] === 'pending' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                                <option value="confirmed" <?php echo $preOrder['status'] === 'confirmed' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                                <option value="cancelled" <?php echo $preOrder['status'] === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="expected-date-group">
                            <label for="expected_date">Tanggal Pengiriman Estimasi:</label>
                            <input type="date" name="expected_date" id="expected_date" value="<?php echo $preOrder['expected_date'] ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <button type="submit" class="btn">Perbarui Status</button>
                    </form>
                    
                    <?php if ($preOrder['status'] === 'confirmed'): ?>
                        <div class="additional-actions">
                            <a href="orders.php?action=create-from-preorder&id=<?php echo $preOrder['id']; ?>" class="btn"><i class="fas fa-shopping-bag"></i> Buat Pesanan</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tampilkan/menyembunyikan tanggal estimasi berdasarkan status
        document.getElementById('status').addEventListener('change', function() {
            const dateGroup = document.getElementById('expected-date-group');
            if (this.value === 'confirmed') {
                dateGroup.style.display = 'block';
            } else {
                dateGroup.style.display = 'none';
            }
        });
    </script>
    <?php
}

function updatePreOrderStatus($preOrderId) {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: preorders.php');
        exit();
    }
    
    $status = $_POST['status'];
    $expectedDate = $status === 'confirmed' ? $_POST['expected_date'] : null;
    
    try {
        $pdo->beginTransaction();
        
        // Update status pre order
        $stmt = $pdo->prepare("UPDATE pre_orders SET status = ?, expected_date = ? WHERE id = ?");
        $stmt->execute([$status, $expectedDate, $preOrderId]);
        
        $pdo->commit();
        $_SESSION['success'] = "Status pre order berhasil diperbarui!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal memperbarui status pre order: " . $e->getMessage();
    }
    
    header("Location: preorders.php?action=detail&id=$preOrderId");
    exit();
}

function showPreOrderList() {
    global $pdo;
    
    $status = $_GET['status'] ?? null;
    $search = $_GET['search'] ?? null;
    $page = max(1, $_GET['page'] ?? 1);
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Build base query
    $query = "SELECT po.*, p.name as product_name, u.name as customer_name 
              FROM pre_orders po
              JOIN products p ON po.product_id = p.id
              JOIN users u ON po.user_id = u.id";
    
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "po.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(u.name LIKE ? OR p.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    
    // Add pagination
    $query .= " ORDER BY po.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    try {
        $stmt = $pdo->prepare($query);
        
        // Bind parameters with proper types
        foreach ($params as $k => $param) {
            // Bind limit and offset as integers
            if ($k === count($params)-2 || $k === count($params)-1) {
                $stmt->bindValue($k+1, $param, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($k+1, $param);
            }
        }
        
        $stmt->execute();
        $preOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count total pre-orders
        $countQuery = "SELECT COUNT(*) FROM pre_orders po
                      JOIN products p ON po.product_id = p.id
                      JOIN users u ON po.user_id = u.id";
        
        if (!empty($where)) {
            $countQuery .= " WHERE " . implode(" AND ", $where);
        }
        
        $stmt = $pdo->prepare($countQuery);
        
        // For count query, we don't need limit/offset params
        $countParams = array_slice($params, 0, -2);
        $stmt->execute($countParams);
        
        $totalPreOrders = $stmt->fetchColumn();
        $totalPages = ceil($totalPreOrders / $limit);
        
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $_SESSION['error'] = "Terjadi kesalahan saat mengambil data pre order";
        $preOrders = [];
        $totalPages = 1;
    }
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
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li class="active"><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Kelola Pre Order</h1>
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
                <form method="get" action="preorders.php">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Cari nama pelanggan atau produk..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn-small"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="form-group">
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Menunggu Konfirmasi</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Dikonfirmasi</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
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
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($preOrders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada pre order ditemukan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($preOrders as $po): ?>
                                <tr>
                                    <td>#<?php echo $po['id']; ?></td>
                                    <td><?php echo htmlspecialchars($po['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($po['product_name']); ?></td>
                                    <td><?php echo $po['quantity']; ?></td>
                                    <td><?php echo date('d M Y', strtotime($po['created_at'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $po['status']; ?>">
                                            <?php 
                                                $statusMap = [
                                                    'pending' => 'Menunggu',
                                                    'confirmed' => 'Dikonfirmasi',
                                                    'cancelled' => 'Dibatalkan'
                                                ];
                                                echo $statusMap[$po['status']] ?? $po['status']; 
                                            ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="preorders.php?action=detail&id=<?php echo $po['id']; ?>" class="btn-small"><i class="fas fa-eye"></i></a>
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
                    <a href="preorders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">&laquo; Sebelumnya</a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="preorders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="preorders.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">Selanjutnya &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
?>