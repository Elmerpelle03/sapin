# Simple Supplier Restock Request

## Overview
Admin can request material restock directly from Material Inventory page. System prepares a message to send to supplier via SMS or Email.

## How It Works

### 1. Click "Restock" Button
**Location:** Material Inventory page, next to each material

### 2. Fill Out Request Form
```
┌─────────────────────────────────┐
│ Request Material Restock        │
├─────────────────────────────────┤
│ Material: Blockout              │
│ Current Stock: 10.5 yards       │
│                                 │
│ Quantity to Request: *          │
│ [50_________________________]   │
│                                 │
│ Supplier Contact: *             │
│ [Mobile ▼] [09171234567_____]   │
│ Enter supplier's mobile or email│
│                                 │
│ Message (optional):             │
│ [Please deliver by Friday___]   │
│                                 │
│ ℹ️ This will prepare a message  │
│   to send to your supplier.     │
│                                 │
│ [Cancel] [Prepare Request]      │
└─────────────────────────────────┘
```

### 3. Message Generated
```
┌─────────────────────────────────┐
│ Request Message Ready           │
├─────────────────────────────────┤
│ Send to: 09171234567            │
│ Via: SMS/WhatsApp               │
│                                 │
│ ┌─────────────────────────────┐ │
│ │ Material Restock Request    │ │
│ │                             │ │
│ │ Material: Blockout          │ │
│ │ Quantity Needed: 50 yards   │ │
│ │ Current Stock: 10.5 yards   │ │
│ │                             │ │
│ │ Notes: Please deliver by    │ │
│ │ Friday                      │ │
│ │                             │ │
│ │ Please confirm availability.│ │
│ │ Thank you!                  │ │
│ └─────────────────────────────┘ │
│                                 │
│ [📋 Copy Message]               │
│                                 │
│ [Save Record] [Close]           │
└─────────────────────────────────┘
```

### 4. Admin Actions
1. Click "Copy Message"
2. Open SMS/Email app
3. Paste and send to supplier
4. Click "Save Record" (optional - for tracking)

## Features

### ✅ **Simple & Direct**
- No complex supplier management
- Just enter contact when needed
- Works with mobile or email

### ✅ **Flexible**
- Mobile number → Send via SMS/WhatsApp
- Email → Send via Email
- Admin chooses each time

### ✅ **Message Prepared**
- System formats the message
- Professional format
- Just copy and send

### ✅ **Optional Tracking**
- Can save request record
- Track what was requested
- See request history

## Database

### Table: `material_supplier_requests`

```sql
CREATE TABLE material_supplier_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    current_stock DECIMAL(10,2) NOT NULL,
    supplier_contact VARCHAR(255),  -- Mobile or Email
    contact_type ENUM('mobile', 'email') NOT NULL,
    message TEXT,
    requested_by VARCHAR(100) NOT NULL,
    requested_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'sent', 'delivered') DEFAULT 'pending',
    FOREIGN KEY (material_id) REFERENCES materials(material_id)
);
```

## Installation

### 1. Run SQL:
```sql
CREATE TABLE IF NOT EXISTS material_supplier_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    current_stock DECIMAL(10,2) NOT NULL,
    supplier_contact VARCHAR(255),
    contact_type ENUM('mobile', 'email') NOT NULL,
    message TEXT,
    requested_by VARCHAR(100) NOT NULL,
    requested_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'sent', 'delivered') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE
);
```

### 2. Upload Files:
- `admin/materialinventory.php` (updated)
- `admin/backend/save_supplier_request.php` (new)

## Usage Example

**Scenario: Blockout material running low**

```
Admin: *Opens Material Inventory*
Admin: *Sees Blockout at 10 yards*
Admin: *Clicks "Restock" button*

Admin: *Fills form:*
  - Quantity: 50 yards
  - Contact: 09171234567
  - Type: Mobile
  - Message: "Please deliver by Friday"

Admin: *Clicks "Prepare Request"*

System: *Shows formatted message*

Admin: *Clicks "Copy Message"*
Admin: *Opens SMS app*
Admin: *Pastes to 09171234567*
Admin: *Sends*

Admin: *Clicks "Save Record"*
✅ Done!
```

## Message Format

```
Material Restock Request

Material: Blockout
Quantity Needed: 50 yards
Current Stock: 10.5 yards

Notes: Please deliver by Friday

Please confirm availability.
Thank you!
```

## Benefits

### ✅ **No Setup Required**
- No need to pre-add suppliers
- Just enter contact when needed
- Works immediately

### ✅ **Flexible**
- Different supplier each time? No problem
- Mobile or email? Your choice
- Add notes as needed

### ✅ **Simple**
- 3 clicks: Restock → Copy → Send
- No complex forms
- No encryption needed

### ✅ **Professional**
- Formatted message
- Clear information
- Professional appearance

## Files

1. **`database/create_simple_supplier_requests.sql`** - Create table
2. **`admin/materialinventory.php`** - Updated with new dialog
3. **`admin/backend/save_supplier_request.php`** - Save request record

---

**Status:** ✅ Complete
**Complexity:** Low (Very simple)
**Setup Time:** 2 minutes
