-- Add security columns to orders table for proof of payment validation
-- Run this SQL to enable advanced anti-scam features
-- This version is safe to run multiple times (checks if columns exist first)

-- Add column to store image hash for duplicate detection
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'image_hash');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `orders` ADD COLUMN `image_hash` VARCHAR(32) NULL AFTER `proof_of_payment`, ADD INDEX `idx_image_hash` (`image_hash`)',
    'SELECT "Column image_hash already exists" AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add column to store proof metadata (EXIF, upload time, file info)
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'proof_metadata');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `orders` ADD COLUMN `proof_metadata` TEXT NULL AFTER `image_hash`',
    'SELECT "Column proof_metadata already exists" AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add column to flag suspicious orders for manual review
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'requires_verification');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `orders` ADD COLUMN `requires_verification` TINYINT(1) DEFAULT 0 AFTER `proof_metadata`',
    'SELECT "Column requires_verification already exists" AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add verification notes column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'verification_notes');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `orders` ADD COLUMN `verification_notes` TEXT NULL AFTER `requires_verification`',
    'SELECT "Column verification_notes already exists" AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add column to track admin who verified the proof
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'verified_by');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `orders` ADD COLUMN `verified_by` INT NULL AFTER `verification_notes`',
    'SELECT "Column verified_by already exists" AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add verification timestamp column
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'verified_at');
SET @sqlstmt := IF(@exist = 0, 
    'ALTER TABLE `orders` ADD COLUMN `verified_at` DATETIME NULL AFTER `verified_by`',
    'SELECT "Column verified_at already exists" AS message');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Show final status
SELECT 'All security columns have been added or already exist!' AS Status;
