# Troubleshooting Guide - Proof of Payment Upload

## Issue: "Unexpected error. Something went wrong. Please try again later."

### Quick Fixes:

#### **Option 1: Run the Database Migration (Recommended)**
This enables all security features.

1. Open phpMyAdmin
2. Select your database
3. Go to SQL tab
4. Copy and paste the contents of `database/add_proof_security_columns.sql`
5. Click "Go" to execute
6. Try uploading again

#### **Option 2: Check Debug Log**
The system creates detailed error logs:

1. Look for file: `c:\xampp\htdocs\sapinbedsheets-main\debug_checkout.log`
2. Open it to see the exact error
3. Share the error message for specific help

#### **Option 3: Check PHP Extensions**
Some security features require PHP extensions:

1. Open `php.ini` (usually in `C:\xampp\php\php.ini`)
2. Find and uncomment (remove `;` from start):
   ```ini
   extension=gd
   extension=exif
   extension=fileinfo
   ```
3. Restart Apache
4. Try again

#### **Option 4: Check Folder Permissions**
The upload folder needs write permissions:

1. Navigate to: `c:\xampp\htdocs\sapinbedsheets-main\uploads\`
2. Create folder `proofs` if it doesn't exist
3. Right-click → Properties → Security
4. Ensure "Users" has "Write" permission
5. Try again

---

## Common Error Messages:

### "File size too large. Maximum 5MB allowed."
**Solution:** Compress your image or use a smaller file.

### "Invalid file type. Only JPG, PNG, and GIF images are allowed."
**Solution:** Convert your image to JPG, PNG, or GIF format.

### "This proof of payment has already been used for another order."
**Solution:** Upload a new, unique receipt. Don't reuse old screenshots.

### "Proof of payment is required for [payment method]."
**Solution:** Make sure you selected a file before clicking submit.

---

## Testing the Upload:

### Test with a simple image:
1. Take a screenshot (any screenshot)
2. Save as JPG
3. Try uploading
4. If it works, the issue was with your original image

### Check file size:
- Right-click image → Properties
- Should be under 5MB (5,000 KB)

### Check file format:
- File should end in `.jpg`, `.jpeg`, `.png`, or `.gif`
- Not `.webp`, `.bmp`, `.pdf`, etc.

---

## Still Not Working?

### Check the debug log:
```
Location: c:\xampp\htdocs\sapinbedsheets-main\debug_checkout.log
```

Look for lines like:
- "CRITICAL ERROR in proof upload: ..."
- "File upload error code: ..."
- "Watermark error: ..."

### Common Issues:

1. **GD Library not installed**
   - Error: "Call to undefined function imagecreatefromjpeg()"
   - Fix: Enable GD extension in php.ini

2. **EXIF extension not installed**
   - Error: "Call to undefined function exif_read_data()"
   - Fix: Enable EXIF extension in php.ini

3. **Upload directory doesn't exist**
   - Error: "Failed to upload proof of payment"
   - Fix: Create `uploads/proofs/` folder manually

4. **Database column missing**
   - Error: "Unknown column 'image_hash'"
   - Fix: Run the SQL migration file

---

## Temporary Workaround (If Nothing Works):

If you need to accept orders immediately while troubleshooting:

1. Open `backend/checkout.php`
2. Find line ~72: `$stmt = $pdo->prepare("SELECT order_id FROM orders WHERE image_hash = :hash");`
3. Comment it out (add `//` at start)
4. This disables duplicate detection but allows uploads

**Remember to re-enable it later!**

---

## Contact Support

If none of these solutions work, provide:
1. Contents of `debug_checkout.log`
2. PHP version (`php -v` in command prompt)
3. Screenshot of the error
4. File size and format of image you're trying to upload
