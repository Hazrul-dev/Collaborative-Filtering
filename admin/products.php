<?php
$pageTitle = "Kelola Produk";
$isAdminPage = true;
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/product_functions.php';

// Periksa apakah user adalah admin
if (!isAdmin()) {
    header('Location: /login.php');
    exit();
}

$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;

// Tangani aksi yang berbeda
switch ($action) {
    case 'add':
        handleAddProduct();
        break;
    case 'edit':
        handleEditProduct($productId);
        break;
    case 'delete':
        handleDeleteProduct($productId);
        break;
    case 'list':
    default:
        showProductList();
        break;
}

function handleAddProduct() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category'];
        
        // Handle file upload
        $imageName = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageName = uploadProductImage($_FILES['image']);
        }
        
        try {
            $pdo->beginTransaction();
            
            // Insert produk utama
            $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $description, $price, $stock, $category, $imageName])) {
                $productId = $pdo->lastInsertId();
                
                // Simpan warna produk jika ada
                if (!empty($_POST['color_names']) && is_array($_POST['color_names'])) {
                    $stmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
                    
                    foreach ($_POST['color_names'] as $index => $colorName) {
                        $colorCode = $_POST['color_codes'][$index] ?? '#000000';
                        
                        // Pastikan nama warna tidak kosong
                        if (!empty(trim($colorName))) {
                            $stmt->execute([$productId, trim($colorName), $colorCode]);
                        }
                    }
                }
                
                $pdo->commit();
                $_SESSION['success'] = "Produk berhasil ditambahkan!";
                header('Location: products.php');
                exit();
            } else {
                $pdo->rollBack();
                $error = "Gagal menambahkan produk.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal menambahkan produk: " . $e->getMessage();
            error_log("Error adding product: " . $e->getMessage());
        }
    }
    
    showProductForm('Tambah Produk');
}

function handleEditProduct($productId) {
    global $pdo;
    
    $product = getProductById($productId);
    if (!$product) {
        $_SESSION['error'] = "Produk tidak ditemukan!";
        header('Location: products.php');
        exit();
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        $category = $_POST['category'];
        
        // Handle file upload
        $imageName = $product['image'];
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if ($imageName && file_exists("../assets/images/products/$imageName")) {
                unlink("../assets/images/products/$imageName");
            }
            $imageName = uploadProductImage($_FILES['image']);
        }
        
        try {
            $pdo->beginTransaction();
            
            // Update produk utama
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $description, $price, $stock, $category, $imageName, $productId]);
            
            // Hapus warna lama
            $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$productId]);
            
            // Proses warna baru
            if (!empty($_POST['color_names']) && is_array($_POST['color_names'])) {
                $colorStmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
                
                foreach ($_POST['color_names'] as $index => $colorName) {
                    $colorName = trim($colorName);
                    $colorCode = $_POST['color_codes'][$index] ?? '#000000';
                    
                    if (!empty($colorName)) {
                        $colorStmt->execute([$productId, $colorName, $colorCode]);
                    }
                }
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Produk berhasil diperbarui!";
            header('Location: products.php');
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Gagal memperbarui produk: " . $e->getMessage();
            error_log("Update product error: " . $e->getMessage());
        }
    }
    
    // Ambil warna produk untuk ditampilkan di form
    $product['colors'] = $pdo->query("SELECT * FROM product_colors WHERE product_id = $productId")
                            ->fetchAll(PDO::FETCH_ASSOC);
    
    showProductForm('Edit Produk', $product);
}

function handleDeleteProduct($productId) {
    global $pdo;
    
    $product = getProductById($productId);
    if (!$product) {
        $_SESSION['error'] = "Produk tidak ditemukan!";
    } else {
        try {
            $pdo->beginTransaction();
            
            // 1. Dapatkan semua color_id terkait produk ini
            $colorIds = $pdo->query("SELECT id FROM product_colors WHERE product_id = $productId")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($colorIds)) {
                // 2. Update order_items yang menggunakan color_id ini (set NULL atau hapus)
                // Pilihan 1: Set color_id menjadi NULL
                $pdo->prepare("UPDATE order_items SET color_id = NULL WHERE color_id IN (".implode(',', $colorIds).")")->execute();
                
                // ATAU Pilihan 2: Hapus order_items yang terkait
                // $pdo->prepare("DELETE FROM order_items WHERE color_id IN (".implode(',', $colorIds).")")->execute();
            }
            
            // 3. Hapus interaksi pengguna terkait produk ini
            $pdo->prepare("DELETE FROM user_product_interactions WHERE product_id = ?")->execute([$productId]);
            
            // 4. Hapus warna produk
            $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?")->execute([$productId]);
            
            // 5. Hapus gambar produk jika ada
            if ($product['image'] && file_exists("../assets/images/products/{$product['image']}")) {
                unlink("../assets/images/products/{$product['image']}");
            }
            
            // 6. Hapus produk
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            if ($stmt->execute([$productId])) {
                $pdo->commit();
                $_SESSION['success'] = "Produk berhasil dihapus!";
            } else {
                $pdo->rollBack();
                $_SESSION['error'] = "Gagal menghapus produk.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Gagal menghapus produk: " . $e->getMessage();
            error_log("Delete product error: " . $e->getMessage());
        }
    }
    
    header('Location: products.php');
    exit();
}

function showProductList() {
    global $pdo;
    
    $search = $_GET['search'] ?? null;
    $category = $_GET['category'] ?? null;
    
    $query = "SELECT * FROM products";
    $params = [];
    
    if ($search) {
        $query .= " WHERE name LIKE ?";
        $params[] = "%$search%";
    } elseif ($category) {
        $query .= " WHERE category = ?";
        $params[] = $category;
    }
    
    $query .= " ORDER BY name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
                <li class="active"><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1>Kelola Produk</h1>
                <a href="products.php?action=add" class="btn"><i class="fas fa-plus"></i> Tambah Produk</a>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <div class="admin-filters">
                <form method="get" action="products.php">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button type="submit" class="btn-small"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="form-group">
                        <select name="category" onchange="this.form.submit()">
                            <option value="">Semua Kategori</option>
                            <option value="Pashmina" <?php echo ($category === 'Pashmina') ? 'selected' : ''; ?>>Pashmina</option>
                            <option value="Segi Empat" <?php echo ($category === 'Segi Empat') ? 'selected' : ''; ?>>Segi Empat</option>
                            <option value="Instan" <?php echo ($category === 'Instan') ? 'selected' : ''; ?>>Instan</option>
                            <option value="Sport" <?php echo ($category === 'Sport') ? 'selected' : ''; ?>>Sport</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Nama Produk</th>
                            <th>Harga</th>
                            <th>Warna</th>
                            <th>Stok</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada produk ditemukan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumbnail">
                                        <?php else: ?>
                                            <div class="no-image">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php 
                                        $colors = $pdo->query("SELECT color_name FROM product_colors WHERE product_id = " . $product['id'])->fetchAll();
                                        echo implode(', ', array_column($colors, 'color_name'));
                                        ?>
                                    </td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td class="actions">
                                        <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn-small btn-edit"><i class="fas fa-edit"></i></a>
                                        <a href="products.php?action=delete&id=<?php echo $product['id']; ?>" class="btn-small btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="admin-actions">
                <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Cetak Daftar Produk</button>
                <button onclick="generatePDF()" class="btn"><i class="fas fa-file-pdf"></i> Export PDF</button>
            </div>
        </div>
    </div>
    
    <script>
        function generatePDF() {
            // Menggunakan jsPDF untuk membuat PDF
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Judul
            doc.setFontSize(18);
            doc.text('Daftar Produk Toko Izra Fashion', 105, 15, { align: 'center' });
            
            // Tanggal
            doc.setFontSize(12);
            doc.text(`Dibuat pada: ${new Date().toLocaleDateString()}`, 105, 25, { align: 'center' });
            
            // Data tabel
            const products = <?php echo json_encode($products); ?>;
            
            let y = 35;
            doc.setFontSize(10);
            
            // Header tabel
            doc.setFillColor(200, 200, 200);
            doc.rect(10, y, 190, 10, 'F');
            doc.text('No', 15, y + 7);
            doc.text('Nama Produk', 30, y + 7);
            doc.text('Harga', 100, y + 7);
            doc.text('Warna', 130, y + 7);
            doc.text('Stok', 150, y + 7);
            doc.text('Kategori', 170, y + 7);
            y += 10;
            
            // Isi tabel
            products.forEach((product, index) => {
                const colors = <?php echo json_encode($pdo->query("SELECT color_name FROM product_colors WHERE product_id = " . $product['id'])->fetchAll(PDO::FETCH_COLUMN)); ?>;
                
                doc.text((index + 1).toString(), 15, y + 7);
                doc.text(product.name, 30, y + 7);
                doc.text(`Rp ${product.price.toLocaleString('id-ID')}`, 100, y + 7);
                doc.text(colors.join(', '), 130, y + 7);
                doc.text(product.stock.toString(), 150, y + 7);
                doc.text(product.category, 170, y + 7);
                y += 10;
                
                // Tambah halaman baru jika mencapai batas bawah
                if (y > 280) {
                    doc.addPage();
                    y = 20;
                }
            });
            
            // Simpan PDF
            doc.save('daftar_produk_izra_fashion.pdf');
        }
    </script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <?php
}

function showProductForm($title, $product = null) {
    global $error, $pdo;
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
                <li class="active"><a href="products.php"><i class="fas fa-box"></i> Kelola Produk</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Kelola Pesanan</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Kelola Pengguna</a></li>
                <li><a href="recommendations.php"><i class="fas fa-star"></i> Rekomendasi Produk</a></li>
                <li><a href="preorders.php"><i class="fas fa-calendar-check"></i> Pre Order</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
            </ul>
        </div>
        
        <div class="admin-content">
            <div class="admin-header">
                <h1><?php echo $title; ?></h1>
                <a href="products.php" class="btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="admin-form">
                <div class="form-group">
                    <label for="name">Nama Produk:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi:</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Warna Produk:</label>
                    <div id="color-container">
                        <?php if (!empty($product['colors'])): ?>
                            <?php foreach ($product['colors'] as $color): ?>
                                <div class="color-item">
                                    <input type="text" name="color_names[]" value="<?= htmlspecialchars($color['color_name']) ?>" placeholder="Nama Warna" required>
                                    <input type="color" name="color_codes[]" value="<?= htmlspecialchars($color['color_code']) ?>">
                                    <button type="button" class="btn-small btn-delete remove-color">Hapus</button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="add-color" class="btn-small">+ Tambah Warna</button>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Harga (Rp):</label>
                        <input type="number" id="price" name="price" min="0" step="1000" value="<?php echo $product['price'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="stock">Stok:</label>
                        <input type="number" id="stock" name="stock" min="0" value="<?php echo $product['stock'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Kategori:</label>
                        <select id="category" name="category" required>
                            <option value="Pashmina" <?php echo (isset($product['category']) && $product['category'] === 'Pashmina' ? 'selected' : ''); ?>>Pashmina</option>
                            <option value="Segi Empat" <?php echo (isset($product['category']) && $product['category'] === 'Segi Empat' ? 'selected' : ''); ?>>Segi Empat</option>
                            <option value="Instan" <?php echo (isset($product['category']) && $product['category'] === 'Instan' ? 'selected' : ''); ?>>Instan</option>
                            <option value="Sport" <?php echo (isset($product['category']) && $product['category'] === 'Sport' ? 'selected' : ''); ?>>Sport</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Gambar Produk:</label>
                    <input type="file" id="image" name="image" accept="image/*">
                    
                    <?php if (isset($product['image']) && $product['image']): ?>
                        <div class="current-image">
                            <p>Gambar saat ini:</p>
                            <img src="../assets/images/products/<?php echo htmlspecialchars($product['image']); ?>" alt="Current Image" style="max-width: 200px;">
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn"><?php echo $product ? 'Perbarui' : 'Simpan'; ?></button>
                    <a href="products.php" class="btn btn-cancel">Batal</a>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tambah warna baru
            document.getElementById('add-color')?.addEventListener('click', function() {
                const container = document.getElementById('color-container');
                const div = document.createElement('div');
                div.className = 'color-item';
                div.innerHTML = `
                    <input type="text" name="color_names[]" placeholder="Nama Warna" required>
                    <input type="color" name="color_codes[]" value="#000000">
                    <button type="button" class="btn-small btn-delete remove-color">Hapus</button>
                `;
                container.appendChild(div);
            });

            // Hapus warna
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-color')) {
                    e.target.closest('.color-item').remove();
                }
            });
        });
    </script>
    <?php
}

function uploadProductImage($file) {
    $targetDir = __DIR__ . '/../assets/images/products/';
    
    // Pastikan direktori ada
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0755, true); // Buat direktori jika tidak ada
    }

    // Validasi file gambar
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        throw new Exception("File bukan gambar.");
    }

    // Validasi ukuran file (max 2MB)
    if ($file['size'] > 2000000) {
        throw new Exception("Ukuran gambar terlalu besar (maks 2MB).");
    }

    // Validasi ekstensi file
    $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        throw new Exception("Hanya format JPG, JPEG, PNG, GIF & WEBP yang diperbolehkan.");
    }

    // Generate nama file unik
    $newFileName = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFileName;

    // Coba upload file
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Debugging: cek error yang terjadi
        error_log("Upload error: " . print_r(error_get_last(), true));
        throw new Exception("Gagal mengunggah gambar. Pastikan folder tujuan ada dan memiliki izin yang tepat.");
    }

    return $newFileName;
}
?>