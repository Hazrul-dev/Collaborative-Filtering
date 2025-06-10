<?php
require_once __DIR__ . '/../config/database.php';

function getShipmentsByUser($userId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT s.*, o.order_date, 
               u.name as customer_name, u.email, u.phone, u.address as shipping_address
        FROM shipments s
        JOIN orders o ON s.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE o.user_id = ?
        ORDER BY s.shipping_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateTrackingNumber($shipmentId, $trackingNumber) {
    global $pdo;
    $stmt = $pdo->prepare("
        UPDATE shipments 
        SET tracking_number = ?, status = 'shipped', shipping_date = NOW()
        WHERE id = ?
    ");
    return $stmt->execute([$trackingNumber, $shipmentId]);
}
?>