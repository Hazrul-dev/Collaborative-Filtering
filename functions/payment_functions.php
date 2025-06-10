<?php
require_once __DIR__ . '/../config/database.php';

if (!function_exists('processPayment')) {
    function processPayment($orderId, $userId, $amount, $method) {
        global $pdo;
        
        try {
            $pdo->beginTransaction();
            
            // Verifikasi order
            $order = $pdo->prepare("
                SELECT id, total, status 
                FROM orders 
                WHERE id = ? AND user_id = ?
                FOR UPDATE
            ");
            $order->execute([$orderId, $userId]);
            $order = $order->fetch(PDO::FETCH_ASSOC);
            
            if (!$order || $order['status'] != 'pending') {
                throw new Exception("Pesanan tidak valid atau sudah diproses");
            }
            
            if ($amount != $order['total']) {
                throw new Exception("Jumlah pembayaran tidak sesuai");
            }
            
            // Buat pembayaran
            $payment = $pdo->prepare("
                INSERT INTO payments 
                (order_id, amount, method, status)
                VALUES (?, ?, ?, 'completed')
            ");
            $payment->execute([$orderId, $amount, $method]);
            
            // Update status order
            $updateOrder = $pdo->prepare("
                UPDATE orders 
                SET status = 'processing' 
                WHERE id = ?
            ");
            $updateOrder->execute([$orderId]);
            
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Payment error: " . $e->getMessage());
            return false;
        }
    }
}
function verifyPayment($paymentId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE payments p
            JOIN orders o ON p.order_id = o.id
            SET p.status = 'completed',
                o.status = 'processing'
            WHERE p.id = ?
            AND p.status = 'pending'
        ");
        return $stmt->execute([$paymentId]);
    } catch (PDOException $e) {
        error_log("Payment Verification Error: " . $e->getMessage());
        return false;
    }
}

function getPaymentMethods() {
    return [
        'bank_transfer' => 'Transfer Bank',
        'e_wallet' => 'E-Wallet',
        'cod' => 'COD (Bayar di Tempat)'
    ];
}
?>