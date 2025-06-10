<?php
require_once __DIR__ . '/../config/database.php';


function getOrderById($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPaymentByOrderId($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getShipmentByOrderId($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM shipments WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getOrderCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getActivePreOrderCount($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM pre_orders 
        WHERE user_id = :user_id 
        AND status = 'pending'
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn();
}

function getPreOrdersByUser($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT po.*, p.name as product_name, p.price, p.image 
            FROM pre_orders po
            JOIN products p ON po.product_id = p.id
            WHERE po.user_id = ?
            ORDER BY po.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Pre Order Error: " . $e->getMessage());
        return [];
    }
}

function getOrdersByUser($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT o.* 
        FROM orders o
        WHERE o.user_id = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOrderItems($orderId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name as name, p.image, 
                   pc.color_name, pc.color_code
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_colors pc ON oi.color_id = pc.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Order Items Error: " . $e->getMessage());
        return [];
    }
}

function createPreOrder($userId, $productId, $quantity, $expectedDate) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO pre_orders 
            (user_id, product_id, quantity, expected_date) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $productId, $quantity, $expectedDate]);
    } catch (PDOException $e) {
        error_log("Create Pre Order Error: " . $e->getMessage());
        return false;
    }
}

function getPreOrderById($preOrderId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT po.*, u.name as customer_name, u.email, u.phone, u.address,
                   p.name as product_name, p.price, p.image, p.category, p.stock
            FROM pre_orders po
            JOIN users u ON po.user_id = u.id
            JOIN products p ON po.product_id = p.id
            WHERE po.id = ?
        ");
        $stmt->execute([$preOrderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Pre Order Error: " . $e->getMessage());
        return false;
    }
}

function getOrderDetails($orderId, $userId) {
    global $pdo;
    
    try {
        // Get order header
        $stmt = $pdo->prepare("
            SELECT o.*, p.method as payment_method, p.status as payment_status
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            WHERE o.id = ? AND o.user_id = ?
        ");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image, p.price
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $order;
    } catch (PDOException $e) {
        error_log("Order Error: " . $e->getMessage());
        return null;
    }
}

function cancelOrder($orderId, $userId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Verify order belongs to user and is cancelable
        $stmt = $pdo->prepare("
            SELECT status FROM orders 
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'processing')
            FOR UPDATE
        ");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            throw new Exception("Pesanan tidak dapat dibatalkan");
        }
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled', 
                cancelled_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        // Restore product stocks
        $stmt = $pdo->prepare("
            UPDATE products p
            JOIN order_items oi ON p.id = oi.product_id
            SET p.stock = p.stock + oi.quantity
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Cancel Order Error: " . $e->getMessage());
        return false;
    }
}

function confirmOrderDelivery($orderId, $userId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Verifikasi pesanan milik user dan statusnya 'shipped'
        $stmt = $pdo->prepare("
            SELECT status FROM orders 
            WHERE id = ? AND user_id = ? AND status = 'shipped'
            FOR UPDATE
        ");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $pdo->rollBack();
            error_log("Order not eligible for delivery confirmation");
            return false;
        }
        
        // Update status pesanan
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'delivered',
                delivered_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Delivery Confirmation Error: " . $e->getMessage());
        return false;
    }
}

function getShippedOrderCount($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM orders o
        LEFT JOIN shipments s ON o.id = s.order_id
        WHERE o.user_id = ? 
        AND (o.status = 'shipped' OR s.status = 'shipped' OR s.status = 'in_transit')
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn();
}

function createOrder($userId, $items, $total) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Buat pesanan
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total) VALUES (?, ?)");
        $stmt->execute([$userId, $total]);
        $orderId = $pdo->lastInsertId();
        
        // Tambahkan item pesanan
        $stmt = $pdo->prepare("
            INSERT INTO order_items 
            (order_id, product_id, quantity, price, color_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($items as $item) {
            $stmt->execute([
                $orderId, 
                $item['product_id'], 
                $item['quantity'], 
                $item['price'],
                $item['color_id'] ?? null
            ]);
            
            // Kurangi stok produk
            $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                ->execute([$item['quantity'], $item['product_id']]);
        }
        
        $pdo->commit();
        return $orderId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getOrdersByUserId($userId, $limit = null) {
    global $pdo;
    
    $query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
    if ($limit) {
        $query .= " LIMIT ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $limit]);
    } else {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentOrdersForUser($userId, $limit = 5) {
    return getOrdersByUserId($userId, $limit);
}

function processPayment($orderId, $userId, $amount, $method) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Verify order belongs to user
        $order = getOrderDetails($orderId, $userId);
        if (!$order || $order['status'] !== 'pending') {
            throw new Exception("Invalid order for payment");
        }
        
        // Verify payment amount
        if ($amount != $order['total']) {
            throw new Exception("Payment amount doesn't match order total");
        }
        
        // Record payment
        $stmt = $pdo->prepare("
            INSERT INTO payments 
            (order_id, amount, method, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$orderId, $amount, $method]);
        $paymentId = $pdo->lastInsertId();
        
        // Update order status
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET status = 'processing' 
            WHERE id = ?
        ");
        $stmt->execute([$orderId]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Payment Error: " . $e->getMessage());
        return false;
    }
}

function getUserById($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("User Error: " . $e->getMessage());
        return false;
    }
}
function canDeleteOrder($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $order && in_array($order['status'], ['pending', 'cancelled']);
}

?>