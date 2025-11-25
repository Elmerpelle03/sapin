<?php
require '../../config/db.php';
require '../../config/session_admin.php';
header('Content-Type: application/json');

$mode = isset($_GET['mode']) ? strtolower(trim($_GET['mode'])) : 'low';
if (!in_array($mode, ['low','out','all'], true)) { $mode = 'low'; }

try {
    // Build base SQL for active variants joined with products and category
    $sql = "SELECT 
                p.product_id,
                p.product_name,
                p.restock_alert,
                c.category_name,
                v.variant_id,
                v.size,
                v.price,
                v.stock,
                v.size_multiplier,
                v.is_active
            FROM products p
            JOIN product_variants v ON v.product_id = p.product_id AND v.is_active = 1
            LEFT JOIN product_category c ON c.category_id = p.category_id
            WHERE 1";

    // Filter by mode
    if ($mode === 'low') {
        $sql .= " AND v.stock > 0 AND v.stock <= p.restock_alert";
    } elseif ($mode === 'out') {
        $sql .= " AND v.stock = 0";
    } else {
        // all active sizes, no extra predicate
    }

    $sql .= " ORDER BY p.product_name ASC, v.size ASC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group rows by product for client convenience
    $response = [];
    foreach ($rows as $r) {
        $response[] = [
            'product_id' => (int)$r['product_id'],
            'product_name' => $r['product_name'],
            'category_name' => $r['category_name'],
            'restock_alert' => (int)$r['restock_alert'],
            'variant' => [
                'variant_id' => (int)$r['variant_id'],
                'size' => $r['size'],
                'price' => (float)$r['price'],
                'stock' => (int)$r['stock'],
                'size_multiplier' => (float)$r['size_multiplier'],
                'is_active' => (int)$r['is_active']
            ]
        ];
    }

    echo json_encode(['success' => true, 'rows' => $response]);
} catch (PDOException $e) {
    error_log('fetch_low_variants error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
