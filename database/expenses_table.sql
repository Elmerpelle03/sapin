-- Create expenses table for tracking business expenses
CREATE TABLE IF NOT EXISTS `expenses` (
  `expense_id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_category` varchar(100) NOT NULL,
  `expense_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`expense_id`),
  KEY `expense_category` (`expense_category`),
  KEY `expense_date` (`expense_date`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `fk_expenses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert sample expense categories (optional)
INSERT INTO `expenses` (`expense_category`, `expense_name`, `amount`, `expense_date`, `description`, `created_by`) VALUES
('Materials', 'Cotton Fabric Purchase', 5000.00, '2024-01-15', 'Bulk purchase of cotton fabric for bedsheets', 1),
('Utilities', 'Electricity Bill - January', 3500.00, '2024-01-31', 'Monthly electricity bill', 1),
('Salaries', 'Staff Salaries - January', 25000.00, '2024-01-31', 'Monthly salaries for production staff', 1),
('Rent', 'Workshop Rent - January', 15000.00, '2024-01-05', 'Monthly rent for production workshop', 1),
('Transportation', 'Delivery Expenses', 2500.00, '2024-01-20', 'Fuel and vehicle maintenance', 1),
('Marketing', 'Facebook Ads Campaign', 3000.00, '2024-01-10', 'Social media advertising', 1),
('Miscellaneous', 'Office Supplies', 800.00, '2024-01-12', 'Pens, papers, and other supplies', 1);
