# Fix: Missing cancellation_requests Table

## Problem
Fatal error: Table 'cancellation_requests' doesn't exist

## Solution

### Option 1: Create Table via phpMyAdmin (Recommended)

1. **Login to Hostinger cPanel**
2. **Open phpMyAdmin**
3. **Select your database**: `u119634533_sapinbedsheets`
4. **Click "SQL" tab**
5. **Copy and paste this SQL code:**

```sql
CREATE TABLE IF NOT EXISTS `cancellation_requests` (
    `cancellation_id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `admin_response` TEXT NULL,
    `admin_id` INT NULL,
    `requested_at` DATETIME NOT NULL,
    `responded_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_order_id` (`order_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

6. **Click "Go"**
7. **Refresh your admin page** - it should now work!

### Option 2: Import SQL File

1. **Upload** `database/HOSTINGER_create_cancellation_table.sql` to your server
2. **In phpMyAdmin**, click **Import** tab
3. **Choose file**: Select the SQL file
4. **Click "Go"**

### Option 3: Use the Fixed Sidebar (Already Done)

I've already updated `includes/sidebar_admin.php` to handle the missing table gracefully. 

**Upload the updated file to Hostinger** and your admin page will work even without the cancellation_requests table (it will just show 0 cancellations).

## Files Updated

✅ **includes/sidebar_admin.php** - Now handles missing table without crashing
✅ **database/HOSTINGER_create_cancellation_table.sql** - SQL to create the table

## What to Upload to Hostinger

1. **includes/sidebar_admin.php** (updated with error handling)
2. **database/HOSTINGER_create_cancellation_table.sql** (optional, for creating table)

## After Fix

Once you upload the updated sidebar file, your admin page should load immediately, even if the table doesn't exist yet.

Then you can create the table later when you need the cancellation feature.
