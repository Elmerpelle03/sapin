# Hostinger Deployment Guide

## Problem: DEFINER Error (#1227)

When uploading your SQL dump to Hostinger, you get this error:
```
#1227 - Access denied; you need (at least one of) the SET USER privilege(s) for this operation
```

This happens because your SQL dump contains `DEFINER='root'@'localhost'` clauses in views, stored procedures, triggers, or events. Hostinger's shared hosting doesn't allow setting definers.

---

## Solution Methods

### Method 1: Clean Export from Localhost (RECOMMENDED)

**Steps:**

1. **Open phpMyAdmin on localhost**
2. **Select your database**
3. **Go to Export tab**
4. **Choose "Custom" export method**
5. **Scroll down to "Object creation options"**
6. **Find and UNCHECK these options:**
   - "Add CREATE PROCEDURE / FUNCTION / EVENT"
   - Any option mentioning "DEFINER"
7. **Export the database**
8. **Upload the new SQL file to Hostinger**

This creates a clean SQL dump without DEFINER clauses.

---

### Method 2: Edit Existing SQL File

If you already have an SQL dump file:

**Option A: Using Text Editor (Find & Replace)**

1. Open your SQL dump file in a text editor (Notepad++, VS Code, etc.)
2. Find and replace:
   - **Find:** `DEFINER=`root`@`localhost``
   - **Replace:** (leave empty)
3. Also find and replace:
   - **Find:** `DEFINER='root'@'localhost'`
   - **Replace:** (leave empty)
4. Save the file
5. Upload to Hostinger

**Option B: Using Command Line (Linux/Mac)**

```bash
sed -i 's/DEFINER=[^ ]*//g' your_database_dump.sql
```

**Option C: Using PowerShell (Windows)**

```powershell
(Get-Content your_database_dump.sql) -replace "DEFINER=\`root\`@\`localhost\`", "" | Set-Content your_database_dump_fixed.sql
```

---

### Method 3: Fix After Import (If Already Uploaded)

If you already imported the database and got the error:

1. **Upload and run the fix script:**
   - Use the file: `database/HOSTINGER_FIX_remove_definers.sql`
   - This recreates the `pos_detailed_sales` view without DEFINER

2. **In Hostinger phpMyAdmin:**
   - Go to SQL tab
   - Copy and paste the contents of `HOSTINGER_FIX_remove_definers.sql`
   - Click "Go"

---

## Additional Hostinger Considerations

### 1. Database User Credentials

Update your config/connection files with Hostinger credentials:

```php
// Localhost
$host = "localhost";
$username = "root";
$password = "";
$database = "your_database";

// Hostinger (example)
$host = "localhost";  // or specific hostname from Hostinger
$username = "u123456789_dbuser";  // from Hostinger control panel
$password = "your_hostinger_password";
$database = "u123456789_dbname";
```

### 2. File Paths

- Change absolute paths to relative paths
- Use `$_SERVER['DOCUMENT_ROOT']` instead of hardcoded paths like `C:\xampp\htdocs\`

### 3. PHP Version

- Check Hostinger's PHP version matches your localhost
- Adjust in Hostinger control panel if needed

### 4. File Permissions

- Ensure upload directories have write permissions (755 or 775)
- Check `.htaccess` files are uploaded

### 5. Error Reporting

Temporarily enable error reporting to debug issues:

```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

**Remember to disable this in production!**

---

## Checklist for Deployment

- [ ] Export database without DEFINER clauses
- [ ] Update database credentials in config files
- [ ] Upload all files via FTP/File Manager
- [ ] Import SQL file in Hostinger phpMyAdmin
- [ ] Run any additional SQL scripts (materials, products, etc.)
- [ ] Test all functionalities:
  - [ ] Login/Authentication
  - [ ] POS System
  - [ ] Inventory Management
  - [ ] Reports/Analytics
  - [ ] User Management
- [ ] Check file upload features work
- [ ] Verify database views and triggers work
- [ ] Test all CRUD operations

---

## Common Hostinger Issues

### Issue: "Table doesn't exist"
- **Cause:** SQL import failed partially
- **Fix:** Drop all tables and re-import clean SQL dump

### Issue: "Connection refused"
- **Cause:** Wrong database credentials
- **Fix:** Double-check credentials from Hostinger control panel

### Issue: "File upload not working"
- **Cause:** Directory permissions
- **Fix:** Set upload directories to 755 or 775

### Issue: "Session not working"
- **Cause:** Session path not writable
- **Fix:** Use `session_save_path()` to set writable directory

---

## Support

If you continue having issues:

1. Check Hostinger's error logs (in control panel)
2. Enable PHP error reporting temporarily
3. Check browser console for JavaScript errors
4. Verify all database tables were created successfully
5. Test database connection separately

---

**Created:** October 2025  
**For:** Sapin Bedsheets POS System
