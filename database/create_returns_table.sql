-- ============================================
-- CREATE RETURNS/REFUNDS TABLE
-- For managing product return and refund requests
-- ============================================

CREATE TABLE IF NOT EXISTS return_requests (
    return_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    return_status ENUM('Pending', 'Approved', 'Rejected', 'Completed') DEFAULT 'Pending',
    admin_notes TEXT NULL,
    refund_amount DECIMAL(10,2) NULL,
    images TEXT NULL, -- JSON array of image paths
    customer_refund_method VARCHAR(50) NULL, -- Customer's preferred refund method
    customer_payment_details TEXT NULL, -- Customer's GCash number, bank account, etc.
    refund_method VARCHAR(50) NULL, -- Actual method used by admin
    refund_reference VARCHAR(255) NULL, -- Transaction reference number
    refund_proof TEXT NULL, -- Image proof of refund payment
    refunded_at TIMESTAMP NULL, -- When refund was sent
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_by INT NULL, -- admin user_id who processed the request
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_user (user_id),
    INDEX idx_status (return_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
