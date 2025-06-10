<?php
$pageTitle = "Laporan";
$isAdminPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/report_functions.php';

// Periksa apakah user adalah admin
if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$action = $_GET['action'] ?? 'dashboard';
$reportType = $_GET['type'] ?? 'sales';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Tangani aksi yang berbeda
switch ($action) {
    case 'generate':
        generateReport($reportType, $startDate, $endDate);
        break;
    case 'print':
        printReport($reportType, $startDate, $endDate);
        break;
    case 'dashboard':
    default:
        showReportDashboard($reportType, $startDate, $endDate);
        break;
}

function generateReport($reportType, $startDate, $endDate) {
    global $pdo;
    
    switch ($reportType) {
        case 'sales':
            $data = getSalesReportData($startDate, $endDate);
            $title = "Laporan Penjualan $startDate s/d $endDate";
            $filename = "laporan_penjualan_$startDate-$endDate.pdf";
            break;
        case 'products':
            $data = getProductsReportData($startDate, $endDate);
            $title = "Laporan Produk Terlaris $startDate s/d $endDate";
            $filename = "laporan_produk_$startDate-$endDate.pdf";
            break;
        case 'customers':
            $data = getCustomersReportData($startDate, $endDate);
            $title = "Laporan Pelanggan $startDate s/d $endDate";
            $filename = "laporan_pelanggan_$startDate-$endDate.pdf";
            break;
        default:
            $_SESSION['error'] = "Jenis laporan tidak valid!";
            header('Location: reports.php');
            exit();
    }
    
    generateReportPDF($data, $title, $filename);
    exit();
}

function printReport($reportType, $startDate, $endDate) {
    global $pdo;
    
    switch ($reportType) {
        case 'sales':
            $data = getSalesReportData($startDate, $endDate);
            $title = "Laporan Penjualan $startDate s/d $endDate";
            break;
        case 'products':
            $data = getProductsReportData($startDate, $endDate);
            $title = "Laporan Produk Terlaris $startDate s/d $endDate";
            break;
        case 'customers':
            $data = getCustomersReportData($startDate, $endDate);
            $title = "Laporan Pelanggan $startDate s/d $endDate";
            break;
        default:
            $_SESSION['error'] = "Jenis laporan tidak valid!";
            header('Location: reports.php');
            exit();
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            h1 { text-align: center; margin-bottom: 20px; }
            .report-info { text-align: center; margin-bottom: 30px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .footer { margin-top: 50px; text-align: right; }
            @page { size: A4; margin: 10mm; }
        </style>
    </head>
    <body>
        <h1><?php echo $title; ?></h1>
        <div class="report-info">
            <p>Periode: <?php echo date('d M Y', strtotime($startDate)); ?> - <?php echo date('d M Y', strtotime($endDate)); ?></p>
            <p>Dibuat pada: <?php echo date('d M Y H:i:s'); ?></p>
        </div>
        
        <?php if (!empty($data)): ?>
            <table>
                <thead>
                    <tr>
                        <?php foreach (array_keys($data[0]) as $header): ?>
                            <th><?php echo ucwords(str_replace('_', ' ', $header)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?php echo $cell; ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-center">Tidak ada data yang ditemukan untuk periode ini.</p>
        <?php endif; ?>
        
        <div class="footer">
            <p>Dibuat oleh: <?php echo $_SESSION['name']; ?></p>
            <p>Toko Izra Fashion</p>
        </div>
        
        <script>
            window.print();
            window.onafterprint = function() {
                window.close();
            };
        </script>
    </body>
    </html>
    <?php
    exit();
}

function showReportDashboard($reportType, $startDate, $endDate) {
    global $pdo;
    
    // Dapatkan data untuk dashboard
    $salesData = getSalesData($startDate, $endDate);
    $topProducts = getBestSellingProducts(5, $startDate, $endDate);
    $topCustomers = getTopCustomers(5, $startDate, $endDate);
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
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li class="active"><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Laporan</h1>
                <div class="header-actions">
                    <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Cetak</button>
                </div>
            </div>
            
            <div class="report-filters">
                <form method="get" action="reports.php">
                    <input type="hidden" name="action" value="dashboard">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">Jenis Laporan:</label>
                            <select name="type" id="type" onchange="this.form.submit()">
                                <option value="sales" <?php echo $reportType === 'sales' ? 'selected' : ''; ?>>Penjualan</option>
                                <option value="products" <?php echo $reportType === 'products' ? 'selected' : ''; ?>>Produk</option>
                                <option value="customers" <?php echo $reportType === 'customers' ? 'selected' : ''; ?>>Pelanggan</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date">Dari Tanggal:</label>
                            <input type="date" name="start_date" id="start_date" value="<?php echo $startDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">Sampai Tanggal:</label>
                            <input type="date" name="end_date" id="end_date" value="<?php echo $endDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn">Filter</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="report-summary">
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Penjualan</h3>
                        <p>Rp <?php echo number_format($salesData['total_sales'], 0, ',', '.'); ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Pesanan</h3>
                        <p><?php echo $salesData['order_count']; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Produk Terjual</h3>
                        <p><?php echo $salesData['product_sold']; ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>Pelanggan</h3>
                        <p><?php echo $salesData['customer_count']; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="report-charts">
                <div class="chart-container">
                    <h2>Grafik Penjualan</h2>
                    <canvas id="salesChart" height="300"></canvas>
                </div>
                
                <div class="chart-container">
                    <h2>Grafik Kategori Produk</h2>
                    <canvas id="categoryChart" height="300"></canvas>
                </div>
            </div>
            
            <div class="report-tables">
                <div class="table-container">
                    <h2>5 Produk Terlaris</h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Terjual</th>
                                <th>Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo $product['sold_quantity']; ?></td>
                                    <td>Rp <?php echo number_format($product['revenue'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-container">
                    <h2>5 Pelanggan Terbaik</h2>
                    <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Pelanggan</th>
                            <th>Total Pesanan</th>
                            <th>Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topCustomers as $customer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                <td><?php echo $customer['order_count']; ?></td>
                                <td>Rp <?php echo number_format($customer['total_spent'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="report-actions">
            <a href="reports.php?action=generate&type=sales&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn"><i class="fas fa-file-pdf"></i> Export PDF Penjualan</a>
            <a href="reports.php?action=generate&type=products&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn"><i class="fas fa-file-pdf"></i> Export PDF Produk</a>
            <a href="reports.php?action=generate&type=customers&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn"><i class="fas fa-file-pdf"></i> Export PDF Pelanggan</a>
            
            <a href="reports.php?action=print&type=sales&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn" target="_blank"><i class="fas fa-print"></i> Cetak Laporan Penjualan</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Grafik Penjualan
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($salesData['daily_sales'], 'date')); ?>,
                datasets: [{
                    label: 'Penjualan Harian',
                    data: <?php echo json_encode(array_column($salesData['daily_sales'], 'total')); ?>,
                    backgroundColor: 'rgba(106, 27, 154, 0.1)',
                    borderColor: 'rgba(106, 27, 154, 1)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
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
        
        // Grafik Kategori Produk
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($salesData['category_sales'], 'category')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($salesData['category_sales'], 'total')); ?>,
                    backgroundColor: [
                        'rgba(106, 27, 154, 0.7)',
                        'rgba(156, 39, 176, 0.7)',
                        'rgba(123, 31, 162, 0.7)',
                        'rgba(142, 36, 170, 0.7)',
                        'rgba(74, 20, 140, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: Rp ${value.toLocaleString('id-ID')} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php
}

function getSalesReportData($startDate, $endDate) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE(o.order_date) as date,
            COUNT(*) as order_count,
            SUM(o.total) as total_sales,
            GROUP_CONCAT(DISTINCT u.name SEPARATOR ', ') as customers
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY DATE(o.order_date)
        ORDER BY DATE(o.order_date)
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductsReportData($startDate, $endDate) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            p.name as product_name,
            p.category,
            SUM(oi.quantity) as quantity_sold,
            SUM(oi.quantity * oi.price) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY oi.product_id
        ORDER BY quantity_sold DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCustomersReportData($startDate, $endDate) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            u.name as customer_name,
            u.email,
            COUNT(*) as order_count,
            SUM(o.total) as total_spent
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY o.user_id
        ORDER BY total_spent DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSalesData($startDate, $endDate) {
    global $pdo;
    
    // Total penjualan dan jumlah pesanan
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as order_count,
            SUM(total) as total_sales,
            COUNT(DISTINCT user_id) as customer_count
        FROM orders
        WHERE order_date BETWEEN ? AND ?
        AND status != 'cancelled'
    ");
    $stmt->execute([$startDate, $endDate]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jumlah produk terjual
    $stmt = $pdo->prepare("
        SELECT SUM(quantity) as product_sold
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.status != 'cancelled'
    ");
    $stmt->execute([$startDate, $endDate]);
    $productSold = $stmt->fetchColumn();
    
    // Penjualan harian
    $stmt = $pdo->prepare("
        SELECT 
            DATE(order_date) as date,
            SUM(total) as total
        FROM orders
        WHERE order_date BETWEEN ? AND ?
        AND status != 'cancelled'
        GROUP BY DATE(order_date)
        ORDER BY DATE(order_date)
    ");
    $stmt->execute([$startDate, $endDate]);
    $dailySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Penjualan per kategori
    $stmt = $pdo->prepare("
        SELECT 
            p.category,
            SUM(oi.quantity * oi.price) as total
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY p.category
        ORDER BY total DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    $categorySales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'order_count' => $result['order_count'] ?? 0,
        'total_sales' => $result['total_sales'] ?? 0,
        'customer_count' => $result['customer_count'] ?? 0,
        'product_sold' => $productSold ?? 0,
        'daily_sales' => $dailySales,
        'category_sales' => $categorySales
    ];
}

function getBestSellingProducts($limit, $startDate, $endDate) {
    global $pdo;
    
    $limit = (int)$limit;
    $query = "
        SELECT 
            p.name,
            SUM(oi.quantity) as sold_quantity,
            SUM(oi.quantity * oi.price) as revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY oi.product_id
        ORDER BY sold_quantity DESC
        LIMIT " . $limit;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTopCustomers($limit, $startDate, $endDate) {
    global $pdo;
    
    $limit = (int)$limit; // Pastikan limit adalah integer
    $query = "
        SELECT 
            u.name,
            COUNT(*) as order_count,
            SUM(o.total) as total_spent
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.order_date BETWEEN ? AND ?
        AND o.status != 'cancelled'
        GROUP BY o.user_id
        ORDER BY total_spent DESC
        LIMIT " . $limit;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>