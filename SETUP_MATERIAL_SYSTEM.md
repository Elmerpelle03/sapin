# Quick Setup Guide - Material Deduction System

## 🚀 5-Minute Setup

### Step 1: Create Database Tables (2 minutes)

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Select your database: `sapin_bedsheets`
3. Click "SQL" tab
4. Copy and paste the contents of `database/product_materials_link.sql`
5. Click "Go"

**Option B: Using MySQL Command Line**
```bash
mysql -u root -p sapin_bedsheets < database/product_materials_link.sql
```

**Expected Result:**
- ✅ Table `product_materials` created
- ✅ Table `material_usage_log` created

---

### Step 2: Test the System (3 minutes)

#### Test 1: Add Material Requirements

1. Go to **Products** page
2. Find any product (e.g., "Bedsheet Single")
3. Click **"Manage Materials"** button (if added to UI)
4. Or manually insert via SQL:

```sql
-- Example: Bedsheet Single needs 2.18 yards of Canadian cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.18
FROM products p, materials m
WHERE p.product_name = 'Bedsheet Single' 
  AND m.material_name = 'Canadian cotton';
```

#### Test 2: Add Material Stock

1. Go to **Material Inventory** page
2. Find "Canadian cotton"
3. Edit and set stock to **100 yards**

#### Test 3: Try Adding Product Stock

1. Go to **Products** page
2. Edit "Bedsheet Single"
3. Increase stock from current to +10 units
4. Click Save

**Expected Results:**
- ✅ Product stock increased by 10
- ✅ Canadian cotton decreased by 21.8 yards (2.18 × 10)
- ✅ Success message: "Product updated successfully. Materials deducted for 10 new units."

#### Test 4: Try Insufficient Materials

1. Set Canadian cotton stock to **5 yards**
2. Try to add 10 units of Bedsheet Single (needs 21.8 yards)

**Expected Result:**
- ❌ Error: "Insufficient materials: Canadian cotton (need 21.8, have 5)"
- ✅ Product stock NOT changed
- ✅ Material stock NOT changed

---

## 📋 Quick Reference

### What Happens When:

| Action | Material Deduction? | Notes |
|--------|-------------------|-------|
| **Add new product** | ✅ YES | Deducts materials for initial stock |
| **Increase stock** | ✅ YES | Deducts only for the increase amount |
| **Decrease stock** | ❌ NO | Materials already used, can't return |
| **Edit price/name** | ❌ NO | Only stock changes trigger deduction |
| **Delete product** | ❌ NO | Materials not returned |

---

## 🔍 Verify It's Working

### Check Material Usage Log:

```sql
SELECT 
    mul.log_id,
    p.product_name,
    m.material_name,
    mul.quantity_used,
    mul.product_quantity_produced,
    mul.notes,
    mul.created_at
FROM material_usage_log mul
JOIN products p ON mul.product_id = p.product_id
JOIN materials m ON mul.material_id = m.material_id
ORDER BY mul.created_at DESC
LIMIT 10;
```

**You should see:**
- Product name
- Material used
- Quantity deducted
- How many units produced
- Timestamp

---

## ⚠️ Important Notes

### Before Production:
1. ✅ Define materials for each product
2. ✅ Ensure materials have sufficient stock
3. ✅ Test with small quantities first

### During Production:
1. ✅ System automatically checks availability
2. ✅ Shows clear error if insufficient
3. ✅ Logs every deduction

### After Production:
1. ✅ Check material_usage_log for history
2. ✅ Verify material stock decreased
3. ✅ Verify product stock increased

---

## 🎯 Example Workflow

### Producing 50 Bedsheets:

**Before:**
```
Canadian cotton: 150 yards
Bedsheet Single stock: 20 units
```

**Action:**
```
Edit Bedsheet Single
Change stock: 20 → 70 (+50 units)
Click Save
```

**System Process:**
```
1. Calculate: 50 units × 2.18 yards = 109 yards needed
2. Check: 150 yards available ✅
3. Deduct: 150 - 109 = 41 yards remaining
4. Update: Product stock now 70 units
5. Log: "Stock increased from 20 to 70"
```

**After:**
```
Canadian cotton: 41 yards
Bedsheet Single stock: 70 units
material_usage_log: New entry created
```

---

## 🛠️ Troubleshooting

### Problem: Materials not deducting

**Check 1:** Does product have materials defined?
```sql
SELECT * FROM product_materials WHERE product_id = YOUR_PRODUCT_ID;
```
If empty → Define materials first

**Check 2:** Is stock actually increasing?
- Only stock increases trigger deduction
- Decreases don't return materials

**Check 3:** Check for errors
- Look at success/error messages
- Check browser console for JavaScript errors

---

### Problem: Wrong amount deducted

**Solution:** Update quantity_needed
```sql
UPDATE product_materials 
SET quantity_needed = 2.50 
WHERE product_id = 5 AND material_id = 1;
```

Then manually adjust material stock if needed.

---

### Problem: Can't add product (insufficient materials)

**Solution A:** Add more material stock
```sql
UPDATE materials 
SET stock = stock + 100 
WHERE material_id = 1;
```

**Solution B:** Reduce product quantity
- Try adding fewer units
- Or remove material requirement temporarily

---

## ✅ Success Checklist

After setup, verify:

- [ ] Tables created (product_materials, material_usage_log)
- [ ] At least one product has materials defined
- [ ] Materials have stock > 0
- [ ] Test: Add product stock successfully
- [ ] Test: Get error when insufficient materials
- [ ] material_usage_log has entries
- [ ] Material stock decreases correctly

---

## 📞 Need Help?

Common questions:

**Q: Do I need to define materials for every product?**
A: No, only for products you want automatic deduction. Products without materials defined will work normally.

**Q: What if I make a mistake?**
A: Check material_usage_log, then manually adjust material stock. The log shows exactly what was deducted.

**Q: Can I return materials?**
A: Not automatically. You can manually increase material stock if needed.

**Q: Does this work with POS sales?**
A: No, this only tracks production (adding product stock). POS sales reduce product stock but don't affect materials.

---

**You're all set! The system will now automatically track material usage whenever you produce products.** 🎉
