<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require '../../config/db.php';
require '../../config/session_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $bundle_price = $_POST['bundle_price'] ?? null;
    $description = $_POST['description'];
    $stock = $_POST['stock'];
    $pieces_per_bundle = $_POST['pieces_per_bundle'] ?? null;
    $category_id = $_POST['category_id'];
    $size = $_POST['size'];
    $material = $_POST['material'];
    $restock_alert = $_POST['restock_alert'];

    // Image handling
    $image = $_FILES['product_image'];
    $image_name = '';
    
    // Basic validation
    if (
        empty($product_name) || empty($price) || empty($description) ||
        empty($stock) || empty($category_id) ||
        empty($size) || empty($material)
    ) {
        $_SESSION['error_message'] = "All fields are required!";
        header('Location: ../products.php');
        exit;
    }

    // Defaults for optional bundle fields
    if ($bundle_price === '' || $bundle_price === null) {
        $bundle_price = $price;
    }
    if ($pieces_per_bundle === '' || $pieces_per_bundle === null) {
        $pieces_per_bundle = 1;
    }

    // Handle file upload
    if ($image['error'] === 0) {
        $image_ext = pathinfo($image['name'], PATHINFO_EXTENSION);
        $image_name = uniqid('prod_', true) . '.' . $image_ext;
        $upload_path = '../../uploads/products/' . $image_name;

        if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
            $_SESSION['error_message'] = "Image upload failed.";
            header('Location: ../products.php');
            exit;
        }
    } else {
        $_SESSION['error_message'] = "Please upload a valid image.";
        header('Location: ../products.php');
        exit;
    }

    // Insert into database
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO products (
            product_name, price, bundle_price, description, stock, pieces_per_bundle,
            category_id, size, material, image_url, restock_alert
        ) VALUES (
            :product_name, :price, :bundle_price, :description, :stock, :pieces_per_bundle,
            :category_id, :size, :material, :image_url, :restock_alert
        )");
        
        $stmt->execute([
            ':product_name' => $product_name,
            ':price' => $price,
            ':bundle_price' => $bundle_price,
            ':description' => $description,
            ':stock' => $stock,
            ':pieces_per_bundle' => $pieces_per_bundle,
            ':category_id' => $category_id,
            ':size' => $size,
            ':material' => $material,
            ':image_url' => $image_name,
            ':restock_alert' => $restock_alert
        ]);
        
        $product_id = $pdo->lastInsertId();
        
        // Auto-link product to material and determine quantity needed
        $quantity_needed = 2.0; // Default
        
        // Determine quantity based on product type and size
        $product_name_lower = strtolower($product_name);
        $size_lower = strtolower(trim($size));
        
        if (strpos($product_name_lower, 'curtain') !== false) {
            // Curtains - based on height
            if (strpos($size_lower, '5') !== false && strpos($size_lower, 'ft') !== false) {
                $quantity_needed = 1.68;
            } elseif (strpos($size_lower, '6') !== false && strpos($size_lower, 'ft') !== false) {
                $quantity_needed = 2.04;
            } elseif (strpos($size_lower, '7') !== false && strpos($size_lower, 'ft') !== false) {
                $quantity_needed = 2.35;
            } elseif (strpos($size_lower, '8') !== false && strpos($size_lower, 'ft') !== false) {
                $quantity_needed = 2.68;
            }
        } elseif (strpos($product_name_lower, 'bedsheet') !== false) {
            // Bedsheets - based on size
            if (strpos($size_lower, 'single') !== false) {
                $quantity_needed = 2.18;
            } elseif (strpos($size_lower, 'double') !== false) {
                $quantity_needed = 2.27;
            } elseif (strpos($size_lower, 'family') !== false) {
                $quantity_needed = 2.36;
            } elseif (strpos($size_lower, 'queen') !== false) {
                $quantity_needed = 2.72;
            } elseif (strpos($size_lower, 'king') !== false) {
                $quantity_needed = 3.21;
            }
        } elseif (strpos($product_name_lower, 'sofamatt') !== false) {
            // Sofa mats - based on dimensions
            if ((strpos($size_lower, '20x60') !== false) || (strpos($product_name_lower, 'long') !== false)) {
                $quantity_needed = 1.2;
            } elseif (strpos($size_lower, '20x20') !== false || strpos($product_name_lower, 'small') !== false) {
                $quantity_needed = 0.6;
            } elseif (strpos($size_lower, '24') !== false && strpos($size_lower, '72') !== false) {
                $quantity_needed = 3.0;
            }
        } elseif (strpos($product_name_lower, 'foam cover') !== false || strpos($product_name_lower, 'foamcover') !== false) {
            if (strpos($size_lower, 'single') !== false) {
                $quantity_needed = 2.8;
            } elseif (strpos($size_lower, 'double') !== false) {
                $quantity_needed = 3.3;
            } elseif (strpos($size_lower, 'family') !== false) {
                $quantity_needed = 3.6;
            } elseif (strpos($size_lower, 'queen') !== false) {
                $quantity_needed = 4.0;
            } elseif (strpos($size_lower, 'king') !== false) {
                $quantity_needed = 4.6;
            } elseif (strpos($size_lower, 'matrimonial') !== false) {
                $quantity_needed = 5.0;
            }
        } elseif (strpos($product_name_lower, 'comforter') !== false) {
            if (strpos($size_lower, 'single') !== false) {
                $quantity_needed = 5.0;
            } elseif (strpos($size_lower, 'double') !== false) {
                $quantity_needed = 5.5;
            } elseif (strpos($size_lower, 'family') !== false) {
                $quantity_needed = 5.8;
            } elseif (strpos($size_lower, 'queen') !== false) {
                $quantity_needed = 6.2;
            } elseif (strpos($size_lower, 'king') !== false) {
                $quantity_needed = 6.8;
            } elseif (strpos($size_lower, 'matrimonial') !== false) {
                $quantity_needed = 7.2;
            }
        } elseif (strpos($product_name_lower, 'table') !== false && strpos($product_name_lower, 'runner') !== false) {
            $quantity_needed = 1.5;
        } elseif (strpos($product_name_lower, 'pillow') !== false) {
            $quantity_needed = 0.5;
        }
        
        // Find the material_id based on the material name
        $findMaterialStmt = $pdo->prepare("
            SELECT material_id, stock, material_name
            FROM materials
            WHERE LOWER(TRIM(material_name)) = LOWER(TRIM(:material_name))
            FOR UPDATE
        ");
        $findMaterialStmt->execute(['material_name' => $material]);
        $materialInfo = $findMaterialStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$materialInfo) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Material '{$material}' not found in inventory. Please add the material first.";
            header('Location: ../products.php');
            exit;
        }
        
        // Create the product-material link for fabric
        $linkStmt = $pdo->prepare("
            INSERT INTO product_materials (product_id, material_id, quantity_needed)
            VALUES (:product_id, :material_id, :quantity_needed)
            ON DUPLICATE KEY UPDATE quantity_needed = :quantity_needed_update
        ");
        $quantity_needed_yards = $quantity_needed * 1.09361;
        $linkStmt->execute([
            'product_id' => $product_id,
            'material_id' => $materialInfo['material_id'],
            'quantity_needed' => $quantity_needed_yards,
            'quantity_needed_update' => $quantity_needed_yards
        ]);
        
        // Add crushed foam with fiber for pillows and sofa mats
        $needsFoam = (strpos($product_name_lower, 'pillow') !== false) || 
                     (strpos($product_name_lower, 'sofamatt') !== false) ||
                     (strpos($product_name_lower, 'comforter') !== false);
        
        if ($needsFoam) {
            // Find crushed foam with fiber material
            $foamStmt = $pdo->prepare("
                SELECT material_id, stock, material_name
                FROM materials
                WHERE LOWER(TRIM(material_name)) LIKE '%crushed%foam%'
                FOR UPDATE
            ");
            $foamStmt->execute();
            $foamInfo = $foamStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($foamInfo) {
                // 266.67g per piece (8000g = 30 pieces)
                $foamQuantity = 266.67;
                if (strpos($product_name_lower, 'sofamatt') !== false) {
                    if ((strpos($size_lower, '20x60') !== false) || (strpos($product_name_lower, 'long') !== false)) {
                        $foamQuantity = 900.0;
                    } elseif (strpos($size_lower, '20x20') !== false || strpos($product_name_lower, 'small') !== false) {
                        $foamQuantity = 300.0;
                    } elseif (strpos($size_lower, '24') !== false && strpos($size_lower, '72') !== false) {
                        $foamQuantity = 1200.0;
                    }
                } elseif (strpos($product_name_lower, 'comforter') !== false) {
                    if (strpos($size_lower, 'single') !== false) {
                        $foamQuantity = 1500.0;
                    } elseif (strpos($size_lower, 'double') !== false) {
                        $foamQuantity = 2000.0;
                    } elseif (strpos($size_lower, 'family') !== false) {
                        $foamQuantity = 2200.0;
                    } elseif (strpos($size_lower, 'queen') !== false) {
                        $foamQuantity = 2500.0;
                    } elseif (strpos($size_lower, 'king') !== false) {
                        $foamQuantity = 3000.0;
                    } elseif (strpos($size_lower, 'matrimonial') !== false) {
                        $foamQuantity = 3300.0;
                    }
                }
                
                $linkFoamStmt = $pdo->prepare("
                    INSERT INTO product_materials (product_id, material_id, quantity_needed)
                    VALUES (:product_id, :material_id, :quantity_needed)
                    ON DUPLICATE KEY UPDATE quantity_needed = :quantity_needed_update
                ");
                $linkFoamStmt->execute([
                    'product_id' => $product_id,
                    'material_id' => $foamInfo['material_id'],
                    'quantity_needed' => $foamQuantity,
                    'quantity_needed_update' => $foamQuantity
                ]);
            }
        }
        
        // Now get materials for deduction (will include the one we just linked)
        $materialStmt = $pdo->prepare("
            SELECT pm.material_id, pm.quantity_needed, m.stock, m.material_name
            FROM product_materials pm
            JOIN materials m ON pm.material_id = m.material_id
            WHERE pm.product_id = :product_id
            FOR UPDATE
        ");
        $materialStmt->execute(['product_id' => $product_id]);
        $materials = $materialStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insufficientMaterials = [];
        
        foreach ($materials as $mat) {
            $totalNeeded = $mat['quantity_needed'] * $stock;
            
            // Check if enough material is available
            if ($mat['stock'] < $totalNeeded) {
                $insufficientMaterials[] = "{$mat['material_name']} (need {$totalNeeded}, have {$mat['stock']})";
            }
        }
        
        // If insufficient materials, rollback with special error
        if (!empty($insufficientMaterials)) {
            // Rollback product creation
            $pdo->rollBack();
            
            // Store insufficient materials info in session for restock functionality
            $cleanInsufficientMaterials = [];
            foreach ($insufficientMaterials as $materialString) {
                // Extract material name and quantities from "Fabric (need 50, have 30)"
                if (preg_match('/^([^(]+)\s*\(need\s+(\d+(?:\.\d+)?)\s*,\s*have\s+(\d+(?:\.\d+)?)\)/', $materialString, $matches)) {
                    $materialName = trim($matches[1]);
                    $needQuantity = floatval($matches[2]);
                    $haveQuantity = floatval($matches[3]);
                    $cleanInsufficientMaterials[] = [
                        'name' => $materialName, 
                        'original' => $materialString,
                        'need_quantity' => $needQuantity,
                        'have_quantity' => $haveQuantity,
                        'shortage' => $needQuantity - $haveQuantity
                    ];
                } else {
                    // Fallback for unexpected format
                    $materialName = preg_replace('/\s*\(.*?\)/', '', $materialString);
                    $cleanInsufficientMaterials[] = [
                        'name' => $materialName, 
                        'original' => $materialString,
                        'need_quantity' => 0,
                        'have_quantity' => 0,
                        'shortage' => 0
                    ];
                }
            }
            
            $_SESSION['insufficient_materials'] = $cleanInsufficientMaterials;
            $_SESSION['attempted_product'] = [
                'name' => $product_name,
                'stock' => $stock,
                'materials' => $materials
            ];
            $_SESSION['trigger_restock_modal'] = true;
            
            $_SESSION['error_message'] = "INSUFFICIENT_MATERIALS|" . implode(', ', $insufficientMaterials);
            header('Location: ../products.php');
            exit;
        }
        
        // Deduct materials and log usage
        foreach ($materials as $mat) {
            $totalNeeded = $mat['quantity_needed'] * $stock;
            
            // Deduct from material stock
            $updateStmt = $pdo->prepare("
                UPDATE materials 
                SET stock = stock - :quantity 
                WHERE material_id = :material_id
            ");
            $updateStmt->execute([
                'quantity' => $totalNeeded,
                'material_id' => $mat['material_id']
            ]);
            
            // Log the usage
            $logStmt = $pdo->prepare("
                INSERT INTO material_usage_log (product_id, material_id, quantity_used, product_quantity_produced, action_type, created_by, notes)
                VALUES (:product_id, :material_id, :quantity_used, :product_quantity, 'production', :user_id, :notes)
            ");
            $logStmt->execute([
                'product_id' => $product_id,
                'material_id' => $mat['material_id'],
                'quantity_used' => $totalNeeded,
                'product_quantity' => $stock,
                'user_id' => $_SESSION['user_id'] ?? null,
                'notes' => "Initial production when adding product"
            ]);
        }
        
        $pdo->commit();
        $_SESSION['success_message'] = "Product added successfully. Materials deducted from inventory.";
        header('Location: ../products.php');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('addproduct PDOException: ' . $e->getMessage());
        $_SESSION['error_message'] = "Unexpected error occurred.";
        header('Location: ../products.php');
        exit;
    }
}
?>
