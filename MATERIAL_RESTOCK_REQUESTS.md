# Material Restock Request System

## Overview
A system that allows staff to request material restocking without knowing supplier information. The owner receives requests and handles supplier contact privately.

## How It Works

### 1. Staff Requests Restock
**Location:** Material Inventory page

- Click **"Restock"** button next to any material
- Enter quantity needed
- Add reason (optional)
- Submit request

**What happens:**
- Request saved to database
- Owner notified (can be viewed in Restock Requests page)
- Staff doesn't need supplier info

### 2. Owner Reviews Requests
**Location:** Material Restock Requests page

- See all pending requests
- View material name, quantity, reason
- Approve or reject requests

### 3. Owner Contacts Supplier (Privately)
- Owner uses their own supplier contacts
- Places order with supplier
- No supplier info exposed to staff

### 4. Owner Marks as Ordered
- Click **"Mark Ordered"** when order is placed
- Tracks that order is in progress

### 5. Material Arrives
- Click **"Mark Received"**
- System automatically adds quantity to material stock
- Request marked as complete

## Database Structure

### Table: `material_restock_requests`

| Column | Type | Description |
|--------|------|-------------|
| request_id | INT | Primary key |
| material_id | INT | Material to restock |
| requested_quantity | DECIMAL | Amount requested |
| current_stock | DECIMAL | Stock at time of request |
| reason | VARCHAR | Why restock is needed |
| requested_by | VARCHAR | Staff member name |
| requested_date | DATETIME | When requested |
| status | ENUM | pending/approved/ordered/received/rejected |
| owner_notes | TEXT | Private notes from owner |
| expected_delivery_date | DATE | When material expected |
| actual_delivery_date | DATE | When actually received |

### Status Flow:

```
pending → approved → ordered → received
   ↓
rejected
```

## Features

### ✅ **Privacy Protected**
- No supplier information visible to staff
- Owner handles all supplier contact
- Supplier details remain confidential

### ✅ **Request Tracking**
- All requests logged with date/time
- See who requested what
- Track request status

### ✅ **Automatic Stock Update**
- When marked "received", stock auto-updates
- No manual entry needed
- Prevents errors

### ✅ **Status Management**
- Pending: Waiting for owner review
- Approved: Owner will order
- Ordered: Order placed with supplier
- Received: Material arrived, stock updated
- Rejected: Request denied

## User Interface

### Material Inventory Page

**New Button Added:**
```
[Restock] [✏️ Edit] [🗑 Delete]
```

**Restock Dialog:**
```
┌─────────────────────────────────┐
│ Request Material Restock        │
├─────────────────────────────────┤
│ Material: Blockout              │
│ Current Stock: 10.5 yards       │
│                                 │
│ Quantity to Request:            │
│ [_________________]             │
│                                 │
│ Reason (optional):              │
│ [_________________]             │
│ [_________________]             │
│                                 │
│ ℹ️ This will notify the owner  │
│   to contact the supplier.      │
│                                 │
│ [Cancel] [Submit Request]       │
└─────────────────────────────────┘
```

### Material Restock Requests Page

**Table View:**
```
┌────┬──────────┬──────────┬────────┬────────┬─────────────┬──────────┬─────────┬─────────┐
│ ID │ Material │ Req. Qty │ Stock  │ Reason │ Requested   │ Date     │ Status  │ Actions │
├────┼──────────┼──────────┼────────┼────────┼─────────────┼──────────┼─────────┼─────────┤
│ 5  │ Blockout │ 50 yards │ 10 yds │ Low    │ Admin       │ 10/18/25 │ PENDING │ [Approve]│
│    │          │          │        │ stock  │             │          │         │ [Reject] │
├────┼──────────┼──────────┼────────┼────────┼─────────────┼──────────┼─────────┼─────────┤
│ 4  │ Cotton   │ 100 yds  │ 20 yds │ Orders │ Staff1      │ 10/17/25 │APPROVED │ [Mark    │
│    │          │          │        │ coming │             │          │         │ Ordered] │
├────┼──────────┼──────────┼────────┼────────┼─────────────┼──────────┼─────────┼─────────┤
│ 3  │ Foam     │ 5000 g   │ 1000 g │ Low    │ Staff2      │ 10/16/25 │ ORDERED │ [Mark    │
│    │          │          │        │ stock  │             │          │         │Received] │
└────┴──────────┴──────────┴────────┴────────┴─────────────┴──────────┴─────────┴─────────┘
```

## Workflow Example

### Scenario: Blockout Material Running Low

**Day 1 - Staff Notices Low Stock:**
```
Staff: "Blockout is at 10 yards, we need more"
Staff: *Clicks Restock button*
Staff: *Enters 50 yards*
Staff: *Reason: "Low stock, upcoming orders"*
Staff: *Submits request*
✅ Request #5 created
```

**Day 1 - Owner Reviews:**
```
Owner: *Opens Restock Requests page*
Owner: *Sees Request #5*
Owner: *Clicks "Approve"*
✅ Status: PENDING → APPROVED
```

**Day 2 - Owner Orders:**
```
Owner: *Calls supplier privately*
Owner: *Places order for 50 yards Blockout*
Owner: *Clicks "Mark Ordered"*
✅ Status: APPROVED → ORDERED
```

**Day 5 - Material Arrives:**
```
Owner: *Material delivered*
Owner: *Clicks "Mark Received"*
✅ Status: ORDERED → RECEIVED
✅ Blockout stock: 10 → 60 yards
```

## Files Created

### Database:
1. **`database/create_material_restock_requests.sql`**
   - Creates `material_restock_requests` table
   - Adds tracking columns to `materials` table

### Backend:
2. **`admin/backend/request_material_restock.php`**
   - Handles restock request submission

3. **`admin/backend/get_restock_requests.php`**
   - Fetches all restock requests for display

4. **`admin/backend/update_restock_status.php`**
   - Updates request status (approve/reject/ordered)

5. **`admin/backend/mark_restock_received.php`**
   - Marks as received and updates material stock

### Frontend:
6. **`admin/materialinventory.php`** (Modified)
   - Added "Restock" button
   - Added request submission dialog

7. **`admin/material_restock_requests.php`** (New Page)
   - Owner's page to manage requests
   - View, approve, track requests

## Installation Steps

### 1. Create Database Table:
```sql
-- Run in phpMyAdmin
CREATE TABLE IF NOT EXISTS material_restock_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    requested_quantity DECIMAL(10,2) NOT NULL,
    current_stock DECIMAL(10,2) NOT NULL,
    reason VARCHAR(255) DEFAULT 'Low stock',
    requested_by VARCHAR(100) NOT NULL,
    requested_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'ordered', 'received', 'rejected') DEFAULT 'pending',
    owner_notes TEXT,
    expected_delivery_date DATE,
    actual_delivery_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (material_id) REFERENCES materials(material_id) ON DELETE CASCADE
);
```

### 2. Upload Files:
- `admin/materialinventory.php` (updated)
- `admin/material_restock_requests.php` (new)
- `admin/backend/request_material_restock.php`
- `admin/backend/get_restock_requests.php`
- `admin/backend/update_restock_status.php`
- `admin/backend/mark_restock_received.php`

### 3. Add to Navigation:
Add link to sidebar for "Material Restock Requests" page

## Benefits

### 🔒 **Protects Supplier Information**
- Owner keeps supplier contacts private
- Staff can't see or contact suppliers
- Business relationships protected

### 📋 **Organized Requests**
- All requests in one place
- Track status of each request
- See request history

### ⚡ **Efficient Process**
- Staff requests when needed
- Owner reviews and orders
- Stock auto-updates when received

### 📊 **Audit Trail**
- Who requested what
- When it was requested
- When it was received
- How much was added

## Future Enhancements (Optional)

1. **Email Notifications**
   - Notify owner of new requests
   - Notify staff when approved/received

2. **Automatic Requests**
   - Auto-create request when stock hits reorder point
   - Owner just approves

3. **Cost Tracking**
   - Owner can add cost per request
   - Track material expenses

4. **Supplier Management (Owner Only)**
   - Owner-only page with supplier contacts
   - Password protected
   - Not visible to staff

---

**Status:** ✅ Complete
**Privacy:** ✅ Supplier info protected
**Automation:** ✅ Stock auto-updates
