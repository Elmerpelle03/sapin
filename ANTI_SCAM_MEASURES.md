# Anti-Scam Measures for Proof of Payment

## Overview
This document outlines the comprehensive security measures implemented to prevent fraudulent proof of payment submissions in the checkout system.

---

## 🔒 Implemented Security Features

### 1. **File Validation**
- ✅ **File Size Limit**: Maximum 5MB to prevent abuse
- ✅ **MIME Type Validation**: Only accepts JPG, PNG, GIF images
- ✅ **Image Integrity Check**: Verifies the file is a valid, non-corrupted image
- ✅ **Extension Sanitization**: Prevents malicious file uploads

**Protection Against:**
- Malware/virus uploads
- Fake file extensions
- Corrupted files
- Server resource abuse

---

### 2. **Duplicate Detection**
- ✅ **Image Hash Generation**: Creates MD5 fingerprint of each uploaded image
- ✅ **Database Check**: Prevents reusing the same receipt for multiple orders
- ✅ **Automatic Rejection**: Blocks duplicate submissions with clear error message

**Protection Against:**
- Reusing old receipts
- Multiple orders with same proof
- Copy-paste fraud

**How it Works:**
```php
$image_hash = md5_file($file['tmp_name']);
// Check if hash exists in database
if (duplicate found) {
    reject_upload();
}
```

---

### 3. **Watermarking System**
- ✅ **Automatic Watermark**: Adds order ID, user ID, and timestamp to image
- ✅ **Semi-Transparent Overlay**: Visible but doesn't obscure important details
- ✅ **Bottom-Right Placement**: Professional positioning
- ✅ **Prevents Reuse**: Makes it obvious if someone tries to reuse the image

**Watermark Contains:**
- Order number (unique identifier)
- User ID
- Upload timestamp
- Semi-transparent background

**Protection Against:**
- Image reuse by other customers
- Screenshot sharing fraud
- External misuse of receipts

---

### 4. **EXIF Metadata Extraction**
- ✅ **Capture Device Info**: Records camera/phone model if available
- ✅ **Original Timestamp**: Extracts when photo was actually taken
- ✅ **File Dimensions**: Records image size
- ✅ **Upload Time**: Logs exact upload timestamp

**Stored Metadata:**
```json
{
    "upload_time": "2025-10-13 22:30:00",
    "file_size": 245678,
    "dimensions": "1080x1920",
    "mime_type": "image/jpeg",
    "exif_datetime": "2025-10-13 22:25:00",
    "exif_make": "Apple",
    "exif_model": "iPhone 12",
    "image_hash": "a1b2c3d4e5f6..."
}
```

**Protection Against:**
- Edited/photoshopped images (EXIF stripped)
- Screenshots (different metadata pattern)
- Old receipts (timestamp mismatch)

---

### 5. **Secure File Storage**
- ✅ **Unique Filenames**: `proof_[userID]_[timestamp]_[unique_id].[ext]`
- ✅ **Organized Directory**: `/uploads/proofs/`
- ✅ **Permission Control**: Proper folder permissions (0777)
- ✅ **Path Sanitization**: Prevents directory traversal attacks

---

## 📊 Database Schema

### New Columns Added to `orders` Table:

| Column | Type | Purpose |
|--------|------|---------|
| `image_hash` | VARCHAR(32) | MD5 hash for duplicate detection |
| `proof_metadata` | TEXT | JSON metadata (EXIF, timestamps, etc.) |
| `requires_verification` | TINYINT(1) | Flag suspicious orders |
| `verification_notes` | TEXT | Admin notes during review |
| `verified_by` | INT | Admin user ID who verified |
| `verified_at` | DATETIME | Verification timestamp |

**To enable these features, run:**
```sql
-- Execute the SQL file
source database/add_proof_security_columns.sql;
```

---

## 🎯 Additional Recommendations

### For Admins:
1. **Manual Verification Process**
   - Review high-value orders (e.g., >₱5,000)
   - Check metadata for suspicious patterns
   - Verify timestamp matches order time
   - Cross-reference with bank records

2. **Red Flags to Watch For:**
   - ⚠️ Missing EXIF data (likely edited/screenshot)
   - ⚠️ Timestamp significantly older than order date
   - ⚠️ Very small file size (possible screenshot)
   - ⚠️ Generic device info or missing device data
   - ⚠️ Multiple orders from same user in short time
   - ⚠️ Amount mismatch between proof and order

3. **Verification Workflow:**
   ```
   Order Placed → Auto-checks Pass → Admin Review (if flagged) → Approve/Reject
   ```

### For Future Enhancements:
1. **AI Image Analysis**
   - Detect photoshopped images
   - OCR to extract amount from receipt
   - Compare extracted amount with order total

2. **Bank API Integration**
   - Real-time verification with GCash/Bank APIs
   - Automatic confirmation of transactions
   - Instant payment validation

3. **User Trust Score**
   - Track successful orders
   - Flag suspicious behavior patterns
   - Require additional verification for new users

4. **Time-Based Validation**
   - Require proof within X hours of order
   - Flag receipts with old timestamps
   - Auto-expire unverified orders

5. **Reference Number Validation**
   - Require users to input reference number
   - Cross-check with proof image
   - Validate format (GCash: 13 digits, etc.)

---

## 🚨 Handling Suspicious Orders

### Automatic Flags:
The system automatically flags orders that:
- Have no EXIF metadata
- Show timestamp mismatch (>24 hours difference)
- Come from users with previous rejected orders
- Have unusually large order amounts

### Manual Review Process:
1. Admin receives notification of flagged order
2. Reviews proof image and metadata
3. Checks bank/GCash records if needed
4. Approves or rejects with notes
5. System logs verification details

---

## 📝 User Communication

### Error Messages:
- **Duplicate Image**: "This proof of payment has already been used for another order. Please upload a unique receipt."
- **Invalid File**: "Invalid file type. Only JPG, PNG, and GIF images are allowed."
- **File Too Large**: "File size too large. Maximum 5MB allowed."
- **Corrupted Image**: "Invalid or corrupted image file."

### Best Practices to Share with Users:
1. ✅ Take clear, well-lit photos of receipts
2. ✅ Ensure all details are visible (amount, date, reference number)
3. ✅ Upload immediately after payment
4. ✅ Keep original receipt for verification
5. ❌ Don't edit or crop important information
6. ❌ Don't reuse old receipts
7. ❌ Don't upload screenshots of receipts

---

## 🔧 Technical Implementation

### Files Modified:
- `backend/checkout.php` - Main security implementation
- `database/add_proof_security_columns.sql` - Database schema

### Key Functions:
1. **`addWatermarkToProof()`** - Adds watermark to uploaded images
2. **File validation checks** - MIME type, size, integrity
3. **Duplicate detection** - Hash comparison
4. **Metadata extraction** - EXIF data capture

### Dependencies:
- PHP GD Library (for image manipulation)
- PHP EXIF extension (for metadata extraction)
- MySQL 5.7+ (for JSON support)

---

## 📈 Monitoring & Analytics

### Metrics to Track:
- Number of rejected proofs (by reason)
- Average verification time
- Duplicate detection rate
- User resubmission rate
- Fraud attempt patterns

### Recommended Dashboard:
```
Orders Pending Verification: 5
Flagged for Review: 2
Rejected Today: 1
Average Verification Time: 15 minutes
```

---

## 🎓 Training for Staff

### Admin Training Checklist:
- [ ] How to review proof of payment
- [ ] Understanding metadata fields
- [ ] Identifying red flags
- [ ] Using verification tools
- [ ] Contacting customers for clarification
- [ ] Documenting verification decisions

---

## ⚖️ Legal Considerations

### Terms of Service Updates:
Add clauses about:
- Proof of payment requirements
- Consequences of fraudulent submissions
- Right to verify and request additional proof
- Account suspension for repeated violations

### Privacy Notice:
Inform users that:
- Uploaded images are stored securely
- Metadata is collected for verification
- Images may be reviewed by staff
- Data retention policy

---

## 🔄 Maintenance

### Regular Tasks:
- **Weekly**: Review flagged orders
- **Monthly**: Analyze fraud patterns
- **Quarterly**: Update security measures
- **Yearly**: Audit entire system

### Backup Strategy:
- Keep proof images for at least 1 year
- Regular database backups
- Secure storage of sensitive data

---

## 📞 Support

For questions or issues:
1. Check admin panel for verification tools
2. Review order metadata in database
3. Contact technical support if needed

---

**Last Updated**: October 13, 2025
**Version**: 1.0
**Status**: Active
