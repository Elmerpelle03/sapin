-- ============================================
-- ADD REFUND PAYMENT COLUMNS TO EXISTING return_requests TABLE
-- Run this if you already created the return_requests table
-- ============================================

ALTER TABLE return_requests 
ADD COLUMN IF NOT EXISTS customer_refund_method VARCHAR(50) NULL COMMENT 'Customer preferred refund method' AFTER images,
ADD COLUMN IF NOT EXISTS customer_payment_details TEXT NULL COMMENT 'Customer GCash number, bank account, etc.' AFTER customer_refund_method,
ADD COLUMN IF NOT EXISTS refund_method VARCHAR(50) NULL COMMENT 'Actual method used by admin' AFTER customer_payment_details,
ADD COLUMN IF NOT EXISTS refund_reference VARCHAR(255) NULL COMMENT 'Transaction reference number' AFTER refund_method,
ADD COLUMN IF NOT EXISTS refund_proof TEXT NULL COMMENT 'Image proof of refund payment' AFTER refund_reference,
ADD COLUMN IF NOT EXISTS refunded_at TIMESTAMP NULL COMMENT 'When refund was sent' AFTER refund_proof;
