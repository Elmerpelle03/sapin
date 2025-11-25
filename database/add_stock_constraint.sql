-- Add constraint to prevent negative stock values
-- This is a safety net to prevent overselling even if race conditions occur

-- First, check if any products have negative stock (shouldn't happen, but let's be safe)
SELECT product_id, product_name, stock 
FROM products 
WHERE stock < 0;

-- If any negative stocks exist, you should fix them first:
-- UPDATE products SET stock = 0 WHERE stock < 0;

-- Add CHECK constraint to prevent negative stock
-- Note: MySQL 8.0.16+ supports CHECK constraints
ALTER TABLE products 
ADD CONSTRAINT chk_stock_non_negative 
CHECK (stock >= 0);

-- Verify the constraint was added
SHOW CREATE TABLE products;
