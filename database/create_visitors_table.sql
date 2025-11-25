-- ============================================
-- CREATE VISITORS TABLE
-- For tracking website visitors
-- ============================================

CREATE TABLE IF NOT EXISTS visitors (
    visitor_id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_date (ip_address, visit_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
