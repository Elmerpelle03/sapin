# Hostinger Deployment Checklist

## Pre-Deployment Preparation

### 1. Fix SQL Export (CRITICAL)
- [ ] **Option A:** Export fresh from phpMyAdmin without DEFINER
  - Open phpMyAdmin on localhost
  - Select database
  - Export → Custom → Uncheck DEFINER options
  
- [ ] **Option B:** Use the automated script
  - Double-click `remove_definers.bat`
  - Drag your SQL file
  - Use the generated `*_hostinger_ready.sql` file

- [ ] **Option C:** Manual find & replace
  - Open SQL file in text editor
  - Find: `DEFINER=`root`@`localhost``
  - Replace with: (empty)
  - Save

### 2. Verify Database Credentials
Your production config is already set:
```
Host: localhost
Database: u119634533_sapin_bedsheet
Username: u119634533_sapin_bedsheet
Password: F9!ge&aUquj
```

- [ ] Verify these credentials match your Hostinger control panel
- [ ] Update `config/db.production.php` if credentials changed

### 3. Prepare Files
- [ ] Remove any local-only files (test files, debug scripts)
- [ ] Check `.htaccess` files are included
- [ ] Verify upload directories exist

---

## Deployment Steps

### Step 1: Upload Files
- [ ] Connect via FTP or use Hostinger File Manager
- [ ] Upload all files to `public_html` directory
- [ ] Preserve directory structure
- [ ] Verify all files uploaded successfully

### Step 2: Import Database
- [ ] Go to Hostinger phpMyAdmin
- [ ] Select your database: `u119634533_sapin_bedsheet`
- [ ] Click "Import" tab
- [ ] Choose your cleaned SQL file (without DEFINER)
- [ ] Click "Go"
- [ ] Wait for import to complete

**If you get DEFINER error:**
- [ ] Run `database/HOSTINGER_FIX_remove_definers.sql` in phpMyAdmin SQL tab

### Step 3: Import Additional SQL Files (in order)
Run these SQL files in Hostinger phpMyAdmin:

1. [ ] `database/materials_products_sales_core.sql` (if not in main dump)
2. [ ] `database/insert_product_materials.sql`
3. [ ] `database/create_notifications_table.sql`
4. [ ] `database/create_returns_table.sql`
5. [ ] `database/expenses_table.sql`
6. [ ] `database/forecast_tables.sql`
7. [ ] Any other custom SQL files you've created

### Step 4: Set File Permissions
- [ ] Set upload directories to 755 or 775:
  - `uploads/`
  - `admin/uploads/`
  - Any other upload directories

---

## Post-Deployment Testing

### Basic Functionality
- [ ] **Homepage loads correctly**
- [ ] **No PHP errors displayed**
- [ ] **CSS/JS files loading**
- [ ] **Images displaying**

### Authentication
- [ ] **Login page accessible**
- [ ] **Can login with admin account**
- [ ] **Session persists across pages**
- [ ] **Logout works**

### POS System
- [ ] **POS interface loads**
- [ ] **Can search products**
- [ ] **Can add items to cart**
- [ ] **Can process sale**
- [ ] **Receipt generates**
- [ ] **Sale recorded in database**

### Inventory Management
- [ ] **Product list displays**
- [ ] **Can add new product**
- [ ] **Can edit product**
- [ ] **Can delete product**
- [ ] **Stock levels update correctly**
- [ ] **Low stock alerts work**

### Materials Management
- [ ] **Materials list displays**
- [ ] **Can add new material**
- [ ] **Can edit material**
- [ ] **Material-product links work**
- [ ] **Material consumption calculates correctly**

### Reports & Analytics
- [ ] **Sales reports generate**
- [ ] **Date filters work**
- [ ] **Export to Excel/PDF works**
- [ ] **Charts/graphs display**
- [ ] **Inventory reports work**

### User Management
- [ ] **User list displays**
- [ ] **Can create new user**
- [ ] **Can edit user**
- [ ] **Can change user role**
- [ ] **User permissions work correctly**

### Notifications
- [ ] **Notifications display**
- [ ] **Low stock notifications trigger**
- [ ] **Can mark as read**
- [ ] **Notification count updates**

### Returns/Refunds
- [ ] **Returns page accessible**
- [ ] **Can process return**
- [ ] **Stock updates on return**
- [ ] **Refund recorded correctly**

### File Uploads
- [ ] **Product image upload works**
- [ ] **Images display correctly**
- [ ] **File size limits respected**

---

## Troubleshooting

### Issue: White Screen / 500 Error
**Check:**
- [ ] PHP error logs in Hostinger control panel
- [ ] Enable error reporting temporarily:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- [ ] Check file permissions
- [ ] Verify PHP version compatibility

### Issue: Database Connection Failed
**Check:**
- [ ] Database credentials in `config/db.production.php`
- [ ] Database exists in Hostinger
- [ ] Database user has correct permissions

### Issue: CSS/JS Not Loading
**Check:**
- [ ] File paths are relative, not absolute
- [ ] Files uploaded to correct directories
- [ ] Check browser console for 404 errors
- [ ] Clear browser cache

### Issue: Session Not Working
**Check:**
- [ ] Session directory is writable
- [ ] PHP session settings in Hostinger
- [ ] Cookie settings in code

### Issue: File Upload Not Working
**Check:**
- [ ] Upload directory exists
- [ ] Directory permissions (755 or 775)
- [ ] PHP upload_max_filesize setting
- [ ] PHP post_max_size setting

### Issue: Views/Triggers Not Working
**Check:**
- [ ] All views created successfully
- [ ] No DEFINER errors in import log
- [ ] Run `database/HOSTINGER_FIX_remove_definers.sql`

---

## Performance Optimization (Optional)

- [ ] Enable PHP OPcache in Hostinger
- [ ] Optimize images (compress large files)
- [ ] Minify CSS/JS files
- [ ] Enable browser caching via .htaccess
- [ ] Add database indexes if queries are slow

---

## Security Checklist

- [ ] **Disable error display in production**
  ```php
  error_reporting(0);
  ini_set('display_errors', 0);
  ```
- [ ] **Remove test/debug files**
- [ ] **Change default admin password**
- [ ] **Verify SQL injection protection (PDO prepared statements)**
- [ ] **Check XSS protection in forms**
- [ ] **Verify file upload validation**
- [ ] **Enable HTTPS (SSL certificate)**
- [ ] **Set secure session settings**

---

## Backup Strategy

- [ ] **Setup automatic database backups in Hostinger**
- [ ] **Keep local copy of database**
- [ ] **Document backup schedule**
- [ ] **Test restore procedure**

---

## Support Resources

### Hostinger Resources
- Control Panel: https://hpanel.hostinger.com
- Knowledge Base: https://support.hostinger.com
- Live Chat Support: Available 24/7

### Your Files
- `QUICK_FIX_HOSTINGER.txt` - Quick reference
- `HOSTINGER_DEPLOYMENT_GUIDE.md` - Detailed guide
- `remove_definers.bat` - Automated DEFINER remover
- `database/HOSTINGER_FIX_remove_definers.sql` - SQL fix script

---

## Completion Sign-Off

Deployment completed on: _______________

Deployed by: _______________

All tests passed: [ ] YES [ ] NO

Issues encountered: _______________________________________________

_______________________________________________________________

_______________________________________________________________

Notes: _______________________________________________________________

_______________________________________________________________

_______________________________________________________________

---

**Last Updated:** October 2025  
**System:** Sapin Bedsheets POS & Inventory Management
