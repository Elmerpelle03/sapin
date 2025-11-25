# Material Inventory Deduction System

## 📋 Overview
This system automatically deducts raw materials from inventory when you produce or add stock of products. It ensures accurate material tracking and prevents production when materials are insufficient.

---

## 🎯 How It Works

### Workflow:
```
1. Define materials needed for each product
   ↓
2. Add/increase product stock
   ↓
3. System checks if enough materials available
   ↓
4. If YES: Deduct materials & log usage
   If NO: Show error, prevent production
```

---

## 📊 Database Structure

### 1. **product_materials** Table
Links products to materials with quantities needed.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| product_id | INT | Product ID (FK) |
| material_id | INT | Material ID (FK) |
| quantity_needed | DECIMAL(10,4) | Amount needed per 1 unit of product |
| created_at | TIMESTAMP | When created |
| updated_at | TIMESTAMP | Last updated |

**Example:**
```sql
-- Bedsheet Single needs 2.18 yards of Canadian cotton per unit
product_id: 5
material_id: 1 (Canadian cotton)
quantity_needed: 2.18
```

### 2. **material_usage_log** Table
Tracks all material usage history.

| Column | Type | Description |
|--------|------|-------------|
| log_id | INT | Primary key |
| product_id | INT | Product produced |
| material_id | INT | Material used |
| quantity_used | DECIMAL(10,4) | Total amount used |
| product_quantity_produced | INT | How many units produced |
| action_type | ENUM | 'production', 'adjustment', 'return' |
| notes | TEXT | Additional info |
| created_by | INT | User ID |
| created_at | TIMESTAMP | When logged |

---

## 🔧 Setup Instructions

### Step 1: Run SQL Script
Execute the database script to create tables:

```bash
# In phpMyAdmin or MySQL command line:
mysql -u root -p sapin_bedsheets < database/product_materials_link.sql
```

Or manually run the SQL in phpMyAdmin.

### Step 2: Define Product Materials
For each product, define which materials it needs:

**Example: Bedsheet Single**
- Material: Canadian cotton
- Quantity needed: 2.18 yards per unit

**Example: Sofa mat 20x60**
- Material: Canadian cotton
- Quantity needed: 2.5 yards per unit

### Step 3: Add Material Stock
Ensure you have materials in inventory before producing products.

---

## 💼 Business Scenarios

### Scenario 1: Adding New Product

**Action:** Add "Bedsheet Queen" with 50 units stock

**System checks:**
```
Product needs: 2.0 yards per unit
Total needed: 2.0 × 50 = 100 yards
Canadian cotton stock: 150 yards ✅

Result: 
- Product added with 50 stock
- Canadian cotton reduced to 50 yards
- Usage logged in material_usage_log
```

**If insufficient:**
```
Canadian cotton stock: 80 yards ❌

Result:
- Error: "Insufficient materials: Canadian cotton (need 100, have 80)"
- Product NOT added
- No changes to inventory
```

---

### Scenario 2: Increasing Product Stock

**Action:** Edit "Bedsheet Single" from 20 to 50 units (+30 units)

**System checks:**
```
Current stock: 20 units
New stock: 50 units
Difference: +30 units

Product needs: 2.18 yards per unit
Total needed: 2.18 × 30 = 65.4 yards
Canadian cotton stock: 100 yards ✅

Result:
- Stock updated to 50 units
- Canadian cotton reduced by 65.4 yards (now 34.6 yards)
- Usage logged: "Stock increased from 20 to 50"
```

---

### Scenario 3: Decreasing Stock (No Material Return)

**Action:** Edit "Bedsheet Single" from 50 to 30 units (-20 units)

**System behavior:**
```
Stock difference: -20 units (decrease)

Result:
- Stock updated to 30 units
- NO materials returned to inventory
- (Materials already used in production)
```

**Note:** Decreasing stock doesn't return materials because they're already used in finished products.

---

### Scenario 4: Multiple Materials

**Product:** Custom Curtain
- Material 1: Blackout fabric (3.5 yards)
- Material 2: Curtain rings (12 pieces)

**Action:** Add 20 units

**System checks:**
```
Blackout fabric needed: 3.5 × 20 = 70 yards
Curtain rings needed: 12 × 20 = 240 pieces

Current stock:
- Blackout fabric: 100 yards ✅
- Curtain rings: 200 pieces ❌

Result:
- Error: "Insufficient materials: Curtain rings (need 240, have 200)"
- Product NOT added
- No deductions made
```

---

## 📈 Usage Tracking & Reports

### View Material Usage Log

**Query to see usage history:**
```sql
SELECT 
    mul.log_id,
    p.product_name,
    m.material_name,
    mul.quantity_used,
    mul.product_quantity_produced,
    mul.action_type,
    mul.notes,
    mul.created_at
FROM material_usage_log mul
JOIN products p ON mul.product_id = p.product_id
JOIN materials m ON mul.material_id = m.material_id
ORDER BY mul.created_at DESC;
```

### Calculate Total Material Used

**Query for monthly material consumption:**
```sql
SELECT 
    m.material_name,
    SUM(mul.quantity_used) as total_used,
    mu.unit,
    MONTH(mul.created_at) as month,
    YEAR(mul.created_at) as year
FROM material_usage_log mul
JOIN materials m ON mul.material_id = m.material_id
LEFT JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id
WHERE mul.action_type = 'production'
GROUP BY m.material_id, YEAR(mul.created_at), MONTH(mul.created_at)
ORDER BY year DESC, month DESC;
```

---

## 🎓 For Your Professor

### Key Features:

1. **Automatic Deduction**
   - No manual calculation needed
   - Prevents human error
   - Real-time inventory updates

2. **Validation Before Production**
   - Checks material availability
   - Prevents over-production
   - Shows clear error messages

3. **Complete Audit Trail**
   - Every deduction logged
   - Who made the change
   - When it happened
   - Why (notes field)

4. **Transaction Safety**
   - Uses database transactions
   - All-or-nothing approach
   - Rollback on errors

5. **Business Logic**
   - Only deducts on stock increase
   - Doesn't return materials on decrease
   - Supports multiple materials per product

---

## 🔍 Technical Implementation

### Transaction Flow (addproduct.php):

```php
BEGIN TRANSACTION

1. Insert product into database
2. Get product_id

3. Fetch materials needed for this product
4. For each material:
   - Calculate total needed (quantity × stock)
   - Check if enough in inventory
   
5. If ANY material insufficient:
   - ROLLBACK transaction
   - Show error message
   - Exit
   
6. If all materials sufficient:
   - Deduct each material from inventory
   - Log each deduction in material_usage_log
   - COMMIT transaction
   - Show success message

END TRANSACTION
```

### Transaction Flow (editproduct.php):

```php
BEGIN TRANSACTION

1. Get current stock from database
2. Calculate stock difference (new - current)

3. If stock INCREASED (difference > 0):
   - Fetch materials needed
   - Check availability for difference amount
   - If insufficient: ROLLBACK & error
   - If sufficient: Deduct & log
   
4. Update product details
5. COMMIT transaction

END TRANSACTION
```

---

## 🛠️ Maintenance & Troubleshooting

### Common Issues:

#### Issue 1: "Insufficient materials" but stock shows available
**Cause:** Multiple users adding products simultaneously
**Solution:** Refresh material inventory page, check actual stock

#### Issue 2: Materials not deducting
**Cause:** Product has no materials defined in product_materials table
**Solution:** Use "Manage Materials" button to define materials

#### Issue 3: Wrong material amounts deducted
**Cause:** Incorrect quantity_needed in product_materials
**Solution:** Update quantity_needed, adjust material stock manually

---

## 📊 Example Data Setup

### Materials Table:
```sql
INSERT INTO materials (material_name, stock, materialunit_id) VALUES
('Canadian cotton', 500.00, 1),  -- unit_id 1 = yards
('Blackout fabric', 300.00, 1),
('Katrina fabric', 200.00, 1),
('Curtain rings', 1000.00, 2);   -- unit_id 2 = pieces
```

### Product Materials (Examples):
```sql
-- Bedsheet Single: 2.18 yards Canadian cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (1, 1, 2.18);

-- Bedsheet Queen: 2.0 yards Canadian cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (2, 1, 2.00);

-- Sofa mat 20x60: 2.5 yards Canadian cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (3, 1, 2.50);

-- Curtain with ring: 3.5 yards Blackout + 12 rings
INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (4, 2, 3.50), (4, 4, 12.00);
```

---

## ✅ Benefits for Business

1. **Accurate Inventory**
   - Always know how much material you have
   - Prevent over-production
   - Plan purchases better

2. **Cost Tracking**
   - See exactly how much material each product uses
   - Calculate true production costs
   - Identify waste

3. **Production Planning**
   - Know how many units you can produce
   - Avoid running out mid-production
   - Schedule material orders

4. **Audit & Compliance**
   - Complete history of material usage
   - Track who produced what
   - Verify inventory counts

---

## 🚀 Future Enhancements

### Possible Additions:

1. **Material Return Feature**
   - Return materials when products are defective
   - Adjust inventory for damaged goods

2. **Batch Production**
   - Produce multiple products at once
   - Bulk material deduction

3. **Material Forecasting**
   - Predict material needs based on sales forecast
   - Auto-generate purchase orders

4. **Cost Calculation**
   - Track material costs
   - Calculate product production cost
   - Profit margin analysis

5. **Low Stock Alerts**
   - Email notifications when materials low
   - Prevent production delays

---

## 📞 Support

If you encounter issues:
1. Check material_usage_log for deduction history
2. Verify product_materials table has correct quantities
3. Ensure materials table has accurate stock
4. Check for database transaction errors in logs

---

**Remember:** This system ensures you never produce more products than you have materials for, keeping your inventory accurate and your business running smoothly! ✅
