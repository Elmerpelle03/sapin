-- Create table for material restock requests
-- This allows staff to request material restocking without knowing suppliers

CREATE TABLE IF NOT EXISTS material_restock_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    current_stock DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255) DEFAULT 'Low stock',
    requested_by VARCHAR(100) NOT NULL,
    requested_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'ordered', 'received', 'rejected') DEFAULT 'pending',
    owner_notes TEXT,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_material (material_id),
    INDEX idx_requested_date (requested_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add a column to track who last restocked (optional)
ALTER TABLE materials 
ADD COLUMN last_restock_date DATETIME,
ADD COLUMN last_restock_by VARCHAR(100),
ADD COLUMN last_restock_amount DECIMAL(10,2);
