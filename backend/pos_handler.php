<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../../config/db.php';

if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['usertype_id'], [1, 5])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'get_products':
            $response = getProducts($pdo);
            break;
        case 'get_variants':
            $response = getVariants($pdo);
            break;
        case 'add_to_cart':
            $response = addToCart($pdo);
            break;
        case 'update_cart_item':
            $response = updateCartItem($pdo);
            break;
        case 'remove_from_cart':
            $response = removeFromCart($pdo);
            break;
        case 'clear_cart':
            $response = clearCart($pdo);
            break;
        case 'process_sale':
            $response = processSale($pdo);
            break;
        case 'get_sales_history':
            $response = getSalesHistory($pdo);
            break;
        case 'get_sale_receipt':
            $response = getSaleReceipt($pdo);
            break;
        case 'hold_transaction':
            $response = holdTransaction($pdo);
            break;
        case 'get_held_transactions':
            $response = getHeldTransactions($pdo);
            break;
        case 'recall_held_transaction':
            $response = recallHeldTransaction($pdo);
            break;
        case 'get_cart':
            $response = getCart($pdo);
            break;
        case 'void_sale':
            $response = voidSale($pdo);
            break;
        case 'get_pos_settings':
            $response = getPosSettings($pdo);
            break;
        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
    }
} catch (Exception $e) {
    error_log('POS Handler Error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred while processing your request'];
}

echo json_encode($response);

// =======================
// PRODUCT FUNCTIONS
// =======================

// Get variants for a specific product
function getVariants($pdo) {
    try {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id <= 0) {
            return ['success' => false, 'message' => 'Invalid product ID'];
        }
        
        $stmt = $pdo->prepare("SELECT variant_id, size, stock, is_active FROM product_variants WHERE product_id = ? ORDER BY size ASC");
        $stmt->execute([$product_id]);
        $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert numeric values to integers for JavaScript
        foreach ($variants as &$variant) {
            $variant['variant_id'] = (int)$variant['variant_id'];
            $variant['stock'] = (int)$variant['stock'];
            $variant['is_active'] = (int)$variant['is_active'] === 1;
        }
        
        return ['success' => true, 'variants' => $variants];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error fetching variants: ' . $e->getMessage()];
    }
}
function getProducts($pdo) {
    try {
        $category = $_POST['category'] ?? '';
        $search = $_POST['search'] ?? '';
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        $sql = "SELECT p.product_id, p.product_name, p.price, 
                      COALESCE(vs.total_stock, p.stock) AS stock, 
                      p.image_url, p.category_id, p.restock_alert,
                      COALESCE(p.product_image, 'default.jpg') as product_image
                FROM products p 
                LEFT JOIN (
                    SELECT 
                        product_id,
                        SUM(CASE WHEN is_active = 1 THEN stock ELSE 0 END) AS total_stock
                    FROM product_variants
                    GROUP BY product_id
                ) vs ON vs.product_id = p.product_id
                WHERE 1=1";
        $params = [];
        
        // Only filter by stock > 0 if not looking for a specific product
        if ($product_id <= 0) {
            $sql .= " AND COALESCE(vs.total_stock, p.stock) > 0";
        }
        
        // Filter by specific product ID if provided
        if ($product_id > 0) {
            $sql .= " AND p.product_id = ?";
            $params[] = $product_id;
        }
        
        if (!empty($category)) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category;
        }
        if (!empty($search)) {
            $sql .= " AND (p.product_name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY p.product_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return ['success' => true, 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error fetching products: ' . $e->getMessage()];
    }
}

// =======================
// CART FUNCTIONS
// =======================
function addToCart($pdo) {
    try {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);
        $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
        
        // Always get the latest product stock from the database
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? FOR UPDATE");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) return ['success' => false, 'message' => 'Product not found'];
        
        // If variant_id is provided, get that specific variant's stock and details
        $variantSize = null;
        if ($variant_id) {
            $stmt = $pdo->prepare("SELECT stock, size FROM product_variants WHERE variant_id = ? AND is_active = 1 FOR UPDATE");
            $stmt->execute([$variant_id]);
            $variant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$variant) {
                return ['success' => false, 'message' => 'Variant not found or inactive'];
            }
            
            // Use specific variant stock
            $product['stock'] = $variant['stock'];
            $variantSize = $variant['size'];
        }
        // If no variant_id but product has variants, use total variant stock
        else {
            // Check if product has variants
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND is_active = 1");
            $stmt->execute([$product_id]);
            $hasVariants = $stmt->fetchColumn() > 0;
            
            if ($hasVariants) {
                // Get total variant stock
                $stmt = $pdo->prepare("SELECT SUM(stock) FROM product_variants WHERE product_id = ? AND is_active = 1");
                $stmt->execute([$product_id]);
                $variantStock = $stmt->fetchColumn() ?: 0;
                
                // Use total variant stock instead of main product stock
                $product['stock'] = $variantStock;
            }
        }
        
        if ($product['stock'] <= 0) return ['success' => false, 'message' => 'Product is out of stock'];
        
        if (!isset($_SESSION['pos_cart'])) $_SESSION['pos_cart'] = [];
        
        $found = false;
        foreach ($_SESSION['pos_cart'] as &$item) {
            // Match both product_id and variant_id (if provided)
            $matches = $item['product_id'] == $product_id;
            if ($variant_id) {
                $matches = $matches && isset($item['variant_id']) && $item['variant_id'] == $variant_id;
            } else {
                $matches = $matches && !isset($item['variant_id']);
            }
            
            if ($matches) {
                $new_quantity = $item['quantity'] + $quantity;
                // Check if new quantity exceeds available stock
                if ($new_quantity > $product['stock']) {
                    return ['success' => false, 'message' => "Cannot add more. Available stock: {$product['stock']}, Currently in cart: {$item['quantity']}"];
                }
                $item['quantity'] = $new_quantity;
                $item['total_price'] = $item['quantity'] * $item['unit_price'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            // New item - check if quantity exceeds stock
            if ($quantity > $product['stock']) {
                return ['success' => false, 'message' => "Cannot add. Available stock: {$product['stock']}"];
            }
            
            // Create the cart item
            $cartItem = [
                'product_id' => $product_id,
                'product_name' => $product['product_name'],
                'unit_price' => $product['price'],
                'quantity' => $quantity,
                'total_price' => $product['price'] * $quantity,
                'image' => $product['product_image'] ?? 'default.jpg',
                'stock' => $product['stock']
            ];
            
            // Add variant information if provided
            if ($variant_id && $variantSize) {
                $cartItem['variant_id'] = $variant_id;
                $cartItem['variant_size'] = $variantSize;
                $cartItem['product_name'] .= ' (' . $variantSize . ')';
            }
            
            $_SESSION['pos_cart'][] = $cartItem;
        }
        return ['success'=>true,'message'=>'Product added to cart','cart'=>$_SESSION['pos_cart']];
    } catch (Exception $e) {
        return ['success'=>false,'message'=>'Error adding to cart: '.$e->getMessage()];
    }
}

function updateCartItem($pdo){
    try{
        $product_id = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
        
        if(!isset($_SESSION['pos_cart'])) return ['success'=>false,'message'=>'Cart is empty'];
        
        // If variant_id is provided, get that specific variant's stock
        if ($variant_id) {
            $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE variant_id = ? AND is_active = 1 FOR UPDATE");
            $stmt->execute([$variant_id]);
            $stock = $stmt->fetchColumn();
            
            if ($stock === false) {
                return ['success' => false, 'message' => 'Variant not found or inactive'];
            }
        } else {
            // Check if product has variants
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND is_active = 1");
            $stmt->execute([$product_id]);
            $hasVariants = $stmt->fetchColumn() > 0;
            
            if ($hasVariants) {
                // Get total variant stock
                $stmt = $pdo->prepare("SELECT SUM(stock) FROM product_variants WHERE product_id = ? AND is_active = 1 FOR UPDATE");
                $stmt->execute([$product_id]);
                $stock = $stmt->fetchColumn() ?: 0;
            } else {
                // Get main product stock
                $stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = ? FOR UPDATE");
                $stmt->execute([$product_id]);
                $stock = $stmt->fetchColumn();
            }
        }
        
        if($quantity>$stock) return ['success'=>false,'message'=>'Not enough stock available. Current stock: '.$stock];
        
        $found = false;
        foreach($_SESSION['pos_cart'] as &$item){
            // Match both product_id and variant_id (if provided)
            $matches = $item['product_id'] == $product_id;
            if ($variant_id) {
                $matches = $matches && isset($item['variant_id']) && $item['variant_id'] == $variant_id;
            } else {
                $matches = $matches && !isset($item['variant_id']);
            }
            
            if($matches){
                $found = true;
                if($quantity<=0){
                    // Mark for removal
                    $item['_remove'] = true;
                }else{
                    $item['quantity']=$quantity;
                    $item['total_price']=$item['quantity']*$item['unit_price'];
                }
                break;
            }
        }
        
        // Remove any items marked for removal
        if ($found && $quantity <= 0) {
            $_SESSION['pos_cart'] = array_values(array_filter($_SESSION['pos_cart'], function($item) {
                return !isset($item['_remove']);
            }));
        }
        return ['success'=>true,'message'=>'Cart updated','cart'=>$_SESSION['pos_cart']];
    }catch(Exception $e){
        return ['success'=>false,'message'=>'Error updating cart: '.$e->getMessage()];
    }
}

function removeFromCart($pdo){
    try{
        $product_id = (int)($_POST['product_id'] ?? 0);
        $variant_id = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;
        
        if(!isset($_SESSION['pos_cart'])) return ['success'=>false,'message'=>'Cart is empty'];
        
        if ($variant_id) {
            // Remove specific variant
            $_SESSION['pos_cart'] = array_values(array_filter($_SESSION['pos_cart'], function($item) use ($product_id, $variant_id) {
                return !($item['product_id'] == $product_id && isset($item['variant_id']) && $item['variant_id'] == $variant_id);
            }));
        } else {
            // Remove non-variant product
            $_SESSION['pos_cart'] = array_values(array_filter($_SESSION['pos_cart'], function($item) use ($product_id) {
                return !($item['product_id'] == $product_id && !isset($item['variant_id']));
            }));
        }
        return ['success'=>true,'message'=>'Item removed from cart','cart'=>$_SESSION['pos_cart']];
    }catch(Exception $e){
        return ['success'=>false,'message'=>'Error removing from cart: '.$e->getMessage()];
    }
}

function clearCart($pdo){
    $_SESSION['pos_cart']=[]; 
    return ['success'=>true,'message'=>'Cart cleared'];
}

function getCart($pdo){
    if(!isset($_SESSION['pos_cart'])) $_SESSION['pos_cart']=[]; 
    return ['success'=>true,'cart'=>$_SESSION['pos_cart']];
}

// =======================
// PROCESS SALE FUNCTION
// =======================
function processSale($pdo){
    try{
        // ===== GET CART =====
        $cart = [];
        if (isset($_POST['cart_items'])) {
            $cart = json_decode($_POST['cart_items'], true);
        } else if (isset($_SESSION['pos_cart'])) {
            $cart = $_SESSION['pos_cart'];
        }

        if (!$cart || count($cart) == 0){
            return ['success'=>false, 'message'=>'Cart is empty'];
        }

        // Sync session
        $_SESSION['pos_cart'] = $cart;

        session_write_close();

        $amount_payment=(float)($_POST['amount_payment']??0);
        if($amount_payment<=0) throw new Exception('Payment amount missing or invalid');

        $payment_method=$_POST['payment_method']??'cash';

        $subtotal=0;
        foreach($cart as $item) $subtotal+=$item['total_price'];
        $tax_rate=0.01; // Changed from 12% to 1%
        $tax_amount=$subtotal*$tax_rate;
        $total_amount=$subtotal+$tax_amount;
        $change_amount=$amount_payment-$total_amount;

        if($amount_payment<$total_amount) throw new Exception('Insufficient payment amount');
        
        // First, check if variant columns exist in the pos_sale_items table
        // We need to do this outside any transaction
        try {
            $checkVariantColumns = $pdo->prepare("SHOW COLUMNS FROM pos_sale_items LIKE 'variant_id'");
            $checkVariantColumns->execute();
            $variantColumnsExist = $checkVariantColumns->rowCount() > 0;
            
            // If variant columns don't exist, try to add them
            if (!$variantColumnsExist) {
                $pdo->exec("ALTER TABLE pos_sale_items ADD COLUMN variant_id INT NULL AFTER total_price");
                $pdo->exec("ALTER TABLE pos_sale_items ADD COLUMN variant_size VARCHAR(50) NULL AFTER variant_id");
                $variantColumnsExist = true;
                error_log('Added variant columns to pos_sale_items table');
            }
        } catch (Exception $e) {
            error_log('Failed to check or add variant columns: ' . $e->getMessage());
            // Continue without variant columns
            $variantColumnsExist = false;
        }
        
        // Now start the transaction for the entire sale process
        $pdo->beginTransaction();
        
        $sale_number=generateSaleNumber($pdo);
        $stmt=$pdo->prepare("INSERT INTO pos_sales (sale_number,cashier_id,subtotal,tax_amount,total_amount,amount_payment,payment_method,change_amount) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$sale_number,$_SESSION['user_id'],$subtotal,$tax_amount,$total_amount,$amount_payment,$payment_method,$change_amount]);
        $sale_id=$pdo->lastInsertId();
        
        foreach($cart as $item){
            // Check if variant information is available and columns exist
            if ($variantColumnsExist && isset($item['variant_id']) && isset($item['variant_size'])) {
                $stmt=$pdo->prepare("INSERT INTO pos_sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price, variant_id, variant_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $sale_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['total_price'],
                    $item['variant_id'],
                    $item['variant_size']
                ]);
            } else {
                // Fall back to inserting without variant information
                $stmt=$pdo->prepare("INSERT INTO pos_sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$sale_id, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['total_price']]);
            }
            
            // Update main product stock regardless of variants
            $stmt=$pdo->prepare("UPDATE products SET stock=0 WHERE product_id=? AND stock < ?");
            $stmt->execute([$item['product_id'], $item['quantity']]);
            
            $stmt=$pdo->prepare("UPDATE products SET stock=stock-? WHERE product_id=? AND stock >= ?");
            $stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
            
            // Check if this item has a specific variant_id
            if (isset($item['variant_id']) && $item['variant_id'] > 0) {
                // Update the specific variant's stock
                $stmt = $pdo->prepare("UPDATE product_variants SET stock = GREATEST(0, stock - ?) WHERE variant_id = ? AND is_active = 1");
                $stmt->execute([$item['quantity'], $item['variant_id']]);
                
                // Log the variant stock update
                error_log("Updated variant ID {$item['variant_id']} stock for product {$item['product_id']}, reduced by {$item['quantity']}");
            } 
            // If no specific variant_id but product has variants, update based on available stock
            else {
                // Check if this product has variants
                $stmt=$pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id=? AND is_active=1");
                $stmt->execute([$item['product_id']]);
                $hasVariants = $stmt->fetchColumn() > 0;
                
                if ($hasVariants) {
                    // Get all active variants
                    $stmt=$pdo->prepare("SELECT variant_id, stock FROM product_variants WHERE product_id=? AND is_active=1 ORDER BY stock DESC");
                    $stmt->execute([$item['product_id']]);
                    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($variants)) {
                        $remainingQuantity = $item['quantity'];
                        
                        // First pass: Set variants with insufficient stock to 0
                        foreach ($variants as $variant) {
                            if ($variant['stock'] < $remainingQuantity) {
                                $stmt=$pdo->prepare("UPDATE product_variants SET stock=0 WHERE variant_id=?");
                                $stmt->execute([$variant['variant_id']]);
                            }
                        }
                        
                        // Second pass: Deduct from variants with sufficient stock
                        foreach ($variants as $variant) {
                            if ($variant['stock'] >= $remainingQuantity && $remainingQuantity > 0) {
                                $stmt=$pdo->prepare("UPDATE product_variants SET stock=stock-? WHERE variant_id=?");
                                $stmt->execute([$remainingQuantity, $variant['variant_id']]);
                                $remainingQuantity = 0;
                                break;
                            }
                        }
                        
                        // If there's still remaining quantity, distribute it proportionally
                        if ($remainingQuantity > 0) {
                            // Set all variants to 0 as we couldn't fulfill the order with any single variant
                            $stmt=$pdo->prepare("UPDATE product_variants SET stock=0 WHERE product_id=? AND is_active=1");
                            $stmt->execute([$item['product_id']]);
                        }
                    }
                }
            }
        }

        $cashier_name='Admin User';
        try{
            $stmt=$pdo->prepare("SELECT username FROM users WHERE user_id=?");
            $stmt->execute([$_SESSION['user_id']]);
            $user=$stmt->fetch(PDO::FETCH_ASSOC);
            if($user && $user['username']) $cashier_name=$user['username'];
        }catch(Exception $e){}

        $receipt_data=[
            'sale_number'=>$sale_number,
            'sale_date'=>date('Y-m-d H:i:s'),
            'cashier_name'=>$cashier_name,
            'items'=>$cart,
            'subtotal'=>$subtotal,
            'tax_amount'=>$tax_amount,
            'total_amount'=>$total_amount,
            'amount_payment'=>$amount_payment,
            'change_amount'=>$change_amount,
            'payment_method'=>$payment_method
        ];

        $_SESSION['pos_cart']=[];

        $pdo->commit();

        return ['success'=>true,'message'=>'Sale completed successfully','sale_id'=>$sale_id,'sale_number'=>$sale_number,'total_amount'=>$total_amount,'change_amount'=>$change_amount,'receipt_data'=>$receipt_data];

    }catch(Exception $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        return ['success'=>false,'message'=>'Sale failed: '.$e->getMessage()];
    }
}

// =======================
// HOLD TRANSACTION FUNCTIONS
// =======================
function holdTransaction($pdo) {
    try {
        $pdo->beginTransaction();
        if (!isset($_SESSION['pos_cart']) || empty($_SESSION['pos_cart'])) {
            throw new Exception('Cart is empty');
        }
        $customer_name = $_POST['customer_name'] ?? 'Walk-in Customer';
        $notes = $_POST['notes'] ?? '';
        $subtotal = 0;
        foreach ($_SESSION['pos_cart'] as $item) $subtotal += $item['total_price'];
        $tax_rate = 0.01; // Changed from 12% to 1%
        $tax_amount = $subtotal * $tax_rate;
        $total_amount = $subtotal + $tax_amount;
        $hold_number = 'HOLD-' . date('Y-m-d') . '-' . time();
        $stmt = $pdo->prepare("
            INSERT INTO pos_held_transactions (
                hold_number, cashier_id, customer_name, subtotal, tax_amount, total_amount, notes, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $stmt->execute([
            $hold_number,
            $_SESSION['user_id'],
            $customer_name,
            $subtotal,
            $tax_amount,
            $total_amount,
            $notes,
            $expires_at
        ]);
        $hold_id = $pdo->lastInsertId();
        foreach ($_SESSION['pos_cart'] as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO pos_held_items (
                    hold_id, product_id, product_name, quantity, unit_price, total_price
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $hold_id,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price']
            ]);
        }
        $_SESSION['pos_cart'] = [];
        $pdo->commit();
        return [
            'success' => true,
            'message' => 'Transaction held successfully',
            'hold_number' => $hold_number
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Hold failed: ' . $e->getMessage()];
    }
}

function getHeldTransactions($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT h.*, u.username as cashier_username 
            FROM pos_held_transactions h 
            LEFT JOIN users u ON h.cashier_id = u.user_id 
            WHERE h.expires_at > NOW() 
            ORDER BY h.created_at DESC
        ");
        $stmt->execute();
        $held_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'held_transactions' => $held_transactions];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error fetching held transactions: ' . $e->getMessage()];
    }
}

function recallHeldTransaction($pdo) {
    try {
        $hold_id = (int)$_POST['hold_id'];
        $stmt = $pdo->prepare("
            SELECT hi.* FROM pos_held_items hi 
            JOIN pos_held_transactions h ON hi.hold_id = h.hold_id 
            WHERE h.hold_id = ? AND h.expires_at > NOW()
        ");
        $stmt->execute([$hold_id]);
        $held_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($held_items)) {
            return ['success' => false, 'message' => 'Held transaction not found or expired'];
        }
        $_SESSION['pos_cart'] = [];
        foreach ($held_items as $item) {
            $_SESSION['pos_cart'][] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
                'image' => 'default.jpg'
            ];
        }
        $stmt = $pdo->prepare("DELETE FROM pos_held_transactions WHERE hold_id = ?");
        $stmt->execute([$hold_id]);
        return [
            'success' => true,
            'message' => 'Transaction recalled successfully',
            'cart' => $_SESSION['pos_cart']
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error recalling transaction: ' . $e->getMessage()];
    }
}

// =======================
// POS SETTINGS & VOID FUNCTIONS
// =======================
function getPosSettings($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM pos_settings");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return ['success' => true, 'settings' => $settings];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error fetching settings: ' . $e->getMessage()];
    }
}

function voidSale($pdo) {
    try {
        $sale_id = (int)$_POST['sale_id'];
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT * FROM pos_sale_items WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $sale_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sale_items as $item) {
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?");
            $stmt->execute([$item['quantity'],$item['product_id']]);
        }
        $stmt = $pdo->prepare("UPDATE pos_sales SET status = 'voided' WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $pdo->commit();
        return ['success' => true, 'message' => 'Sale voided successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Error voiding sale: ' . $e->getMessage()];
    }
}

// =======================
// SALES HISTORY & RECEIPT
// =======================
function getSalesHistory($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.sale_id,
                s.sale_number,
                s.sale_date,
                s.total_amount,
                s.payment_method,
                s.status,
                u.username as cashier_name
            FROM pos_sales s
            LEFT JOIN users u ON s.cashier_id = u.user_id
            WHERE s.status != 'voided'
            ORDER BY s.sale_date DESC
            LIMIT 100
        ");
        $stmt->execute();
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['success' => true, 'sales' => $sales];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error fetching sales history: ' . $e->getMessage()];
    }
}

function getSaleReceipt($pdo) {
    try {
        $sale_id = (int)$_POST['sale_id'];
        $stmt = $pdo->prepare("
            SELECT s.*, u.username as cashier_name
            FROM pos_sales s
            LEFT JOIN users u ON s.cashier_id = u.user_id
            WHERE s.sale_id = ?
        ");
        $stmt->execute([$sale_id]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sale) throw new Exception('Sale not found');
        $stmt = $pdo->prepare("SELECT * FROM pos_sale_items WHERE sale_id = ?");
        $stmt->execute([$sale_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $receipt_data = [
            'sale'=>$sale,
            'items'=>$items
        ];
        return ['success' => true, 'receipt_data' => $receipt_data];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error fetching receipt: ' . $e->getMessage()];
    }
}

// =======================
// HELPER FUNCTION
// =======================
function generateSaleNumber($pdo){
    $prefix='POS-'.date('Y-m-d').'-';
    $stmt=$pdo->prepare("SELECT sale_number FROM pos_sales WHERE sale_number LIKE ? ORDER BY sale_id DESC LIMIT 1");
    $stmt->execute([$prefix.'%']);
    $last_number=$stmt->fetchColumn();
    $sequence=$last_number ? ((int)substr($last_number,-4)+1) : 1;
    return $prefix.str_pad($sequence,4,'0',STR_PAD_LEFT);
}
?>


