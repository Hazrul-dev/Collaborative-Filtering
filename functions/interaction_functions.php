<?php
// functions/interaction_functions.php

function recordUserInteraction($userId, $productId, $interactionType) {
    global $pdo;
    
    try {
        // Cek apakah tabel ada
        $tableExists = $pdo->query("SHOW TABLES LIKE 'user_product_interactions'")->rowCount() > 0;
        
        if (!$tableExists) {
            error_log("Tabel user_product_interactions tidak ditemukan");
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO user_product_interactions 
            (user_id, product_id, interaction_type) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            interaction_date = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$userId, $productId, $interactionType]);
    } catch (PDOException $e) {
        error_log("Interaction Error: " . $e->getMessage());
        return false;
    }
}

function recordProductDeliveryInteractions($orderId, $userId) {
    global $pdo;
    try {
        $items = $pdo->query("
            SELECT product_id FROM order_items 
            WHERE order_id = $orderId
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($items as $productId) {
            recordUserInteraction($userId, $productId, 'purchase');
        }
        return true;
    } catch (PDOException $e) {
        error_log("Delivery Interaction Error: " . $e->getMessage());
        return false;
    }
}