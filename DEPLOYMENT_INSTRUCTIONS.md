# 🚀 Deployment Instructions for Hostinger

## Quick Fix for Blank Admin Page

Your admin page is showing blank because of one or more of these issues:
1. Database connection problems
2. Missing database tables
3. PHP errors not being displayed
4. Missing files or incorrect permissions

---

## 🔧 Immediate Fix Steps

### Step 1: Upload the Fix Script
1. Upload `admin/fix_deployment.php` to your Hostinger server
2. Visit: `https://sapinbedsheets.com/admin/fix_deployment.php`
3. The script will automatically check and fix common issues

### Step 2: Run Diagnostics
1. Visit: `https://sapinbedsheets.com/admin/debug.php`
2. Check what errors are showing
3. Follow the recommendations

### Step 3: Check Database
1. Log into Hostinger cPanel
2. Go to **phpMyAdmin**
3. Select database: `u119634533_sapinbedsheets`
4. Verify these tables exist:
   - users
   - usertype
   - orders
   - products
   - materials
   - visitors
   - expenses
   - pos_sales

**If tables are missing:**
- Import your database SQL file from the `database/` folder
- Use phpMyAdmin > Import > Choose file > Go

---

## 📋 Complete Deployment Checklist

### Before Upload

- [ ] Export your local database
- [ ] Update `config/db.production.php` with correct credentials
- [ ] Test locally with production database settings
- [ ] Create a backup of current production site (if exists)

### Files to Upload

Upload these folders/files to Hostinger:
```
✓ admin/          (all admin files)
✓ assets/         (CSS, JS, images)
✓ auth/           (login/logout)
✓ config/         (database config)
✓ database/       (SQL files)
✓ includes/       (shared components)
✓ logs/           (error logs - create empty folder)
✓ uploads/        (user uploads - create empty folder)
✓ .htaccess       (server config)
✓ index.php       (homepage)
```

### After Upload

1. **Set File Permissions** (via File Manager or FTP):
   ```
   Directories: 755
   Files: 644
   logs/: 777
   uploads/: 777
   ```

2. **Import Database**:
   - cPanel > phpMyAdmin
   - Select your database
   - Import > Choose your .sql file
   - Click "Go"

3. **Configure PHP Settings**:
   - cPanel > Select PHP Version
   - Choose PHP 7.4 or 8.0
   - Enable extensions:
     - pdo
     - pdo_mysql
     - mysqli
     - mbstring
     - json
     - session
     - gd

4. **Run Fix Script**:
   - Visit: `https://yourdomain.com/admin/fix_deployment.php`
   - Follow any recommendations

5. **Test Login**:
   - Go to: `https://yourdomain.com/auth/login.php`
   - Login with your admin credentials
   - Check if dashboard loads correctly

---

## 🔍 Troubleshooting Common Issues

### Issue 1: "Connection failed" Error

**Cause:** Database credentials are wrong or database doesn't exist

**Fix:**
1. Check `config/db.production.php` credentials
2. Verify database exists in cPanel > MySQL Databases
3. Ensure database user has all privileges
4. Test connection using `admin/debug.php`

### Issue 2: Blank Page (No Errors)

**Cause:** PHP errors are hidden

**Fix:**
1. Check `.htaccess` has error display enabled
2. Check PHP error logs in cPanel
3. Enable error display in cPanel > Select PHP Version > Options
4. Run `admin/fix_deployment.php`

### Issue 3: "Table doesn't exist" Error

**Cause:** Database tables not imported

**Fix:**
1. Go to cPanel > phpMyAdmin
2. Select your database
3. Import your SQL file from `database/` folder
4. Verify all tables are created

### Issue 4: Session/Login Issues

**Cause:** Session not working or user not logged in

**Fix:**
1. Clear browser cookies and cache
2. Check session path in PHP settings
3. Ensure `session` extension is enabled
4. Try logging in again

### Issue 5: CSS/JS Not Loading

**Cause:** File paths are incorrect or files missing

**Fix:**
1. Check browser console (F12) for 404 errors
2. Verify all files in `admin/css/` and `admin/js/` are uploaded
3. Check file permissions (should be 644)
4. Clear browser cache

---

## 🗄️ Database Configuration

### Production Database Details
```php
Host: localhost
Database: u119634533_sapinbedsheets
Username: u119634533_sapinbedsheets
Password: AicellDEC_ROBLES200325
```

### Create Database (if not exists)
1. cPanel > MySQL Databases
2. Create Database: `u119634533_sapinbedsheets`
3. Create User: `u119634533_sapinbedsheets`
4. Set Password: `AicellDEC_ROBLES200325`
5. Add User to Database with ALL PRIVILEGES

---

## 🔐 Security Recommendations

### After Everything Works:

1. **Disable Error Display**
   Edit `.htaccess`:
   ```apache
   php_flag display_errors Off
   ```

2. **Delete Debug Files**
   ```
   admin/debug.php
   admin/fix_deployment.php
   admin/test_db.php (if exists)
   ```

3. **Secure Config Files**
   Ensure `config/` directory has proper permissions (755)

4. **Enable HTTPS**
   - Get SSL certificate from Hostinger (usually free)
   - Force HTTPS in `.htaccess`

5. **Change Default Passwords**
   - Update database password
   - Change admin user passwords

---

## 📞 Getting Help

### If Issues Persist:

1. **Check Error Logs**:
   - cPanel > Error Logs
   - Or check: `/logs/php_errors.log`

2. **Run Diagnostics**:
   - `admin/debug.php` - System diagnostics
   - `admin/fix_deployment.php` - Auto-fix script

3. **Contact Hostinger Support**:
   - Provide error messages
   - Share debug.php output
   - Include screenshots

4. **Check Documentation**:
   - See `DEPLOYMENT_TROUBLESHOOTING.md` for detailed solutions

---

## ✅ Verification Steps

After deployment, verify these work:

- [ ] Can access homepage: `https://yourdomain.com`
- [ ] Can login: `https://yourdomain.com/auth/login.php`
- [ ] Admin dashboard loads: `https://yourdomain.com/admin/index.php`
- [ ] Can view orders: `https://yourdomain.com/admin/orders.php`
- [ ] Can view products: `https://yourdomain.com/admin/products.php`
- [ ] Can view materials: `https://yourdomain.com/admin/materialinventory.php`
- [ ] POS system works: `https://yourdomain.com/admin/pos.php`
- [ ] Reports generate: `https://yourdomain.com/admin/reports.php`

---

## 🎯 Quick Reference

### Important URLs
- Homepage: `https://sapinbedsheets.com`
- Admin Login: `https://sapinbedsheets.com/auth/login.php`
- Admin Dashboard: `https://sapinbedsheets.com/admin/index.php`
- Debug Script: `https://sapinbedsheets.com/admin/debug.php`
- Fix Script: `https://sapinbedsheets.com/admin/fix_deployment.php`

### Important Files
- Database Config: `config/db.production.php`
- Session Handler: `config/session_admin.php`
- Server Config: `.htaccess`
- Error Logs: `logs/php_errors.log`

### Hostinger cPanel Sections
- **File Manager**: Upload/manage files
- **phpMyAdmin**: Database management
- **MySQL Databases**: Create/manage databases
- **Select PHP Version**: PHP settings & extensions
- **Error Logs**: View PHP errors

---

**Last Updated:** October 17, 2025
**Version:** 1.0
