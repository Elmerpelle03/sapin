# Feature: Stock Status Badges in Shopping Cart

## Overview
Added visual stock status badges to the shopping cart to make it immediately obvious when products are out of stock or running low. Uses the actual `restock_alert` threshold from the database instead of hardcoded values.

## Changes Made

### ✅ **Updated Cart Query**
Added `restock_alert` to the product data fetched for cart items.

### ✅ **Improved Badge Logic**
Changed from hardcoded `5` to actual `restock_alert` value per product.

### ✅ **Better Visual Hierarchy**
Added spacing (`ms-2`) to badges for better readability.

## Badge Display Logic

### 🔴 **OUT OF STOCK Badge**
```
Condition: stock <= 0
Display: Red badge with "OUT OF STOCK"
Icon: X-circle-fill
```

### 🟡 **LOW STOCK Badge**
```
Condition: stock > 0 AND stock <= restock_alert
Display: Yellow badge with "LOW STOCK"
Icon: Exclamation-circle
```

### 🔴 **EXCEEDS STOCK Badge**
```
Condition: quantity > stock
Display: Red badge with "Exceeds by X"
Icon: Exclamation-triangle
```

### ✅ **Normal Stock**
```
Condition: stock > restock_alert
Display: Just stock count (e.g., "515 in stock")
No badge
```

## Visual Examples

### Before Fix:
```
Product: Bedsheet
Stock: 8 units
Restock Alert: 20

Display: "8 in stock" (no badge)
❌ WRONG - Should show LOW STOCK!
```

### After Fix:
```
Product: Bedsheet
Stock: 8 units
Restock Alert: 20

Display: "8 in stock [LOW STOCK]"
✅ CORRECT - Shows yellow badge!
```

## All Scenarios

### Scenario 1: Out of Stock
```
Stock: 0
Display: [OUT OF STOCK] (red badge)
User can't proceed to checkout
```

### Scenario 2: Low Stock (Below Restock Alert)
```
Stock: 10
Restock Alert: 20
Display: "10 in stock [LOW STOCK]" (yellow badge)
User warned but can still checkout
```

### Scenario 3: Normal Stock
```
Stock: 100
Restock Alert: 20
Display: "100 in stock"
No badge - plenty of stock
```

### Scenario 4: Quantity Exceeds Stock
```
Stock: 5
Cart Quantity: 10
Display: "5 in stock [Exceeds by 5]" (red badge)
User must reduce quantity
```

### Scenario 5: Low Stock + Quantity OK
```
Stock: 8
Restock Alert: 20
Cart Quantity: 3
Display: "8 in stock [LOW STOCK]" (yellow badge)
User can checkout but warned
```

## Files Modified

### **`cart.php`**

**Line ~1385:** Added `restock_alert` to SELECT query
```php
SELECT 
    cart.cart_id,
    products.product_name,
    products.stock,
    products.restock_alert,  // NEW!
    ...
```

**Line ~1609:** Updated badge logic
```php
// BEFORE (hardcoded 5):
<?php elseif ($row['stock'] <= 5): ?>
    <span class="badge bg-warning text-dark">
        Low stock
    </span>

// AFTER (uses actual restock_alert):
<?php elseif ($row['stock'] <= $row['restock_alert']): ?>
    <span class="badge bg-warning text-dark ms-2">
        <i class="bi bi-exclamation-circle me-1"></i>
        LOW STOCK
    </span>
```

## Benefits

### ⚠️ **Clear Visual Warnings**
- Customers immediately see stock issues
- No need to read numbers carefully
- Color-coded for quick scanning

### 🎯 **Accurate Thresholds**
- Uses actual restock_alert per product
- Product A: alert = 10
- Product B: alert = 50
- Each shows correct badge

### 💡 **Better User Experience**
- Customers know to order soon
- Reduces checkout failures
- Prevents disappointment

### 📊 **Consistent with Admin**
- Same logic as admin product inventory
- Same badges and colors
- Unified system

## Badge Styling

### Colors:
- **Red (Danger):** Out of stock, Exceeds stock
- **Yellow (Warning):** Low stock
- **No badge:** Normal stock levels

### Icons:
- **X-circle-fill:** Out of stock (critical)
- **Exclamation-circle:** Low stock (warning)
- **Exclamation-triangle:** Exceeds stock (error)
- **Box-seam:** Stock count (info)

### Spacing:
- `ms-2` margin added to badges for separation from stock count
- Prevents badges from touching text

## User Flow

### Happy Path:
```
1. User adds product to cart
2. Views cart
3. Sees "100 in stock" (no badge)
4. Proceeds to checkout ✅
```

### Low Stock Warning:
```
1. User adds product to cart
2. Views cart
3. Sees "8 in stock [LOW STOCK]"
4. Thinks: "Better order now!"
5. Proceeds to checkout ✅
```

### Out of Stock:
```
1. User has product in cart
2. Stock runs out
3. Views cart
4. Sees [OUT OF STOCK] badge
5. Removes item or waits for restock
```

### Quantity Exceeds:
```
1. User has 10 items in cart
2. Stock drops to 5
3. Views cart
4. Sees "5 in stock [Exceeds by 5]"
5. Reduces quantity to 5
6. Proceeds to checkout ✅
```

## Upload This File

### Modified:
1. **`cart.php`** - Added restock_alert and improved badge logic

### No Other Changes Needed:
- Database already has restock_alert column
- No new tables or columns required

## Testing Checklist

- [ ] OUT OF STOCK badge shows when stock = 0
- [ ] LOW STOCK badge shows when stock ≤ restock_alert
- [ ] No badge shows when stock > restock_alert
- [ ] "Exceeds" badge shows when quantity > stock
- [ ] Different products use their own restock_alert values
- [ ] Badge colors are correct (red/yellow)
- [ ] Icons display properly
- [ ] Spacing looks good
- [ ] Mobile responsive

## Comparison

### Before:
❌ Hardcoded threshold of 5
❌ Product with restock_alert = 20 at 10 units → No badge
❌ Inconsistent with admin panel
❌ Text only: "Low stock"

### After:
✅ Uses actual restock_alert per product
✅ Product with restock_alert = 20 at 10 units → LOW STOCK badge
✅ Consistent with admin panel
✅ Badge with icon: [⚠️ LOW STOCK]

---

**Status:** ✅ Implemented
**Impact:** Medium (Better UX, prevents confusion)
**User Experience:** Excellent
**Consistency:** Matches admin product inventory badges
