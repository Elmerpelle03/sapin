<?php
require '../../config/db.php';
require '../../config/session_admin.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['product_id']) || !is_array($input['variants'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$product_id = (int)$input['product_id'];
$variants = $input['variants'];

try {
    $pdo->beginTransaction();

    // Fetch existing for this product (lock rows for consistency)
    $stmt = $pdo->prepare("SELECT variant_id, stock, size_multiplier FROM product_variants WHERE product_id = :pid FOR UPDATE");
    $stmt->execute([':pid' => $product_id]);
    $existingRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existing = array_column($existingRows, 'variant_id');
    $existingStockMap = [];
    $existingMultMap = [];
    foreach ($existingRows as $row) {
        $existingStockMap[(int)$row['variant_id']] = (int)$row['stock'];
        $existingMultMap[(int)$row['variant_id']] = (float)$row['size_multiplier'];
    }
    $keepIds = [];

    // Validate duplicates by size (case-insensitive)
    $seen = [];
    foreach ($variants as $v) {
        $sizeKey = strtolower(trim($v['size'] ?? ''));
        if ($sizeKey === '' || !isset($v['price']) || !isset($v['stock']) || !isset($v['size_multiplier'])) {
            throw new Exception('Missing fields in variant');
        }
        if (isset($seen[$sizeKey])) {
            throw new Exception('Duplicate size: ' . $v['size']);
        }
        $seen[$sizeKey] = true;
    }

    // Upsert rows
    $ins = $pdo->prepare("INSERT INTO product_variants (product_id, size, price, stock, size_multiplier, is_active)
                          VALUES (:pid, :size, :price, :stock, :mult, :active)");
    $upd = $pdo->prepare("UPDATE product_variants
                          SET size = :size, price = :price, stock = :stock, size_multiplier = :mult, is_active = :active
                          WHERE variant_id = :vid AND product_id = :pid");

    // Track stock increases for material deduction
    $increases = [];

    foreach ($variants as $v) {
        $vid = isset($v['variant_id']) && $v['variant_id'] !== '' ? (int)$v['variant_id'] : null;
        $size = trim($v['size']);
        $price = (float)$v['price'];
        $stock = (int)$v['stock'];
        $mult = (float)$v['size_multiplier'];
        $active = (int)($v['is_active'] ?? 1);

        if ($price <= 0 || $stock < 0 || $mult <= 0) {
            throw new Exception('Invalid numeric values');
        }

        if ($vid) {
            // Compare previous vs new stock
            $prevStock = $existingStockMap[$vid] ?? 0;
            $prevMult = $existingMultMap[$vid] ?? $mult;
            $upd->execute([':size'=>$size, ':price'=>$price, ':stock'=>$stock, ':mult'=>$mult, ':active'=>$active, ':vid'=>$vid, ':pid'=>$product_id]);
            $keepIds[] = $vid;
            if ($stock > $prevStock && $active === 1) {
                $increases[] = ['units' => $stock - $prevStock, 'mult' => $mult, 'variant_id' => $vid];
            }
        } else {
            $ins->execute([':pid'=>$product_id, ':size'=>$size, ':price'=>$price, ':stock'=>$stock, ':mult'=>$mult, ':active'=>$active]);
            $newVid = (int)$pdo->lastInsertId();
            $keepIds[] = $newVid;
            if ($stock > 0 && $active === 1) {
                $increases[] = ['units' => $stock, 'mult' => $mult, 'variant_id' => $newVid];
            }
        }
    }

    // Deactivate others that are not in payload
    if (!empty($existing)) {
        $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
        $sql = "UPDATE product_variants SET is_active = 0 WHERE product_id = ?";
        $params = [$product_id];
        if (!empty($keepIds)) {
            $sql .= " AND variant_id NOT IN ($placeholders)";
            $params = array_merge([$product_id], $keepIds);
        }
        $pdo->prepare($sql)->execute($params);
    }

    // MATERIAL DEDUCTION for increases using product BOM * size_multiplier
    if (!empty($increases)) {
        // Lock product materials
        $matStmt = $pdo->prepare("SELECT pm.material_id, pm.quantity_needed, m.stock, m.material_name
                                  FROM product_materials pm
                                  JOIN materials m ON pm.material_id = m.material_id
                                  WHERE pm.product_id = :pid
                                  FOR UPDATE");
        $matStmt->execute([':pid' => $product_id]);
        $materials = $matStmt->fetchAll(PDO::FETCH_ASSOC);

        // Build combined requirement per material across all increases
        $needTotals = []; // material_id => total_needed
        $totalUnits = 0;  // sum of units across variants (for logging)
        foreach ($increases as $inc) {
            $totalUnits += (int)$inc['units'];
            foreach ($materials as $mat) {
                $mid = (int)$mat['material_id'];
                $need = (float)$mat['quantity_needed'] * (int)$inc['units'] * (float)$inc['mult'];
                if (!isset($needTotals[$mid])) $needTotals[$mid] = 0.0;
                $needTotals[$mid] += $need;
            }
        }

        // Check sufficiency using combined totals
        $insufficient = [];
        $insufficientDetails = [];
        foreach ($materials as $mat) {
            $mid = (int)$mat['material_id'];
            $need = (float)($needTotals[$mid] ?? 0.0);
            if ($need > 0 && (float)$mat['stock'] < $need) {
                $shortage = $need - (float)$mat['stock'];
                $insufficient[] = $mat['material_name'] . ' (need ' . $need . ', have ' . $mat['stock'] . ')';
                $insufficientDetails[] = [
                    'name' => $mat['material_name'],
                    'needed' => $need,
                    'available' => (float)$mat['stock'],
                    'shortage' => $shortage
                ];
            }
        }
        if (!empty($insufficient)) {
            // Store insufficient materials in session for restock modal
            $_SESSION['insufficient_materials'] = $insufficientDetails;
            $_SESSION['trigger_restock_modal'] = true;
            $_SESSION['variant_update_context'] = [
                'product_id' => $product_id,
                'total_units_requested' => $totalUnits,
                'increases' => $increases
            ];
            
            // Rollback and return structured error
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => 'Insufficient materials for stock increases',
                'insufficient_materials' => $insufficientDetails,
                'detailed_message' => implode(', ', $insufficient),
                'requested_units' => $totalUnits,
                'max_producible' => 0
            ]);
            exit;
        }

        // Deduct once per material and log a single summary row per material
        foreach ($materials as $mat) {
            $mid = (int)$mat['material_id'];
            $need = (float)($needTotals[$mid] ?? 0.0);
            if ($need <= 0) continue;
            $updMat = $pdo->prepare("UPDATE materials SET stock = stock - :q WHERE material_id = :mid");
            $updMat->execute([':q' => $need, ':mid' => $mid]);
            $log = $pdo->prepare("INSERT INTO material_usage_log (product_id, material_id, quantity_used, product_quantity_produced, action_type, created_by, notes)
                                   VALUES (:pid, :mid, :qty, :units, 'production', :uid, :notes)");
            $log->execute([
                ':pid' => $product_id,
                ':mid' => $mid,
                ':qty' => $need,
                ':units' => $totalUnits,
                ':uid' => $_SESSION['user_id'] ?? null,
                ':notes' => 'Variant stock increases (batched)'
            ]);
        }
    }

    // Update product total stock to sum of active variant stock
    $sumStmt = $pdo->prepare("SELECT COALESCE(SUM(stock),0) AS total FROM product_variants WHERE product_id = :pid AND is_active = 1");
    $sumStmt->execute([':pid' => $product_id]);
    $total = (int)$sumStmt->fetchColumn();
    $updProd = $pdo->prepare("UPDATE products SET stock = :s WHERE product_id = :pid");
    $updProd->execute([':s' => $total, ':pid' => $product_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'total_stock' => $total]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('save_variants error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('save_variants PDO error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
