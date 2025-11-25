# Fix: Returns/Refunds Page Deprecation Warnings

## Problem
PHP deprecation warnings appearing on the Returns/Refunds page:

```
Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) 
of type string is deprecated in /home/u119634533/domains/sapinbedsheets.com/
public_html/admin/returns.php on line 393

Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) 
of type string is deprecated in /home/u119634533/domains/sapinbedsheets.com/
public_html/admin/returns.php on line 394
```

## Root Cause
The code was using `htmlspecialchars()` on database fields that could be NULL:
- `customer_refund_method` - Can be NULL if customer hasn't specified
- `customer_payment_details` - Can be NULL if customer hasn't provided details

In PHP 8.1+, passing NULL to `htmlspecialchars()` triggers a deprecation warning.

## Solution

### Before (Lines 393-394):
```php
<span class="badge bg-primary mb-2"><?= htmlspecialchars($return['customer_refund_method']) ?></span>
<p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($return['customer_payment_details'])) ?></p>
```

### After (Fixed):
```php
<span class="badge bg-primary mb-2"><?= htmlspecialchars($return['customer_refund_method'] ?? 'Not specified') ?></span>
<p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($return['customer_payment_details'] ?? 'No details provided')) ?></p>
```

### Also Fixed in Modal (Lines 491-492):
```php
<span class="badge bg-dark"><?= htmlspecialchars($return['customer_refund_method'] ?? 'Not specified') ?></span><br>
<small class="mt-1 d-block"><?= nl2br(htmlspecialchars($return['customer_payment_details'] ?? 'No details provided')) ?></small>
```

## What Changed

Used the **null coalescing operator (`??`)** to provide default values:
- If `customer_refund_method` is NULL → Show "Not specified"
- If `customer_payment_details` is NULL → Show "No details provided"

This ensures `htmlspecialchars()` always receives a string, never NULL.

## Files Updated

✅ **`admin/returns.php`**
- Fixed 4 instances of `htmlspecialchars()` with NULL values
- Lines 393, 394, 491, 492

## Benefits

✅ No more deprecation warnings
✅ Cleaner display when customer hasn't provided payment details
✅ PHP 8.1+ compatible
✅ Better user experience with meaningful default text

## Upload This File

Upload to Hostinger:
- **`admin/returns.php`** (fixed deprecation warnings)

## Testing

1. **View a return request** that has payment details
   - Should display normally
   - No warnings

2. **View a return request** without payment details (NULL values)
   - Should show "Not specified" and "No details provided"
   - No warnings

3. **Check page source** (Ctrl+U)
   - Should see no deprecation warnings
   - Clean HTML output

---

**Status:** ✅ Fixed
**Files Updated:** 1 file
**PHP Compatibility:** PHP 8.1+ ready
