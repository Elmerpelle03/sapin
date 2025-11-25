-- ============================================
-- WHOLESALER FEATURES - DATABASE TABLES
-- ============================================

-- 1. Add discount_rate column to users table
ALTER TABLE users 
ADD COLUMN discount_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Discount percentage for wholesalers (e.g., 10.00 for 10%)';

-- 2. Create bulk_buyer_messages table
CREATE TABLE IF NOT EXISTS `bulk_buyer_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `sender_type` enum('buyer','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  KEY `user_id` (`user_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `bulk_buyer_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create quote_requests table
CREATE TABLE IF NOT EXISTS `quote_requests` (
  `quote_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('Pending','Quoted','Approved','Declined') DEFAULT 'Pending',
  `admin_quote_price` decimal(10,2) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`quote_id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `status` (`status`),
  CONSTRAINT `quote_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `quote_requests_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Set default 10% discount for existing wholesalers (usertype_id = 3)
UPDATE users 
SET discount_rate = 10.00 
WHERE usertype_id = 3 AND discount_rate = 0.00;

-- 5. Create indexes for better performance
CREATE INDEX idx_messages_user_read ON bulk_buyer_messages(user_id, is_read);
CREATE INDEX idx_quotes_user_status ON quote_requests(user_id, status);

-- ============================================
-- VERIFICATION QUERIES (Run these to check)
-- ============================================

-- Check if discount_rate column was added
-- SELECT user_id, username, usertype_id, discount_rate FROM users WHERE usertype_id = 3;

-- Check messages table
-- SELECT * FROM bulk_buyer_messages LIMIT 5;

-- Check quote_requests table
-- SELECT * FROM quote_requests LIMIT 5;
