<?php
$pageTitle = "Admin Dashboard";
$isAdminPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/product_functions.php';
require_once __DIR__ . '/../functions/user_functions.php';
require_once __DIR__ . '/../functions/report_functions.php';

if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

// Ambil data statistik
$totalProducts = getTotalProductsCount();
$totalOrders = getTotalOrdersCount();
$totalUsers = getTotalUsersCount();
$monthlyRevenue = getMonthlyRevenue();
$stockAlert = getLowStockProducts(5);
$recentOrders = getRecentOrders(5);
$salesData = getSalesDataLast30Days();
?>

<div class="admin-container">
    <div class="admin-sidebar">
    <div class="admin-profile">
        <img src="../assets/images/IF.jpg" alt="Admin Avatar">
        <h3><?php echo htmlspecialchars($_SESSION['name'] ?? $_SESSION['username'] ?? 'Admin'); ?></h3>
        <p><?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></p>
    </div>
        
        <ul class="admin-menu">
            <li class="active"><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
            <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
            <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
            <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
        </ul>
    </div>
    
    <div class="admin-content">
        <h1>Dashboard Admin</h1>
        
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Produk</h3>
                    <p><?php echo $totalProducts; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Pesanan</h3>
                    <p><?php echo $totalOrders; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Pengguna</h3>
                    <p><?php echo $totalUsers; ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-info">
                    <h3>Pendapatan Bulan Ini</h3>
                    <p>Rp <?php echo number_format($monthlyRevenue, 0, ',', '.'); ?></p>
                </div>
            </div>
        </div>
        
        <div class="admin-row">
            <div class="admin-chart">
                <h2>Penjualan 30 Hari Terakhir</h2>
                <canvas id="salesChart"></canvas>
            </div>
            
            <div class="admin-recent-orders">
                <h2>Pesanan Terbaru</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $order['status']; ?>">
                                        <?php 
                                            $statusMap = [
                                                'pending' => 'Menunggu',
                                                'processing' => 'Diproses',
                                                'shipped' => 'Dikirim',
                                                'delivered' => 'Selesai',
                                                'cancelled' => 'Batal'
                                            ];
                                            echo $statusMap[$order['status']] ?? $order['status']; 
                                        ?>
                                    </span>
                                </td>
                                <td><a href="orders.php?action=detail&id=<?php echo $order['id']; ?>" class="btn-small">Detail</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="admin-row">
            <div class="admin-stock-alert">
                <h2>Stok Produk Menipis</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Stok Tersedia</th>
                            <th>Harga</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockAlert as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td class="<?php echo $product['stock'] < 5 ? 'text-danger' : ''; ?>">
                                    <?php echo $product['stock']; ?>
                                </td>
                                <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                <td><a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn-small">Restok</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($stockAlert)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada produk dengan stok menipis</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="admin-latest-reports">
                <h2>Laporan Terakhir</h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID Laporan</th>
                            <th>Jenis</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (getLatestReports(5) as $report): ?>
                            <tr>
                                <td>#<?php echo $report['id']; ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($report['type'])); ?></td>
                                <td><?php echo date('d M Y', strtotime($report['created_at'])); ?></td>
                                <td>
                                    <a href="reports.php?action=view&id=<?php echo $report['id']; ?>" class="btn-small">Lihat</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Chart penjualan
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['date'] . "'"; }, $salesData)); ?>],
            datasets: [{
                label: 'Penjualan Harian',
                data: [<?php echo implode(',', array_map(function($item) { return $item['total_sales']; }, $salesData)); ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.raw.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
</script>