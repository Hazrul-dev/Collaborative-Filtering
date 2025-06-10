<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/auth_functions.php';
require_once __DIR__ . '/../functions/recommendation.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $success = calculateProductSimilarities();
    echo json_encode(['success' => $success, 'message' => $success ? 'Success' : 'Failed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}