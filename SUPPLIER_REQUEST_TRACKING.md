# Supplier Request Tracking

## Where Records Are Saved

When you click "Save Record" after preparing a supplier request, it saves to:

**Database Table:** `material_supplier_requests`

**Saved Information:**
- Material name
- Quantity requested
- Current stock at time of request
- Supplier contact (mobile or email)
- Contact type (mobile/email)
- Message/notes
- Who requested it (admin username)
- Date and time
- Status (pending/sent/delivered)

## View Saved Requests

**New Page:** `supplier_requests_history.php`

### What You Can See:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ Supplier Request History                                                    │
├─────────────────────────────────────────────────────────────────────────────┤
│ Date       │ Material  │ Qty    │ Stock │ Contact      │ Via   │ By    │ Status │
├────────────┼───────────┼────────┼───────┼──────────────┼───────┼───────┼────────┤
│ 2025-10-18 │ Blockout  │ 50 yds │ 10    │ 09171234567  │ 📱    │ Admin │ PENDING│
│ 12:45 AM   │           │        │       │              │ Mobile│       │ [Sent] │
├────────────┼───────────┼────────┼───────┼──────────────┼───────┼───────┼────────┤
│ 2025-10-17 │ Cotton    │ 100 yds│ 20    │ sup@mail.com │ ✉️    │ Admin │ SENT   │
│ 10:30 PM   │           │        │       │              │ Email │       │[Deliver]│
└─────────────────────────────────────────────────────────────────────────────┘
```

### Status Workflow:

1. **PENDING** (Yellow) - Request saved, not sent yet
   - Action: [Mark Sent]

2. **SENT** (Blue) - Request sent to supplier
   - Action: [Mark Delivered]

3. **DELIVERED** (Green) - Material received
   - Done! ✅

## How to Use

### 1. Request Material (Material Inventory)
```
Admin: *Clicks "Restock" on Blockout*
Admin: *Fills form*
Admin: *Clicks "Send via SMS Now"*
Admin: *SMS app opens, sends message*
Admin: *Clicks "Save Record"*
✅ Saved to database
```

### 2. View History (Supplier Requests History)
```
Admin: *Opens "Supplier Requests History" page*
Admin: *Sees all past requests*
Admin: *Clicks "Mark Sent" when sent*
Admin: *Clicks "Mark Delivered" when received*
```

### 3. Track Status
```
PENDING → Admin sends → Mark as "SENT"
SENT → Material arrives → Mark as "DELIVERED"
DELIVERED → Complete! ✅
```

## Benefits

### ✅ **Track All Requests**
- See what was requested
- When it was requested
- Who requested it

### ✅ **Monitor Status**
- Know what's pending
- Know what's been sent
- Know what's delivered

### ✅ **History**
- Review past requests
- See supplier contacts used
- Track delivery times

### ✅ **Accountability**
- See who made each request
- Track when requests were made
- Monitor follow-up

## Files Created

1. **`admin/supplier_requests_history.php`** - View saved requests
2. **`admin/backend/get_supplier_requests.php`** - Fetch requests
3. **`admin/backend/update_supplier_request_status.php`** - Update status

## Installation

### Upload Files:
- `admin/supplier_requests_history.php`
- `admin/backend/get_supplier_requests.php`
- `admin/backend/update_supplier_request_status.php`

### Add to Navigation:
Add link to sidebar menu:
```html
<a href="supplier_requests_history.php">
    <i class="bi bi-clock-history"></i> Supplier Requests
</a>
```

## Example Usage

### Scenario: Order Blockout Material

**Day 1 - Request:**
```
Admin: *Material Inventory → Restock Blockout*
Admin: *Quantity: 50 yards*
Admin: *Contact: 09171234567*
Admin: *Clicks "Send via SMS Now"*
Admin: *Sends SMS*
Admin: *Clicks "Save Record"*
✅ Saved as PENDING
```

**Day 1 - Mark Sent:**
```
Admin: *Opens Supplier Requests History*
Admin: *Sees Blockout request - PENDING*
Admin: *Clicks "Mark Sent"*
✅ Status changed to SENT
```

**Day 3 - Material Arrives:**
```
Admin: *Material delivered*
Admin: *Opens Supplier Requests History*
Admin: *Clicks "Mark Delivered"*
✅ Status changed to DELIVERED
```

**Day 4 - Review:**
```
Admin: *Opens Supplier Requests History*
Admin: *Sees:*
  - Blockout: 50 yards - DELIVERED ✅
  - Cotton: 100 yards - SENT (waiting)
  - Foam: 5kg - PENDING (need to send)
```

## Summary

**When you click "Save Record":**
- ✅ Saves to `material_supplier_requests` table
- ✅ Can view in "Supplier Requests History" page
- ✅ Can track status (pending → sent → delivered)
- ✅ Can review history anytime

---

**Status:** ✅ Complete
**Purpose:** Track supplier requests and delivery status
**Location:** Supplier Requests History page
