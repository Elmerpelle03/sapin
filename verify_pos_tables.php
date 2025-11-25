<?php
// Set environment variables for CLI usage
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

try {
    require_once 'config/db.php';
    
    echo "Checking POS database tables...\n";
    
    // Check required tables
    $requiredTables = [
        'pos_sales',
        'pos_sale_items', 
        'pos_settings',
        'pos_held_transactions',
        'pos_held_items'
    ];
    
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ $table table exists\n";
        } else {
            echo "✗ $table table missing - creating...\n";
            
            // Create missing tables
            switch ($table) {
                case 'pos_sales':
                    $pdo->exec("CREATE TABLE pos_sales (
                        sale_id INT AUTO_INCREMENT PRIMARY KEY,
                        sale_number VARCHAR(50) UNIQUE NOT NULL,
                        cashier_id INT NOT NULL,
                        customer_name VARCHAR(255) DEFAULT 'Walk-in Customer',
                        customer_phone VARCHAR(20),
                        subtotal DECIMAL(10,2) NOT NULL,
                        tax_amount DECIMAL(10,2) DEFAULT 0,
                        discount_amount DECIMAL(10,2) DEFAULT 0,
                        total_amount DECIMAL(10,2) NOT NULL,
                        amount_paid DECIMAL(10,2) NOT NULL,
                        change_amount DECIMAL(10,2) DEFAULT 0,
                        payment_method ENUM('cash', 'card', 'gcash', 'bank_transfer') DEFAULT 'cash',
                        status ENUM('completed', 'voided') DEFAULT 'completed',
                        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (cashier_id) REFERENCES users(user_id)
                    )");
                    echo "  + Created pos_sales table\n";
                    break;
                    
                case 'pos_sale_items':
                    $pdo->exec("CREATE TABLE pos_sale_items (
                        item_id INT AUTO_INCREMENT PRIMARY KEY,
                        sale_id INT NOT NULL,
                        product_id INT NOT NULL,
                        product_name VARCHAR(255) NOT NULL,
                        quantity INT NOT NULL,
                        unit_price DECIMAL(10,2) NOT NULL,
                        total_price DECIMAL(10,2) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (sale_id) REFERENCES pos_sales(sale_id) ON DELETE CASCADE,
                        FOREIGN KEY (product_id) REFERENCES products(product_id)
                    )");
                    echo "  + Created pos_sale_items table\n";
                    break;
                    
                case 'pos_settings':
                    $pdo->exec("CREATE TABLE pos_settings (
                        setting_id INT AUTO_INCREMENT PRIMARY KEY,
                        setting_key VARCHAR(100) UNIQUE NOT NULL,
                        setting_value TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                    
                    // Insert default settings
                    $defaults = [
                        ['tax_rate', '0.12'],
                        ['currency', 'PHP'],
                        ['store_name', 'Sapin Bedsheets'],
                        ['receipt_footer', 'Thank you for your purchase!']
                    ];
                    
                    $stmt = $pdo->prepare("INSERT INTO pos_settings (setting_key, setting_value) VALUES (?, ?)");
                    foreach ($defaults as $setting) {
                        $stmt->execute($setting);
                    }
                    echo "  + Created pos_settings table with defaults\n";
                    break;
                    
                case 'pos_held_transactions':
                    $pdo->exec("CREATE TABLE pos_held_transactions (
                        hold_id INT AUTO_INCREMENT PRIMARY KEY,
                        hold_number VARCHAR(50) UNIQUE NOT NULL,
                        cashier_id INT NOT NULL,
                        customer_name VARCHAR(255) NOT NULL,
                        subtotal DECIMAL(10,2) NOT NULL,
                        tax_amount DECIMAL(10,2) DEFAULT 0,
                        total_amount DECIMAL(10,2) NOT NULL,
                        notes TEXT,
                        expires_at TIMESTAMP NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (cashier_id) REFERENCES users(user_id)
                    )");
                    echo "  + Created pos_held_transactions table\n";
                    break;
                    
                case 'pos_held_items':
                    $pdo->exec("CREATE TABLE pos_held_items (
                        item_id INT AUTO_INCREMENT PRIMARY KEY,
                        hold_id INT NOT NULL,
                        product_id INT NOT NULL,
                        product_name VARCHAR(255) NOT NULL,
                        quantity INT NOT NULL,
                        unit_price DECIMAL(10,2) NOT NULL,
                        total_price DECIMAL(10,2) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (hold_id) REFERENCES pos_held_transactions(hold_id) ON DELETE CASCADE,
                        FOREIGN KEY (product_id) REFERENCES products(product_id)
                    )");
                    echo "  + Created pos_held_items table\n";
                    break;
            }
        }
    }
    
    echo "\nAll POS tables are ready!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
