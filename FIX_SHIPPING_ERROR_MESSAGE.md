# Fix: Shipping Rule Error Messages

## Problem 1: "Unexpected Error" Message
When adding a shipping rule, you see "Unexpected error occurred" message, but the rule is actually added successfully.

## Problem 2: "Invalid Parameter Number" Error
After fixing Problem 1, the actual error appeared: **"SQLSTATE[HY093]: Invalid parameter number"**

This happens because empty string values for optional fields (province, municipality, barangay) cause parameter binding issues.

## Root Causes

### Issue 1: Generic Error Message
The `add_rule.php` file had a generic error message in the catch block that hid the real error.

### Issue 2: Empty String vs NULL
When optional location fields are left empty, the form sends empty strings `""` instead of `NULL`. PDO's parameter binding doesn't handle this properly, causing the "Invalid parameter number" error

## Solutions Applied

### Fix 1: Show Actual Error Messages

**Updated error handling in catch block:**
```php
catch (PDOException $e) {
    error_log("Shipping rule add error: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
}
```

This revealed the real error: "Invalid parameter number"

### Fix 2: Handle Empty Strings Properly

**Convert empty strings to NULL early in the process:**
```php
// Convert empty strings to NULL for optional fields (do this early)
$province_id = !empty($province_id) ? $province_id : null;
$municipality_id = !empty($municipality_id) ? $municipality_id : null;
$barangay_id = !empty($barangay_id) ? $barangay_id : null;
```

**Use execute() with array instead of bindParam():**
```php
$result = $stmt->execute([
    ':rule_name' => $rule_name,
    ':shipping_fee' => $fee,
    ':region_id' => $region_id,
    ':province_id' => $province_id,
    ':municipality_id' => $municipality_id,
    ':barangay_id' => $barangay_id
]);

if ($result) {
    $_SESSION['success_message'] = "Shipping rule added successfully.";
} else {
    $_SESSION['error_message'] = "Failed to add shipping rule.";
}
```

## What Changed

1. ✅ Converts empty strings to NULL for optional fields
2. ✅ Uses `execute()` with array instead of `bindParam()` for better NULL handling
3. ✅ Shows **actual database error** instead of generic message
4. ✅ Logs errors for debugging
5. ✅ Proper success/error message handling

## Upload These Files

Upload the updated files to Hostinger:
- **`admin/backend/add_rule.php`** (fixed parameter binding)
- **`admin/backend/edit_rule.php`** (same fix applied)

## Result

After uploading:
- ✅ Success message shows when rule is added successfully
- ✅ Actual error message shows if there's a real problem
- ✅ No more misleading "Unexpected error" when operation succeeds

## Testing

1. Add a new shipping rule
2. You should see: **"Shipping rule added successfully"** (green)
3. Try adding a duplicate rule
4. You should see: **"A shipping rule with the same location already exists"** (red)
5. If there's a real database error, you'll see the actual error message

---

**Status:** ✅ Fixed
**File Updated:** `admin/backend/add_rule.php`
