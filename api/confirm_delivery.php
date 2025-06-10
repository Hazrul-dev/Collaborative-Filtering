<?php
// api/confirm_delivery.php

require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/order_functions.php';
require_once __DIR__ . '/../functions/interaction_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

if (confirmOrderDelivery($orderId, $userId)) {
    recordProductDeliveryInteractions($orderId, $userId);
    echo json_encode(['success' => true]);
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Gagal mengkonfirmasi pesanan'
    ]);
}