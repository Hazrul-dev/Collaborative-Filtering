<?php
$pageTitle = "Pembayaran";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/functions/auth_functions.php';
require_once __DIR__ . '/functions/order_functions.php';
require_once __DIR__ . '/functions/payment_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$orderId = $_GET['order_id'] ?? null;
$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $orderId = $_POST['order_id'];
        $method = $_POST['method'];
        $amount = $_POST['amount'];
        
        if (!function_exists('processPayment')) {
            throw new Exception("Payment processing function not available");
        }
        
        if (processPayment($orderId, $userId, $amount, $method)) {
            $_SESSION['success'] = "Pembayaran berhasil diproses!";
            header('Location: orders.php');
            exit();
        } else {
            throw new Exception("Gagal memproses pembayaran");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Get order details with proper error handling
$order = null;
if ($orderId) {
    if (!function_exists('getOrderDetails')) {
        error_log("getOrderDetails function missing");
        $_SESSION['error'] = "System error occurred";
        header('Location: orders.php');
        exit();
    }
    
    $order = getOrderDetails($orderId, $userId);
    if (!$order) {
        $_SESSION['error'] = "Pesanan tidak ditemukan atau tidak dapat diakses";
        header('Location: orders.php');
        exit();
    }
}
?>

<div class="payment-container">
    <div class="sidebar">
        <!-- Sama seperti dashboard -->
    </div>
    
    <div class="main-content">
        <h2>Pembayaran</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <?php if ($order): ?>
            <div class="payment-details">
                <h3>Detail Pesanan</h3>
                <div class="order-summary">
                    <p><strong>Nomor Pesanan:</strong> #INV-<?php echo $order['id']; ?></p>
                    <p><strong>Tanggal Pesanan:</strong> <?php echo date('d M Y H:i', strtotime($order['order_date'])); ?></p>
                    <p><strong>Total Pembayaran:</strong> Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></p>
                </div>
                
                <form method="POST" class="payment-form">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    <input type="hidden" name="amount" value="<?php echo $order['total']; ?>">
                    
                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="method" required>
                            <option value="">Pilih Metode</option>
                            <option value="bank_transfer">Transfer Bank</option>
                            <option value="e_wallet">E-Wallet</option>
                            <option value="cod">COD (Bayar di Tempat)</option>
                        </select>
                    </div>
                    
                    <div class="payment-instructions">
                        <h4>Instruksi Pembayaran:</h4>
                        <div id="bank-instructions" class="instructions">
                            <p>Silakan transfer ke rekening berikut:</p>
                            <p><strong>Bank:</strong> BCA</p>
                            <p><strong>Nomor Rekening:</strong> 1234567890</p>
                            <p><strong>Atas Nama:</strong> Toko Izra Fashion</p>
                            <p>Jumlah: Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></p>
                        </div>
                        
                        <div id="ewallet-instructions" class="instructions" style="display:none;">
                            <p>Silakan transfer ke e-wallet berikut:</p>
                            <p><strong>Provider:</strong> OVO/Gopay/DANA</p>
                            <p><strong>Nomor:</strong> 081234567890</p>
                            <p><strong>Atas Nama:</strong> Toko Izra Fashion</p>
                        </div>
                        
                        <div id="cod-instructions" class="instructions" style="display:none;">
                            <p>Anda akan membayar saat produk diterima.</p>
                            <p>Pastikan menyiapkan uang tunai sebesar Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></p>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Konfirmasi Pembayaran</button>
                </form>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-credit-card"></i>
                <p>Tidak ada pesanan yang perlu dibayar</p>
                <a href="orders.php" class="btn">Lihat Pesanan Saya</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    document.querySelector('select[name="method"]').addEventListener('change', function() {
        // Sembunyikan semua instruksi
        document.querySelectorAll('.instructions').forEach(el => {
            el.style.display = 'none';
        });
        
        // Tampilkan instruksi yang dipilih
        if (this.value === 'bank_transfer') {
            document.getElementById('bank-instructions').style.display = 'block';
        } else if (this.value === 'e_wallet') {
            document.getElementById('ewallet-instructions').style.display = 'block';
        } else if (this.value === 'cod') {
            document.getElementById('cod-instructions').style.display = 'block';
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>