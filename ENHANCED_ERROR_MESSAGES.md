# Enhanced Material Shortage Error Messages

## Overview
Improved error messages that show detailed material shortage information and indicate how many units CAN be produced with available materials.

## What Changed

### Before:
```
❌ Update Failed
Insufficient materials to produce 5 units: US Katrina (need 2.35, have 1.12)
```

### After:
```
❌ Update Failed
Product Name
Cannot produce 5 units. Insufficient materials.

✓ You can add up to 0 units

Material Shortage:
• US Katrina
  Need: 11.75 | Have: 1.12 | Short: 10.63
```

## Features

### 1. **Max Producible Units**
Shows how many units CAN be produced with current materials:
- ✅ **"You can add up to 3 units"** - Some materials available
- ❌ **"Cannot produce any units"** - No materials available

### 2. **Detailed Material Breakdown**
For each insufficient material:
- **Material Name**
- **Amount Needed** for requested quantity
- **Amount Available** in inventory
- **Shortage Amount** (how much more is needed)

### 3. **Bulk Restock Summary**
Shows all failed products with their details:
```
Bulk Restock Complete
5 products restocked successfully
3 failed

Failed Products:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
3IN1 With Ring Curtain
Requested: 10 units
✓ Can produce: 0 units
Blockout: need 23.5, have 1.12 (short by 22.38)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Bedsheet Queen
Requested: 20 units
✓ Can produce: 15 units
Canadian Cotton: need 54.4, have 40.8 (short by 13.6)
```

## Implementation

### Backend Changes

**File:** `admin/backend/quick_stock_update.php`

**Added:**
1. Calculate max producible units for each material
2. Find the limiting material (minimum)
3. Return detailed error information

```php
$insufficient_materials = [];
$max_producible = PHP_INT_MAX;

foreach ($materials as $mat) {
    $total_needed = $mat['quantity_needed'] * $stock_difference;
    
    // Calculate max units this material can produce
    $units_possible = floor($mat['stock'] / $mat['quantity_needed']);
    $max_producible = min($max_producible, $units_possible);
    
    if ($mat['stock'] < $total_needed) {
        $shortage = $total_needed - $mat['stock'];
        $insufficient_materials[] = [
            'name' => $mat['material_name'],
            'needed' => $total_needed,
            'available' => $mat['stock'],
            'shortage' => $shortage,
            'max_units' => $units_possible
        ];
    }
}

// Return detailed error
echo json_encode([
    'success' => false,
    'message' => "Cannot produce {$stock_difference} units. Insufficient materials.",
    'detailed_message' => implode(' | ', $error_details),
    'requested_units' => $stock_difference,
    'max_producible' => $max_producible,
    'insufficient_materials' => $insufficient_materials,
    'product_name' => $product_name
]);
```

### Frontend Changes

**File:** `admin/products.php`

**Individual Stock Update:**
```javascript
// Build detailed error message
let errorHtml = `<div class="text-start">`;
errorHtml += `<p><strong>${response.product_name}</strong></p>`;
errorHtml += `<p class="text-danger">${response.message}</p>`;

if (response.max_producible > 0) {
    errorHtml += `<p class="text-success">✓ You can add up to ${response.max_producible} units</p>`;
} else {
    errorHtml += `<p class="text-danger">✗ Cannot produce any units</p>`;
}

// Show material breakdown
response.insufficient_materials.forEach(mat => {
    errorHtml += `
        <li>
            <strong>${mat.name}</strong><br>
            Need: ${mat.needed} | Have: ${mat.available} | Short: ${mat.shortage}
        </li>
    `;
});
```

**Bulk Restock:**
```javascript
// Store errors during processing
window.bulkRestockErrors.push({
    product_name: response.product_name,
    message: response.message,
    requested_units: response.requested_units,
    max_producible: response.max_producible,
    insufficient_materials: response.insufficient_materials
});

// Show summary at the end
window.bulkRestockErrors.forEach(error => {
    resultHtml += `
        <div class="alert alert-warning">
            <strong>${error.product_name}</strong><br>
            Requested: ${error.requested_units} units<br>
            ${error.max_producible > 0 ? 
                `✓ Can produce: ${error.max_producible} units` : 
                '✗ Cannot produce any units'
            }
            <br><small>${error.detailed_message}</small>
        </div>
    `;
});
```

## Examples

### Example 1: Partial Materials Available

**Scenario:**
- Product: Curtain 7ft Blockout
- Requested: 10 units
- Blockout needed: 23.5 yards (2.35 per unit)
- Blockout available: 12.0 yards
- Max producible: 5 units (12.0 ÷ 2.35 = 5.1, floor to 5)

**Error Message:**
```
❌ Update Failed
Curtain 7ft Blockout
Cannot produce 10 units. Insufficient materials.

✓ You can add up to 5 units

Material Shortage:
• Blockout
  Need: 23.5 | Have: 12.0 | Short: 11.5
```

### Example 2: No Materials Available

**Scenario:**
- Product: Pillow
- Requested: 10 units
- Crushed Foam needed: 4000 grams
- Crushed Foam available: 0 grams
- Max producible: 0 units

**Error Message:**
```
❌ Update Failed
Pillow
Cannot produce 10 units. Insufficient materials.

✗ Cannot produce any units with current materials

Material Shortage:
• Crushed Foam with Fiber
  Need: 4000 | Have: 0 | Short: 4000
```

### Example 3: Multiple Materials, One Insufficient

**Scenario:**
- Product: Pillow
- Requested: 20 units
- Canadian Cotton needed: 40 yards, have: 100 yards ✅
- Crushed Foam needed: 8000 grams, have: 3000 grams ❌
- Max producible: 7 units (3000 ÷ 400 = 7.5, floor to 7)

**Error Message:**
```
❌ Update Failed
Pillow
Cannot produce 20 units. Insufficient materials.

✓ You can add up to 7 units

Material Shortage:
• Crushed Foam with Fiber
  Need: 8000 | Have: 3000 | Short: 5000
```

### Example 4: Bulk Restock with Mixed Results

**Scenario:**
- Selected: 6 products
- Successful: 3 products
- Failed: 3 products

**Result Message:**
```
✅ Bulk Restock Complete
3 products restocked successfully
3 failed

Failed Products:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Curtain 7ft Blockout
Requested: 10 units
✓ Can produce: 5 units
Blockout: need 23.5, have 12.0 (short by 11.5)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Pillow
Requested: 20 units
✓ Can produce: 7 units
Crushed Foam with Fiber: need 8000, have 3000 (short by 5000)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Bedsheet King
Requested: 15 units
✗ Cannot produce any units
Canadian Cotton: need 48.15, have 0 (short by 48.15)
```

## Benefits

### ✅ **Clear Communication**
- Users know exactly why the restock failed
- Shows which materials are insufficient
- Indicates how much more material is needed

### ✅ **Actionable Information**
- **"You can add up to X units"** - User can add partial amount
- Material shortage details - User knows what to restock
- Prevents wasted time trying amounts that won't work

### ✅ **Better Planning**
- See max producible before attempting
- Prioritize material restocking
- Make informed decisions about production

### ✅ **Bulk Restock Transparency**
- See all failures at once
- Understand which products need attention
- Know which materials are limiting production

## User Workflow

### Before Enhancement:
1. Click +10 on product
2. ❌ "Insufficient materials"
3. Try +5
4. ❌ "Insufficient materials"
5. Try +3
6. ❌ "Insufficient materials"
7. Give up or keep guessing

### After Enhancement:
1. Click +10 on product
2. ❌ "Insufficient materials. You can add up to 5 units"
3. Click custom, enter 5
4. ✅ Success!

## Files Modified

1. **`admin/backend/quick_stock_update.php`**
   - Calculate max producible units
   - Return detailed error information

2. **`admin/products.php`**
   - Enhanced individual error display
   - Enhanced bulk restock error summary
   - Store and display error details

## Upload These Files

1. `admin/backend/quick_stock_update.php` - Updated
2. `admin/products.php` - Updated

---

**Status:** ✅ Complete
**Impact:** High (Better user experience)
**User Benefit:** Clear, actionable error messages
