# Supplier Management System (Privacy Protected)

## Overview
A system where the **owner can manage suppliers and send orders**, but **supplier contact information is encrypted** so developers/staff cannot see it.

## Key Features

### 🔒 **Privacy Protected**
- Supplier mobile numbers and emails are **encrypted** in the database
- Developers/staff **cannot see** the actual contact details
- Only the **owner** sees decrypted contacts when sending orders

### 📱 **Flexible Contact Methods**
- **Mobile Only** - Supplier only has phone number
- **Email Only** - Supplier only has email
- **Both** - Supplier has both mobile and email
- Owner chooses which method to use when sending orders

### 📝 **Order Management**
- Owner prepares order message in the system
- System shows the message to copy
- Owner manually sends via SMS or Email
- Order record saved for tracking

## How It Works

### 1. Owner Adds Supplier

**Page:** Suppliers Management

```
┌─────────────────────────────────┐
│ Add Supplier                    │
├─────────────────────────────────┤
│ Supplier Name: *                │
│ [ABC Textile Supply_______]     │
│                                 │
│ Company Name:                   │
│ [ABC Company______________]     │
│                                 │
│ Contact Method: *               │
│ [Mobile Number Only ▼]          │
│                                 │
│ Mobile Number:                  │
│ [09171234567______________]     │
│ ⚠️ This will be encrypted       │
│                                 │
│ Notes:                          │
│ [Main supplier for cotton__]    │
│                                 │
│ [Cancel] [Save Supplier]        │
└─────────────────────────────────┘
```

**What Happens:**
- Mobile/Email is **encrypted** before saving
- Stored as gibberish in database
- Developers see: `aGh4jK9mP2xQ...` (encrypted)
- Owner sees: `09171234567` (when sending orders)

### 2. Owner Sends Order

**Page:** Send Order to Supplier

```
┌─────────────────────────────────┐
│ Send Order to ABC Textile       │
├─────────────────────────────────┤
│ Supplier Information            │
│ Name: ABC Textile Supply        │
│ Company: ABC Company            │
│                                 │
│ Send via:                       │
│ ○ Mobile: 09171234567           │
│ ○ Email: supplier@abc.com       │
│                                 │
│ Material to Order: *            │
│ [Blockout (Current: 10 yds) ▼]  │
│                                 │
│ Quantity: *                     │
│ [50_________________________]   │
│ Unit: yards                     │
│                                 │
│ Message/Notes:                  │
│ [Please deliver by Friday___]   │
│                                 │
│ Expected Delivery:              │
│ [2025-10-25________________]    │
│                                 │
│ [Prepare Order Message]         │
└─────────────────────────────────┘
```

**What Happens:**
1. Owner fills out order details
2. Clicks "Prepare Order Message"
3. System shows formatted message to copy
4. Owner manually sends via SMS/Email app
5. Order record saved in system

### 3. Order Message Generated

```
┌─────────────────────────────────┐
│ Order Message Ready             │
├─────────────────────────────────┤
│ Send via: SMS                   │
│ To: 09171234567                 │
│                                 │
│ Message:                        │
│ ┌─────────────────────────────┐ │
│ │ Material Order Request      │ │
│ │                             │ │
│ │ Material: Blockout          │ │
│ │ Quantity: 50 yards          │ │
│ │                             │ │
│ │ Notes: Please deliver by    │ │
│ │ Friday                      │ │
│ │                             │ │
│ │ Thank you!                  │ │
│ └─────────────────────────────┘ │
│                                 │
│ [📋 Copy Message]               │
│                                 │
│ [Save Order Record] [Close]     │
└─────────────────────────────────┘
```

**Owner:**
1. Clicks "Copy Message"
2. Opens SMS/Email app
3. Pastes message
4. Sends to supplier
5. Clicks "Save Order Record"

## Database Structure

### Table: `suppliers`

| Column | Type | Description |
|--------|------|-------------|
| supplier_id | INT | Primary key |
| supplier_name | VARCHAR | Supplier name (visible) |
| company_name | VARCHAR | Company name (visible) |
| contact_type | ENUM | mobile/email/both |
| **encrypted_mobile** | **TEXT** | **Encrypted phone number** |
| **encrypted_email** | **TEXT** | **Encrypted email** |
| notes | TEXT | Public notes |
| is_active | TINYINT | Active status |

### Encryption Example:

**Owner Enters:**
```
Mobile: 09171234567
```

**Stored in Database:**
```
encrypted_mobile: aGh4jK9mP2xQvR8sT3nY6wZ1bC4dE7fG...
```

**Developers See:**
```
SELECT encrypted_mobile FROM suppliers;
Result: aGh4jK9mP2xQvR8sT3nY6wZ1bC4dE7fG...
(Gibberish - cannot decrypt without key)
```

**Owner Sees (when sending order):**
```
Decrypted: 09171234567
(Only shown on send order page)
```

### Table: `supplier_restock_orders`

| Column | Type | Description |
|--------|------|-------------|
| order_id | INT | Primary key |
| supplier_id | INT | Which supplier |
| material_id | INT | Which material |
| requested_quantity | DECIMAL | Amount ordered |
| message | TEXT | Order message |
| sent_via | ENUM | mobile/email |
| sent_date | DATETIME | When sent |
| status | ENUM | sent/confirmed/delivered/cancelled |
| expected_delivery | DATE | Expected date |
| actual_delivery | DATE | Actual date |

## Security Features

### 🔐 **Encryption**
- Uses AES-256-CBC encryption
- Encryption key stored in `config/encryption.php`
- Owner should change the key to their own secret

### 👁️ **Visibility Control**
- **Developers see:** Encrypted gibberish
- **Owner sees:** Actual contact info (only when sending orders)
- **Staff see:** Supplier name only (no contact info)

### 🔒 **Access Control**
- Only owner can add suppliers
- Only owner can send orders
- Only owner sees decrypted contacts

## Files Created

### Database:
1. **`database/create_suppliers_encrypted.sql`**
   - Creates suppliers table
   - Creates supplier_restock_orders table
   - Creates material_suppliers link table

### Config:
2. **`config/encryption.php`**
   - Encryption/decryption functions
   - Masking functions for display

### Pages:
3. **`admin/suppliers.php`**
   - Supplier management page
   - Add/view suppliers
   - Send order button

4. **`admin/send_supplier_order.php`**
   - Order form
   - Shows decrypted contacts (owner only)
   - Generates order message

### Backend:
5. **`admin/backend/add_supplier.php`**
   - Encrypts and saves supplier

6. **`admin/backend/get_suppliers.php`**
   - Lists suppliers (no contact info)

7. **`admin/backend/save_supplier_order.php`**
   - Saves order record

## Installation

### 1. Run SQL:
```sql
-- In phpMyAdmin, run:
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(255) NOT NULL,
    contact_type ENUM('mobile', 'email', 'both') NOT NULL,
    encrypted_mobile TEXT,
    encrypted_email TEXT,
    company_name VARCHAR(255),
    notes TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS supplier_restock_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    supplier_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    message TEXT,
    sent_via ENUM('mobile', 'email') NOT NULL,
    sent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'confirmed', 'delivered', 'cancelled') DEFAULT 'sent',
    expected_delivery DATE,
    actual_delivery DATE,
    FOREIGN KEY (material_id) REFERENCES materials(material_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);
```

### 2. Change Encryption Key:
Edit `config/encryption.php`:
```php
// Change this to your own secret key!
define('ENCRYPTION_KEY', 'YOUR_SECRET_KEY_HERE_CHANGE_THIS');
```

### 3. Upload Files:
- All files listed above

### 4. Add to Navigation:
Add "Suppliers" link to sidebar menu

## Usage Example

### Scenario: Order Blockout Material

**Step 1 - Add Supplier (One Time):**
```
Owner: *Goes to Suppliers page*
Owner: *Clicks "Add Supplier"*
Owner: *Enters:*
  - Name: ABC Textile
  - Mobile: 09171234567
  - Contact Type: Mobile Only
Owner: *Saves*
✅ Supplier added, mobile encrypted
```

**Step 2 - Send Order:**
```
Owner: *Clicks "Send Order" on ABC Textile*
Owner: *Selects:*
  - Material: Blockout
  - Quantity: 50 yards
  - Message: "Please deliver by Friday"
Owner: *Clicks "Prepare Order Message"*
✅ Message generated
```

**Step 3 - Copy & Send:**
```
System: *Shows message:*
  "Material Order Request
   Material: Blockout
   Quantity: 50 yards
   Notes: Please deliver by Friday
   Thank you!"

Owner: *Clicks "Copy Message"*
Owner: *Opens SMS app*
Owner: *Pastes to 09171234567*
Owner: *Sends SMS*
Owner: *Clicks "Save Order Record"*
✅ Order saved in system
```

**Step 4 - Track Delivery:**
```
Owner: *Material arrives*
Owner: *Updates order status to "Delivered"*
Owner: *Adds stock to material inventory*
✅ Complete
```

## Benefits

### ✅ **Privacy Protected**
- Supplier contacts stay private
- Developers can't see phone/email
- Owner controls all supplier communication

### ✅ **Flexible**
- Works with mobile-only suppliers
- Works with email-only suppliers
- Works with both

### ✅ **Simple**
- Owner prepares message in system
- Copies and sends manually
- No complex integrations needed

### ✅ **Tracked**
- All orders recorded
- See order history
- Track delivery dates

## What Developers See

**In Database:**
```sql
SELECT * FROM suppliers;

supplier_id: 1
supplier_name: ABC Textile
encrypted_mobile: aGh4jK9mP2xQvR8sT3nY6wZ1bC4dE7fG...
encrypted_email: NULL
```

**Cannot decrypt without the encryption key!**

## What Owner Sees

**On Send Order Page:**
```
Supplier: ABC Textile
Mobile: 09171234567 ← Decrypted!
```

**Only shown when owner is sending an order.**

---

**Status:** ✅ Complete
**Privacy:** ✅ Contacts encrypted
**Flexibility:** ✅ Mobile or Email or Both
**Tracking:** ✅ Order history saved
