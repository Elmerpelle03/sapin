# Deployment Troubleshooting Guide - Hostinger

## Issue: Blank Admin Page After Upload

### Symptoms
- Admin page loads but shows only sidebar
- Main content area is blank/dark
- No error messages displayed

---

## Common Causes & Solutions

### 1. Database Connection Issues ⚠️

**Problem:** Database credentials are incorrect or database doesn't exist on Hostinger.

**Solution:**
1. Log into Hostinger cPanel
2. Go to **MySQL Databases**
3. Verify database name, username, and password match `config/db.production.php`:
   - Database: `u119634533_sapinbedsheets`
   - Username: `u119634533_sapinbedsheets`
   - Password: `AicellDEC_ROBLES200325`

4. If database doesn't exist, create it and import your SQL file:
   ```
   - Create database: u119634533_sapinbedsheets
   - Create user: u119634533_sapinbedsheets
   - Grant all privileges
   - Import database.sql via phpMyAdmin
   ```

**Test:** Visit `https://yourdomain.com/admin/debug.php` to check connection status.

---

### 2. Missing Database Tables 🗄️

**Problem:** Required tables don't exist in production database.

**Solution:**
1. Access phpMyAdmin in Hostinger cPanel
2. Select your database: `u119634533_sapinbedsheets`
3. Import your database dump file
4. Verify these tables exist:
   - `users`
   - `usertype`
   - `orders`
   - `products`
   - `materials`
   - `visitors` (if missing, run the SQL below)

**Create visitors table if missing:**
```sql
CREATE TABLE IF NOT EXISTS `visitors` (
  `visitor_id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `visit_time` datetime NOT NULL,
  PRIMARY KEY (`visitor_id`),
  KEY `idx_ip_date` (`ip_address`, `visit_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### 3. PHP Version Compatibility 🐘

**Problem:** Hostinger PHP version is incompatible.

**Solution:**
1. In Hostinger cPanel, go to **Select PHP Version**
2. Set to **PHP 7.4** or **PHP 8.0** (recommended)
3. Enable required extensions:
   - ✓ pdo
   - ✓ pdo_mysql
   - ✓ mysqli
   - ✓ mbstring
   - ✓ json
   - ✓ session

---

### 4. File Permissions 📁

**Problem:** PHP cannot read/write files.

**Solution:**
Set correct permissions via Hostinger File Manager or FTP:
```
Directories: 755
Files: 644
config/ directory: 755
logs/ directory: 777 (writable)
uploads/ directory: 777 (writable)
```

---

### 5. Session Issues 🔐

**Problem:** Session not starting or user not logged in.

**Solution:**
1. Ensure you're logged in as admin
2. Clear browser cookies and cache
3. Try logging in again at: `https://yourdomain.com/auth/login.php`
4. Check session path in PHP settings (cPanel > Select PHP Version > Options)

---

### 6. Missing Files 📄

**Problem:** Required files weren't uploaded.

**Checklist - Ensure these files exist on Hostinger:**
```
✓ /config/db.php
✓ /config/db.production.php
✓ /config/session_admin.php
✓ /includes/sidebar_admin.php
✓ /includes/navbar_admin.php
✓ /admin/index.php
✓ /admin/css/app.css
✓ /admin/js/ (all JS files)
✓ /.htaccess
```

---

### 7. .htaccess Issues ⚙️

**Problem:** Server configuration blocking PHP execution.

**Solution:**
Ensure `.htaccess` file exists in root directory with:
```apache
php_flag display_errors On
php_value error_reporting E_ALL
```

---

## Diagnostic Steps

### Step 1: Run Debug Script
Visit: `https://sapinbedsheets.com/admin/debug.php`

This will show:
- PHP version
- File existence checks
- Database connection status
- Table checks
- Session information

### Step 2: Check PHP Error Logs
1. In Hostinger cPanel, go to **Error Logs**
2. Look for recent errors
3. Or check: `/logs/php_errors.log`

### Step 3: Check Browser Console
1. Open browser Developer Tools (F12)
2. Check Console tab for JavaScript errors
3. Check Network tab for failed requests

### Step 4: Test Database Connection
Create `test_db.php` in admin folder:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$dbname = 'u119634533_sapinbedsheets';
$username = 'u119634533_sapinbedsheets';
$password = 'AicellDEC_ROBLES200325';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    echo "✓ Connection successful!";
} catch (PDOException $e) {
    echo "✗ Connection failed: " . $e->getMessage();
}
?>
```

---

## Quick Fixes

### If you see "Connection failed" error:
1. Double-check database credentials in `config/db.production.php`
2. Ensure database exists in Hostinger cPanel
3. Verify user has proper privileges

### If you see blank page with no errors:
1. Enable error display in `.htaccess`
2. Check PHP error logs in cPanel
3. Run `debug.php` script

### If sidebar shows but content is blank:
1. Check browser console for JavaScript errors
2. Verify CSS files are loading (check Network tab)
3. Check if database queries are failing

---

## After Fixing

Once the issue is resolved:

1. **Disable error display** in `.htaccess`:
   ```apache
   php_flag display_errors Off
   ```

2. **Keep error logging enabled**:
   ```apache
   php_flag log_errors On
   ```

3. **Delete debug files**:
   - `admin/debug.php`
   - `admin/test_db.php`

4. **Test all functionality**:
   - Login/logout
   - View orders
   - Add products
   - Check reports

---

## Contact Support

If issues persist:
1. Check Hostinger Knowledge Base
2. Contact Hostinger Support with:
   - Error messages from debug.php
   - PHP error log contents
   - Screenshots of the issue

---

## Prevention

To avoid issues in future deployments:

1. **Always test locally first** with production database credentials
2. **Export database** before making changes
3. **Use version control** (Git) to track changes
4. **Keep backups** of working versions
5. **Document changes** in deployment notes

---

**Last Updated:** October 17, 2025
**Version:** 1.0
