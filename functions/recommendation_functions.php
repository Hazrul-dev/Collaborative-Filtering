<?php
require_once __DIR__ . '/../config/database.php';

// Fungsi untuk menghitung similarity antar produk menggunakan Cosine Similarity
function calculate_product_similarity($pdo) {
    try {
        // Clear existing recommendations
        $pdo->query("TRUNCATE TABLE product_recommendations");
        
        // Get all delivered orders with their products and quantities
        $orders = $pdo->query("
            SELECT o.user_id, oi.product_id, SUM(oi.quantity) as total_quantity, p.name as product_name
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN products p ON oi.product_id = p.id
            WHERE o.status = 'delivered'
            GROUP BY o.user_id, oi.product_id
            ORDER BY o.user_id
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($orders)) {
            error_log("No delivered orders found for recommendations");
            return false;
        }
        
        // Organize products by order with quantities
        $orderProducts = [];
        foreach ($orders as $order) {
            $userProductMatrix[$order['user_id']][$order['product_id']] = $order['total_quantity'];
        }
        
        // Get all active products
        $productIds = $pdo->query("SELECT id FROM products WHERE stock > 0")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($productIds)) {
            error_log("No active products found");
            return false;
        }
        
        // Create user-product matrix with quantities (normalized)
        $userProductMatrix = [];
        foreach ($orderProducts as $orderId => $products) {
            foreach ($productIds as $productId) {
                // Normalize quantities to 0-1 range per order
                $userProductMatrix[$orderId][$productId] = isset($products[$productId]) ? 
                    min(1, $products[$productId] / 5) : 0; // Assume max 5 items per product per order
            }
        }
        
        // Calculate cosine similarity
        $similarityMatrix = [];
        foreach ($productIds as $product1) {
            foreach ($productIds as $product2) {
                if ($product1 == $product2) {
                    $similarityMatrix[$product1][$product2] = 1;
                    continue;
                }
                
                $dotProduct = 0;
                $magnitude1 = 0;
                $magnitude2 = 0;
                
                foreach ($userProductMatrix as $userId => $products) {
                    $max = max($products);
                    foreach ($products as $productId => $quantity) {
                        $userProductMatrix[$userId][$productId] = $quantity / $max;
                    }
                }
                
                $magnitude = sqrt($magnitude1) * sqrt($magnitude2);
                $similarity = $magnitude != 0 ? $dotProduct / $magnitude : 0;
                
                // Only store significant similarities
                if ($similarity > 0.1) {
                    $similarityMatrix[$product1][$product2] = $similarity;
                }
            }
        }
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO product_recommendations 
            (product_id, recommended_product_id, similarity_score) 
            VALUES (?, ?, ?)
        ");
        
        foreach ($similarityMatrix as $product1 => $similarities) {
            arsort($similarities); // Sort by similarity descending
            
            $count = 0;
            foreach ($similarities as $product2 => $similarity) {
                if ($product1 == $product2 || $similarity <= 0.1) continue;
                
                $stmt->execute([$product1, $product2, $similarity]);
                $count++;
                
                if ($count >= 5) break; // Top 5 recommendations
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error in calculate_product_similarity: " . $e->getMessage());
        return false;
    }
}

function log_activity($userId, $action) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action) 
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $action]);
        return true;
    } catch (PDOException $e) {
        error_log("Error in log_activity: " . $e->getMessage());
        return false;
    }
}

// Fungsi untuk mendapatkan rekomendasi produk untuk pengguna tertentu
function get_recommended_products($pdo, $userId, $limit = 12) {
    try {
        // Jika tidak ada user ID, kembalikan produk populer
        if (!$userId) {
            return get_popular_products($pdo, $limit);
        }

        // Dapatkan produk yang pernah dibeli user
        $stmt = $pdo->prepare("
            SELECT DISTINCT product_id 
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = ? AND o.status = 'delivered'
        ");
        $stmt->execute([$userId]);
        $userProducts = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($userProducts)) {
            // Jika user belum pernah membeli, kembalikan produk populer
            return get_popular_products($pdo, $limit);
        }

        // Buat placeholder untuk query IN
        $placeholders = implode(',', array_fill(0, count($userProducts), '?'));
        
        // Dapatkan rekomendasi produk
        $stmt = $pdo->prepare("
            SELECT p.*, SUM(pr.similarity_score) as recommendation_score
            FROM product_recommendations pr
            JOIN products p ON pr.recommended_product_id = p.id
            WHERE pr.product_id IN ($placeholders)
            AND pr.recommended_product_id NOT IN ($placeholders)
            AND p.stock > 0
            GROUP BY pr.recommended_product_id
            ORDER BY recommendation_score DESC
            LIMIT ?
        ");
        
        // Gabungkan parameter untuk query
        $params = array_merge($userProducts, $userProducts, [$limit]);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_recommended_products: " . $e->getMessage());
        return get_popular_products($pdo, $limit); // Fallback ke produk populer jika error
    }
}


function getProductRecommendations($productId, $userId = null, $limit = 4) {
    global $pdo;
    
    try {
        // Rekomendasi berdasarkan similaritas produk
        $query = "
            SELECT p.*, pr.similarity_score
            FROM product_recommendations pr
            JOIN products p ON pr.recommended_product_id = p.id
            WHERE pr.product_id = ?
            AND p.stock > 0
            ORDER BY pr.similarity_score DESC
            LIMIT ?
        ";
        
        $params = [$productId, $limit];
        
        // Jika ada user ID, tambahkan rekomendasi berdasarkan riwayat user
        if ($userId) {
            $query = "
                (SELECT p.*, pr.similarity_score
                FROM product_recommendations pr
                JOIN products p ON pr.recommended_product_id = p.id
                JOIN (
                    SELECT product_id 
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.id
                    WHERE o.user_id = ? AND o.status = 'delivered'
                    GROUP BY product_id
                ) up ON up.product_id = pr.product_id
                WHERE pr.recommended_product_id != ?
                AND p.stock > 0
                ORDER BY pr.similarity_score DESC
                LIMIT ?)
                
                UNION
                
                (SELECT p.*, pr.similarity_score
                FROM product_recommendations pr
                JOIN products p ON pr.recommended_product_id = p.id
                WHERE pr.product_id = ?
                AND p.stock > 0
                ORDER BY pr.similarity_score DESC
                LIMIT ?)
                
                ORDER BY similarity_score DESC
                LIMIT ?
            ";
            
            $params = [
                $userId, $productId, $limit,
                $productId, $limit,
                $limit
            ];
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Recommendation Error: " . $e->getMessage());
        return [];
    }
}
function get_popular_products($pdo, $limit = 12) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, COUNT(oi.id) as purchase_count
            FROM products p
            LEFT JOIN order_items oi ON p.id = oi.product_id
            LEFT JOIN orders o ON oi.order_id = o.id AND o.status = 'delivered'
            WHERE p.stock > 0
            GROUP BY p.id
            ORDER BY purchase_count DESC, p.name ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in get_popular_products: " . $e->getMessage());
        return [];
    }
}

?>