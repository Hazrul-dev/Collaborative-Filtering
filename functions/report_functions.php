<?php
function getTotalProductsCount() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
}

function getTotalOrdersCount() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
}

function getTotalUsersCount() {
    global $pdo;
    return $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

function getMonthlyRevenue() {
    global $pdo;
    $currentMonth = date('Y-m');
    return $pdo->query("
        SELECT SUM(total) 
        FROM orders 
        WHERE status = 'delivered' 
        AND DATE_FORMAT(order_date, '%Y-%m') = '$currentMonth'
    ")->fetchColumn() ?? 0;
}

function getLowStockProducts($limit = 5) {
    global $pdo;
    
    // Solusi 1: Gunakan query langsung dengan parameter yang sudah divalidasi
    $limit = (int)$limit; // Pastikan limit adalah integer
    $stmt = $pdo->query("
        SELECT * 
        FROM products 
        WHERE stock < 10 
        ORDER BY stock ASC 
        LIMIT $limit
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecentOrders($limit = 5) {
    global $pdo;
    
    $limit = (int)$limit;
    $stmt = $pdo->query("
        SELECT o.*, u.name as customer_name 
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC 
        LIMIT $limit
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSalesDataLast30Days() {
    global $pdo;
    $stmt = $pdo->query("
        SELECT 
            DATE(order_date) as date,
            SUM(total) as total_sales,
            COUNT(*) as order_count
        FROM orders
        WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND status = 'delivered'
        GROUP BY DATE(order_date)
        ORDER BY DATE(order_date)
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLatestReports($limit = 5) {
    global $pdo;
    
    $limit = (int)$limit;
    $stmt = $pdo->query("
        SELECT * 
        FROM reports 
        ORDER BY created_at DESC 
        LIMIT $limit
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function generateReportPDF($data, $title, $filename) {
    require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';
    
    // Buat instance PDF baru
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set dokumen metadata
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Toko Izra Fashion');
    $pdf->SetTitle($title);
    $pdf->SetSubject('Laporan Toko Izra Fashion');
    
    // Tambahkan halaman
    $pdf->AddPage();
    
    // Tambahkan judul
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 15, $title, 0, 1, 'C');
    
    // Tambahkan informasi laporan
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'Dibuat pada: ' . date('d F Y H:i:s'), 0, 1);
    
    // Tambahkan tabel
    if (!empty($data)) {
        $pdf->SetFont('helvetica', 'B', 10);
        
        // Header tabel
        $headers = array_keys($data[0]);
        foreach ($headers as $header) {
            $pdf->Cell(40, 7, ucwords(str_replace('_', ' ', $header)), 1);
        }
        $pdf->Ln();
        
        // Isi tabel
        $pdf->SetFont('helvetica', '', 9);
        foreach ($data as $row) {
            foreach ($row as $cell) {
                $pdf->Cell(40, 6, $cell, 1);
            }
            $pdf->Ln();
        }
    } else {
        $pdf->Cell(0, 10, 'Tidak ada data yang ditemukan', 0, 1);
    }
    
    // Output PDF
    $pdf->Output($filename, 'D');
}
?>