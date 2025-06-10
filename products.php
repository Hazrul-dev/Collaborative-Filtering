<?php
$pageTitle = "Produk";
require_once 'includes/header.php';
require_once 'functions/auth_functions.php';
require_once 'functions/product_functions.php';
require_once 'functions/interaction_functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$action = $_GET['action'] ?? 'list';

if ($action === 'detail' && isset($_GET['id'])) {
    // Show product details
    $productId = $_GET['id'];
    $product = getProductById($productId);
    
    if (!$product) {
        header('Location: products.php');
        exit();
    }
    
    // Record user interaction
    if (function_exists('recordUserInteraction')) {
        recordUserInteraction($_SESSION['user_id'], $productId, 'view');
    } else {
        error_log("recordUserInteraction function not found");
    }
    
    // Get product colors
    $colors = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM product_colors WHERE product_id = ?");
        $stmt->execute([$productId]);
        $colors = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching product colors: " . $e->getMessage());
    }
    ?>
    
    <div class="product-detail">
        <div class="product-images">
            <img src="assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" 
                 alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>">
        </div>
        
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name'] ?? 'No Name'); ?></h1>
            <p class="price">Rp <?php echo isset($product['price']) ? number_format($product['price'], 0, ',', '.') : '0'; ?></p>
            
            <div class="product-meta">
                <span>Kategori: <?php echo htmlspecialchars($product['category'] ?? 'Unknown'); ?></span>
                <span>Stok: <?php echo $product['stock'] ?? '0'; ?></span>
            </div>
            
            <div class="product-description">
                <h3>Deskripsi Produk</h3>
                <p><?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?></p>
            </div>
            
            <form action="cart.php" method="post" class="add-to-cart-form">
                <input type="hidden" name="product_id" value="<?php echo $product['id'] ?? ''; ?>">
                
                <?php if (!empty($colors)): ?>
                <div class="form-group">
                    <label for="color">Pilih Warna:</label>
                    <select name="color_id" id="color" required>
                        <?php foreach ($colors as $color): ?>
                            <option value="<?= $color['id'] ?>">
                                <?= htmlspecialchars($color['color_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="quantity-selector">
                    <label for="quantity">Jumlah:</label>
                    <input type="number" id="quantity" name="quantity" min="1" 
                           max="<?php echo $product['stock'] ?? 1; ?>" value="1" required>
                </div>
                <button type="submit" class="btn">Tambah ke Keranjang</button>
            </form>
            
            <!-- Pre Order Button -->
            <a href="create_preorder.php?product_id=<?php echo $product['id']; ?>" class="btn btn-preorder">
                <i class="fas fa-calendar-check"></i> Pre Order
            </a>
        </div>
    </div>
    
    <?php
} else {
    // Show product list
    $products = $category ? getProductsByCategory($category) : ($search ? searchProducts($search) : getAllProducts());
    ?>
    
    <div class="products-page">
        <div class="sidebar">
            <h3>Kategori</h3>
            <ul>
                <li><a href="products.php">Semua Produk</a></li>
                <li><a href="products.php?category=Pashmina">Pashmina</a></li>
                <li><a href="products.php?category=Segi Empat">Segi Empat</a></li>
                <li><a href="products.php?category=Instan">Instan</a></li>
                <li><a href="products.php?category=Sport">Sport</a></li>
            </ul>
        </div>
        
        <div class="products-list">
            <div class="search-bar">
                <form action="products.php" method="get">
                    <input type="text" name="search" placeholder="Cari produk..." 
                           value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="assets/images/products/<?php echo htmlspecialchars($product['image'] ?? 'default.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>">
                        <h4><?php echo htmlspecialchars($product['name'] ?? 'No Name'); ?></h4>
                        <p class="price">Rp <?php echo isset($product['price']) ? number_format($product['price'], 0, ',', '.') : '0'; ?></p>
                        <a href="products.php?action=detail&id=<?php echo $product['id'] ?? ''; ?>" class="btn">Lihat Detail</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

require_once 'includes/footer.php';
?>