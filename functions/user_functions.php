<?php
require_once __DIR__ . '/../config/database.php';

function getAllUsers($role = null) {
    global $pdo;
    
    $query = "SELECT * FROM users";
    $params = [];
    
    if ($role) {
        $query .= " WHERE role = ?";
        $params[] = $role;
    }
    
    $query .= " ORDER BY name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchUsers($keyword) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE name LIKE ? OR username LIKE ? OR email LIKE ? ORDER BY name");
    $stmt->execute(["%$keyword%", "%$keyword%", "%$keyword%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateUser($id, $name, $email, $phone, $address, $role) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, role = ? WHERE id = ?");
    return $stmt->execute([$name, $email, $phone, $address, $role, $id]);
}

function deleteUser($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$id]);
}

function getUserOrders($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count,
               (SELECT SUM(quantity * price) FROM order_items WHERE order_id = o.id) as total
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserPreOrders($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT po.*, p.name as product_name, p.price
        FROM pre_orders po
        JOIN products p ON po.product_id = p.id
        WHERE po.user_id = ?
        ORDER BY po.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function recordUserInteraction($userId, $productId, $interactionType, $value = 1.0) {
    global $pdo;
    
    // Cek apakah interaksi sudah ada
    $stmt = $pdo->prepare("
        SELECT id, value 
        FROM user_product_interactions 
        WHERE user_id = ? AND product_id = ? AND interaction_type = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId, $productId, $interactionType]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update nilai interaksi jika sudah ada
        $newValue = $existing['value'] + $value;
        $stmt = $pdo->prepare("UPDATE user_product_interactions SET value = ? WHERE id = ?");
        return $stmt->execute([$newValue, $existing['id']]);
    } else {
        // Buat interaksi baru
        $stmt = $pdo->prepare("
            INSERT INTO user_product_interactions (user_id, product_id, interaction_type, value)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $productId, $interactionType, $value]);
    }
}
function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, email, phone, address, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("User Error: " . $e->getMessage());
        return false;
    }
}
function updateUserProfile($userId, $name, $email, $phone, $address) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, email = ?, phone = ?, address = ?
            WHERE id = ?
        ");
        return $stmt->execute([$name, $email, $phone, $address, $userId]);
    } catch (PDOException $e) {
        error_log("Update Profile Error: " . $e->getMessage());
        return false;
    }
}
?>