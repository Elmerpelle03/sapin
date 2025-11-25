# Hostinger Database Migration Guide

## Problem Identified
Your Hostinger database is **missing critical tables**:
- ❌ `return_requests` table
- ❌ `notifications` table
- ❌ Possibly other tables

This is why you're seeing limited data - the tables simply don't exist on Hostinger!

## Solution: Two Options

### Option 1: Quick Fix - Create Missing Tables Only (Recommended for Quick Fix)

If you want to keep your existing Hostinger data and just add the missing tables:

#### Step 1: Run These SQL Files on Hostinger

**Via Hostinger phpMyAdmin:**
1. Log into Hostinger control panel
2. Go to **phpMyAdmin**
3. Select database: `u119634533_sapin_bedsheet`
4. Click **SQL** tab
5. Copy and paste the content from each file below, one at a time:

**File 1: Create Notifications Table**
```sql
-- ============================================
-- CREATE NOTIFICATIONS TABLE
-- For user notifications about order updates
-- ============================================

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**File 2: Create Return Requests Table**
```sql
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
    images TEXT NULL,
    customer_refund_method VARCHAR(50) NULL,
    customer_payment_details TEXT NULL,
    refund_method VARCHAR(50) NULL,
    refund_reference VARCHAR(255) NULL,
    refund_proof TEXT NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_user (user_id),
    INDEX idx_status (return_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Step 2: Verify Tables Created
Run this query in phpMyAdmin:
```sql
SHOW TABLES LIKE '%return%';
SHOW TABLES LIKE '%notification%';
```

You should see both tables listed.

---

### Option 2: Complete Database Export/Import (Recommended for Full Sync)

If you want to completely sync your localhost database to Hostinger:

#### Step 1: Export Database from Localhost

**Using phpMyAdmin (Localhost):**
1. Open `http://localhost/phpmyadmin`
2. Select database: `sapinbedsheets`
3. Click **Export** tab
4. Choose **Custom** method
5. **IMPORTANT Settings:**
   - Format: SQL
   - ✅ Check "Add DROP TABLE"
   - ✅ Check "IF NOT EXISTS"
   - ✅ Check "Add CREATE DATABASE"
   - ✅ Uncheck "Add CREATE PROCEDURE/FUNCTION/EVENT"
   - ✅ Data: Check "Complete inserts"
6. Click **Go** to download the SQL file

**Using Command Line (Alternative):**
```bash
cd C:\xampp\mysql\bin
mysqldump -u root -p sapinbedsheets > C:\xampp\htdocs\sapinbedsheets-main\database\full_export.sql
```

#### Step 2: Prepare SQL File for Hostinger

Open the exported SQL file and:

1. **Remove DEFINER statements** (if any):
   - Find and replace: `DEFINER=`root`@`localhost`` with nothing (empty)
   - Or run the file: `database/HOSTINGER_FIX_remove_definers.sql` on the export

2. **Change database name** (if needed):
   - Find: `CREATE DATABASE IF NOT EXISTS `sapinbedsheets``
   - Replace with: `-- CREATE DATABASE IF NOT EXISTS `sapinbedsheets``
   - Or just delete that line

#### Step 3: Import to Hostinger

**Method A: Via phpMyAdmin (Recommended for files < 50MB)**
1. Log into Hostinger control panel
2. Go to **phpMyAdmin**
3. Select database: `u119634533_sapin_bedsheet`
4. Click **Import** tab
5. Choose your SQL file
6. Click **Go**
7. Wait for import to complete

**Method B: Via Hostinger File Manager + MySQL (For large files)**
1. Upload SQL file to Hostinger via File Manager
2. Use Hostinger's MySQL Import tool in control panel
3. Or use SSH if available:
   ```bash
   mysql -u u119634533_sapin_bedsheet -p u119634533_sapin_bedsheet < full_export.sql
   ```

#### Step 4: Verify Import

Run this query in Hostinger phpMyAdmin:
```sql
-- Check all tables exist
SHOW TABLES;

-- Check row counts
SELECT 'orders' as table_name, COUNT(*) as rows FROM orders
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'users', COUNT(*) FROM users
UNION ALL
SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL
SELECT 'return_requests', COUNT(*) FROM return_requests;
```

Compare counts with your localhost database.

---

## After Migration: Test Again

1. **Re-run the diagnostic test:**
   - Visit: `https://yourdomain.com/admin/test_data_fetch.php`
   - All tests should now show ✓ green checkmarks

2. **Check Admin Panel:**
   - All sidebar items should be visible
   - Notifications should work
   - Returns/Refunds page should load

3. **Clean Up:**
   - Delete `admin/test_data_fetch.php` from server

---

## Common Issues & Solutions

### Issue 1: "Table already exists" Error
**Solution:** Add `DROP TABLE IF EXISTS` before CREATE TABLE statements

### Issue 2: Foreign Key Constraint Fails
**Solution:** 
- Import tables in correct order (parent tables first)
- Or temporarily disable foreign key checks:
```sql
SET FOREIGN_KEY_CHECKS = 0;
-- Your import here
SET FOREIGN_KEY_CHECKS = 1;
```

### Issue 3: Import File Too Large
**Solutions:**
- Split the SQL file into smaller parts
- Use Hostinger's SSH access (if available)
- Use BigDump tool for large imports
- Increase PHP upload limits in Hostinger control panel

### Issue 4: Character Encoding Issues
**Solution:** Ensure UTF-8 encoding:
```sql
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
```

---

## Quick Checklist

- [ ] Export localhost database
- [ ] Remove DEFINER statements (if any)
- [ ] Import to Hostinger phpMyAdmin
- [ ] Verify all tables exist
- [ ] Run diagnostic test
- [ ] Test admin panel functionality
- [ ] Delete test files

---

## Files You Already Have

Your `database` folder contains these helpful SQL files:
- ✅ `create_notifications_table.sql` - Creates notifications table
- ✅ `create_returns_table.sql` - Creates return_requests table
- ✅ `HOSTINGER_FIX_remove_definers.sql` - Removes DEFINER statements
- ✅ `VERIFY_DATABASE_HOSTINGER.sql` - Verification queries

---

## Need Help?

If you encounter errors during import:
1. Copy the exact error message
2. Check which line number failed
3. Look for table dependencies
4. Ensure all required tables exist first

**Pro Tip:** Start with Option 1 (Quick Fix) if you just need the missing tables. Use Option 2 (Full Export/Import) if you want complete data synchronization.
