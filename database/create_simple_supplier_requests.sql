-- Simple supplier restock requests
-- Admin can request materials from suppliers

CREATE TABLE IF NOT EXISTS material_supplier_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    current_stock DECIMAL(10,2) NOT NULL,
    supplier_contact VARCHAR(255),  -- Mobile or Email
    contact_type ENUM('mobile', 'email') NOT NULL,
    message TEXT,
    requested_by VARCHAR(100) NOT NULL,
    requested_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'sent', 'delivered') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_date (requested_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
