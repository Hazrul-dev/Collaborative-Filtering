<?php
$pageTitle = "Keranjang Belanja";
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/product_functions.php';
require_once 'functions/order_functions.php';
require_once __DIR__ . '/functions/interaction_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Tambah item ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    $colorId = $_POST['color_id'] ?? null;
    
    $product = getProductById($productId);
    if (!$product || $product['stock'] < $quantity) {
        $_SESSION['error'] = "Produk tidak tersedia atau stok tidak mencukupi";
        header('Location: products.php');
        exit();
    }
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Buat key unik berdasarkan product_id dan color_id
    $cartKey = $productId . '_' . ($colorId ?? '0');
    
    if (isset($_SESSION['cart'][$cartKey])) {
        $_SESSION['cart'][$cartKey]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cartKey] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'color_id' => $colorId
        ];
    }
    
    // Record interaction
    if (function_exists('recordUserInteraction')) {
        recordUserInteraction($_SESSION['user_id'], $productId, 'cart', $quantity);
    }
    
    $_SESSION['success'] = "Produk berhasil ditambahkan ke keranjang";
    header('Location: cart.php');
    exit();
}

// Update keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $cartKey => $quantity) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$cartKey]);
        } else {
            $_SESSION['cart'][$cartKey]['quantity'] = $quantity;
        }
    }
    header('Location: cart.php');
    exit();
}

// Hapus item dari keranjang
if (isset($_GET['action']) && $_GET['action'] === 'remove' && isset($_GET['key'])) {
    $cartKey = $_GET['key'];
    if (isset($_SESSION['cart'][$cartKey])) {
        unset($_SESSION['cart'][$cartKey]);
        $_SESSION['success'] = "Produk berhasil dihapus dari keranjang";
    }
    header('Location: cart.php');
    exit();
}

// Proses checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $_SESSION['error'] = "Keranjang belanja kosong";
        header('Location: cart.php');
        exit();
    }
    
    try {
        // Hitung total
        $total = 0;
        $items = [];
        
        foreach ($_SESSION['cart'] as $cartKey => $cartItem) {
            $product = getProductById($cartItem['product_id']);
            if (!$product || $product['stock'] < $cartItem['quantity']) {
                throw new Exception("Produk {$product['name']} stok tidak mencukupi");
            }
            
            $items[] = [
                'product_id' => $cartItem['product_id'],
                'quantity' => $cartItem['quantity'],
                'price' => $product['price'],
                'color_id' => $cartItem['color_id'] ?? null
            ];
            
            $total += $product['price'] * $cartItem['quantity'];
        }
        
        // Buat pesanan
        $orderId = createOrder($_SESSION['user_id'], $items, $total);
        
        // Kosongkan keranjang
        unset($_SESSION['cart']);
        
        $_SESSION['success'] = "Pesanan berhasil dibuat! Silakan lakukan pembayaran.";
        header("Location: payment.php?order_id=$orderId");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: cart.php');
        exit();
    }
}

// Dapatkan detail produk di keranjang
$cartItems = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cartKey => $cartItem) {
        $product = getProductById($cartItem['product_id']);
        if ($product) {
            $color = null;
            if (!empty($cartItem['color_id'])) {
                $color = getProductColorById($cartItem['color_id']);
            }
            
            $subtotal = $product['price'] * $cartItem['quantity'];
            $total += $subtotal;
            
            $cartItems[] = [
                'key' => $cartKey,
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $cartItem['quantity'],
                'subtotal' => $subtotal,
                'image' => $product['image'],
                'stock' => $product['stock'],
                'color' => $color
            ];
        }
    }
}

// Fungsi helper untuk mendapatkan warna produk
function getProductColorById($colorId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_colors WHERE id = ?");
    $stmt->execute([$colorId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="cart-container">
    <h1>Keranjang Belanja</h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <p>Keranjang belanja Anda kosong</p>
            <a href="products.php" class="btn">Lanjutkan Belanja</a>
        </div>
    <?php else: ?>
        <form action="cart.php" method="post">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Warna</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                        <tr>
                            <td class="product-info">
                                <img src="assets/images/products/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <div>
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p>Stok: <?php echo $item['stock']; ?></p>
                                </div>
                            </td>
                            <td class="color">
                                <?php if ($item['color']): ?>
                                    <span class="color-badge" style="background-color: <?= $item['color']['color_code'] ?>">
                                        <?= $item['color']['color_name'] ?>
                                    </span>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                            <td class="quantity">
                                <input type="number" name="quantities[<?php echo $item['key']; ?>]" 
                                       value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                            </td>
                            <td class="subtotal">Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                            <td class="actions">
                                <a href="cart.php?action=remove&key=<?php echo $item['key']; ?>" class="btn-small btn-delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Total:</strong></td>
                        <td colspan="2" class="total">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="cart-actions">
                <a href="products.php" class="btn btn-cancel">Lanjutkan Belanja</a>
                <button type="submit" name="update_cart" class="btn">Perbarui Keranjang</button>
                <button type="submit" name="checkout" class="btn btn-checkout">Checkout</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>