<?php
require_once __DIR__ . '/../config/database.php';

function getAllProducts() {
    global $pdo;
    return $pdo->query("SELECT * FROM products ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

function getProductsByCategory($category) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE category = ? ORDER BY name");
    $stmt->execute([$category]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchProducts($keyword) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY name");
    $stmt->execute(["%$keyword%", "%$keyword%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductById($productId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Product Error: " . $e->getMessage());
        return false;
    }
}

function addProduct($name, $description, $price, $stock, $category, $image) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO products (name, description, price, stock, category, image) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $description, $price, $stock, $category, $image]);
}

function updateProduct($id, $name, $description, $price, $stock, $category, $image = null) {
    global $pdo;
    
    if ($image) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ?, image = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $price, $stock, $category, $image, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock = ?, category = ? WHERE id = ?");
        return $stmt->execute([$name, $description, $price, $stock, $category, $id]);
    }
}

function deleteProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    return $stmt->execute([$id]);
}

function getProductImage($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

function getRecommendedProducts($userId, $limit = 4) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, COUNT(*) as interaction_count
            FROM user_product_interactions upi
            JOIN products p ON upi.product_id = p.id
            WHERE upi.user_id = ?
            GROUP BY p.id
            ORDER BY interaction_count DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Recommendation Error: " . $e->getMessage());
        return [];
    }
}

function getNewProducts($limit = 4) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getPopularProducts($limit = 4) {
    global $pdo;
    
    try {
        // Cek apakah tabel ada
        $tableExists = $pdo->query("SHOW TABLES LIKE 'user_product_interactions'")->rowCount() > 0;
        
        if (!$tableExists) {
            error_log("Tabel user_product_interactions tidak ditemukan, menggunakan fallback");
            return getFallbackProducts($limit);
        }
        
        $stmt = $pdo->prepare("
            SELECT p.*, COUNT(upi.product_id) as interaction_count
            FROM products p
            LEFT JOIN user_product_interactions upi ON p.id = upi.product_id
            GROUP BY p.id
            ORDER BY interaction_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getPopularProducts: " . $e->getMessage());
        return getFallbackProducts($limit);
    }
}
function getFallbackProducts($limit) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM products
            ORDER BY RAND()
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getFallbackProducts: " . $e->getMessage());
        return [];
    }
}

?>