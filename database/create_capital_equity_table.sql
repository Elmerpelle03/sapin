-- ============================================
-- CAPITAL & EQUITY TABLE FOR BUSINESS FINANCIALS
-- ============================================

CREATE TABLE IF NOT EXISTS capital_equity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('initial_capital', 'additional_investment', 'withdrawal', 'profit_distribution', 'retained_earnings') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_capital_date (transaction_date),
    INDEX idx_capital_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert initial capital (you can adjust this amount)
INSERT INTO capital_equity (transaction_date, transaction_type, description, amount, created_by) 
VALUES (CURDATE(), 'initial_capital', 'Initial Business Capital', 1000000.00, 1)
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Create a view for current capital position
CREATE OR REPLACE VIEW current_capital_position AS
SELECT 
    transaction_type,
    SUM(CASE 
        WHEN transaction_type IN ('initial_capital', 'additional_investment', 'retained_earnings') 
        THEN amount 
        ELSE 0 
    END) as total_capital_injected,
    SUM(CASE 
        WHEN transaction_type IN ('withdrawal', 'profit_distribution') 
        THEN amount 
        ELSE 0 
    END) as total_withdrawals,
    SUM(CASE 
        WHEN transaction_type = 'retained_earnings' 
        THEN amount 
        ELSE 0 
    END) as retained_earnings
FROM capital_equity 
GROUP BY transaction_type;
