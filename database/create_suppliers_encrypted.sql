-- Create suppliers table with encrypted contact information
-- Owner can add suppliers, but contact details are encrypted

CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_type ENUM('mobile', 'email', 'both') NOT NULL,
    -- Encrypted fields (you won't see the actual values)
    encrypted_mobile TEXT,
    encrypted_email TEXT,
    -- Public info (visible to all)
    company_name VARCHAR(255),
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Link materials to suppliers
CREATE TABLE IF NOT EXISTS material_suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    supplier_id INT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    notes VARCHAR(255),
    FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE CASCADE,
    UNIQUE KEY unique_material_supplier (material_id, supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Restock requests to suppliers
CREATE TABLE IF NOT EXISTS supplier_restock_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    supplier_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    message TEXT,
    sent_via ENUM('mobile', 'email') NOT NULL,
    sent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'confirmed', 'delivered', 'cancelled') DEFAULT 'sent',
    expected_delivery DATE,
    actual_delivery DATE,
    notes TEXT,
    created_by VARCHAR(100),
    FOREIGN KEY (material_id) REFERENCES materials(material_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id),
    INDEX idx_status (status),
    INDEX idx_sent_date (sent_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
