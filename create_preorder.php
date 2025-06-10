<?php
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/product_functions.php';
require_once 'functions/order_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$productId = $_GET['product_id'] ?? null;
$product = $productId ? getProductById($productId) : null;

if (!$product) {
    header('Location: products.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = $_POST['quantity'];
    $expectedDate = $_POST['expected_date'];
    
    // Validasi input
    if ($quantity < 1 || empty($expectedDate)) {
        $_SESSION['error'] = "Jumlah dan tanggal estimasi harus diisi";
    } else {
        if (createPreOrder($_SESSION['user_id'], $productId, $quantity, $expectedDate)) {
            $_SESSION['success'] = "Pre Order berhasil dibuat";
            header('Location: preorder.php');
            exit();
        } else {
            $_SESSION['error'] = "Gagal membuat Pre Order";
        }
    }
}
?>

<div class="preorder-form-container">
    <h2>Buat Pre Order</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="product-info">
            <img src="assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
            <p>Harga: Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
        </div>
        
        <div class="form-group">
            <label for="quantity">Jumlah:</label>
            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
        </div>
        
        <div class="form-group">
            <label for="expected_date">Estimasi Tersedia:</label>
            <input type="date" id="expected_date" name="expected_date" 
                   min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <button type="submit" class="btn">Konfirmasi Pre Order</button>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>