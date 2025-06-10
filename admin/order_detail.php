<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Periksa apakah user adalah admin
if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

// Redirect jika bukan admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'owner')) {
    header("Location: ../login.php");
    exit();
}

// Dapatkan detail pesanan
if (isset($_GET['id'])) {
    $order_id = $_GET['id'];
    
    // Dapatkan informasi pesanan
    $stmt = $pdo->prepare("SELECT o.*, u.nama as pelanggan, u.email, u.no_telepon, u.alamat, 
                          p.metode_pembayaran, p.jumlah_pembayaran, p.status_pembayaran, p.bukti_pembayaran,
                          s.alamat_pengiriman, s.jasa_pengiriman, s.no_resi, s.status_pengiriman
                          FROM orders o
                          JOIN users u ON o.user_id = u.id
                          LEFT JOIN payments p ON o.id = p.order_id
                          LEFT JOIN shipments s ON o.id = s.order_id
                          WHERE o.id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header("Location: orders.php");
        exit();
    }
    
    // Dapatkan item pesanan
    $stmt = $pdo->prepare("SELECT od.*, p.nama_produk, p.gambar 
                          FROM order_details od
                          JOIN products p ON od.product_id = p.id
                          WHERE od.order_id = ?");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll();
} else {
    header("Location: orders.php");
    exit();
}

// Update status pengiriman
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_shipment'])) {
    $no_resi = $_POST['no_resi'];
    $status_pengiriman = $_POST['status_pengiriman'];
    
    if ($order['status_pengiriman'] === null) {
        // Buat entri pengiriman baru
        $stmt = $pdo->prepare("INSERT INTO shipments (order_id, alamat_pengiriman, jasa_pengiriman, no_resi, status_pengiriman, tanggal_pengiriman)
                              VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $order_id,
            $order['alamat'],
            $_POST['jasa_pengiriman'],
            $no_resi,
            $status_pengiriman
        ]);
    } else {
        // Update pengiriman yang ada
        $stmt = $pdo->prepare("UPDATE shipments 
                              SET no_resi = ?, status_pengiriman = ?, jasa_pengiriman = ?
                              WHERE order_id = ?");
        $stmt->execute([
            $no_resi,
            $status_pengiriman,
            $_POST['jasa_pengiriman'],
            $order_id
        ]);
    }
    
    // Update status pesanan jika dikirim
    if ($status_pengiriman == 'dikirim') {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'dikirim' WHERE id = ?");
        $stmt->execute([$order_id]);
    }
    
    // Log aktivitas
    log_activity($_SESSION['user_id'], "Mengupdate pengiriman pesanan #$order_id");
    
    header("Location: order_detail.php?id=$order_id&success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Toko Izra Fashion</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-menu">
            <h3>Menu</h3>
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="orders.php" class="active"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="pre_orders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <?php if ($_SESSION['role'] == 'owner'): ?>
                    <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <header>
            <div class="navbar">
                <div class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <div class="header-title">
                    <h1>Detail Pesanan #<?php echo $order_id; ?></h1>
                </div>
                <div class="user-actions">
                    <div class="dropdown">
                        <button class="btn btn-outline">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['nama']; ?>
                        </button>
                        <div class="dropdown-content">
                            <a href="../profile.php"><i class="fas fa-user"></i> Profil</a>
                            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="content">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Informasi pengiriman berhasil diperbarui!
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Item Pesanan</h2>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($order_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($item['gambar']): ?>
                                                            <img src="../assets/images/products/<?php echo $item['gambar']; ?>" alt="<?php echo $item['nama_produk']; ?>" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                                                        <?php endif; ?>
                                                        <div>
                                                            <h5><?php echo $item['nama_produk']; ?></h5>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>Rp <?php echo number_format($item['harga_satuan'], 0, ',', '.'); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>Rp <?php echo number_format($item['harga_satuan'] * $item['quantity'], 0, ',', '.'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-right"><strong>Total</strong></td>
                                            <td><strong>Rp <?php echo number_format($order['total_harga'], 0, ',', '.'); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Informasi Pesanan</h2>
                        </div>
                        <div class="card-body">
                            <div class="order-info">
                                <p><strong>ID Pesanan:</strong> #<?php echo $order_id; ?></p>
                                <p><strong>Tanggal Pesanan:</strong> <?php echo date('d M Y H:i', strtotime($order['tanggal_pesanan'])); ?></p>
                                <p><strong>Status Pesanan:</strong> 
                                    <span class="badge 
                                        <?php 
                                            switch($order['status']) {
                                                case 'pending': echo 'badge-warning'; break;
                                                case 'diproses': echo 'badge-primary'; break;
                                                case 'dikirim': echo 'badge-info'; break;
                                                case 'selesai': echo 'badge-success'; break;
                                                case 'dibatalkan': echo 'badge-danger'; break;
                                                default: echo 'badge-secondary';
                                            }
                                        ?>
                                    ">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h2 class="card-title">Informasi Pelanggan</h2>
                        </div>
                        <div class="card-body">
                            <div class="customer-info">
                                <p><strong>Nama:</strong> <?php echo $order['pelanggan']; ?></p>
                                <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
                                <p><strong>No. Telepon:</strong> <?php echo $order['no_telepon']; ?></p>
                                <p><strong>Alamat:</strong> <?php echo $order['alamat']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h2 class="card-title">Informasi Pembayaran</h2>
                        </div>
                        <div class="card-body">
                            <?php if ($order['metode_pembayaran']): ?>
                                <p><strong>Metode Pembayaran:</strong> <?php echo ucfirst($order['metode_pembayaran']); ?></p>
                                <p><strong>Status Pembayaran:</strong> 
                                    <span class="badge 
                                        <?php 
                                            switch($order['status_pembayaran']) {
                                                case 'pending': echo 'badge-warning'; break;
                                                case 'lunas': echo 'badge-success'; break;
                                                case 'gagal': echo 'badge-danger'; break;
                                                default: echo 'badge-secondary';
                                            }
                                        ?>
                                    ">
                                        <?php echo ucfirst($order['status_pembayaran']); ?>
                                    </span>
                                </p>
                                <?php if ($order['bukti_pembayaran']): ?>
                                    <p><strong>Bukti Pembayaran:</strong></p>
                                    <a href="../assets/images/payments/<?php echo $order['bukti_pembayaran']; ?>" target="_blank">
                                        <img src="../assets/images/payments/<?php echo $order['bukti_pembayaran']; ?>" style="max-width: 100%; height: auto;">
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <p>Belum ada informasi pembayaran</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h2 class="card-title">Informasi Pengiriman</h2>
                        </div>
                        <div class="card-body">
                            <form action="" method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="jasa_pengiriman">Jasa Pengiriman</label>
                                            <input type="text" class="form-control" id="jasa_pengiriman" name="jasa_pengiriman" 
                                                   value="<?php echo $order['jasa_pengiriman'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="no_resi">Nomor Resi</label>
                                            <input type="text" class="form-control" id="no_resi" name="no_resi" 
                                                   value="<?php echo $order['no_resi'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="status_pengiriman">Status Pengiriman</label>
                                    <select class="form-control" id="status_pengiriman" name="status_pengiriman" required>
                                        <option value="diproses" <?php echo ($order['status_pengiriman'] ?? '') == 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                        <option value="dikirim" <?php echo ($order['status_pengiriman'] ?? '') == 'dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                                        <option value="diterima" <?php echo ($order['status_pengiriman'] ?? '') == 'diterima' ? 'selected' : ''; ?>>Diterima</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Alamat Pengiriman</label>
                                    <textarea class="form-control" rows="3" readonly><?php echo $order['alamat']; ?></textarea>
                                </div>
                                <button type="submit" name="update_shipment" class="btn btn-primary">Simpan Perubahan</button>
                            </form>
                        </div>
                    </div>
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
                $('.sidebar').toggleClass('active');
            });

            // Cetak invoice
            $('#printInvoice').click(function() {
                window.print();
            });
        });
    </script>
</body>
</html>