# QR Code Validation System - Security Documentation

## 🔒 Overview

This system ensures that **only valid QR codes from official receipts** can be scanned and processed by POS and Kiosk scanners.

---

## ✅ Security Features Implemented

### 1. **Unique QR Code Generation**
- Each order gets a unique QR code: `ORD` + 8-character hash
- Each reward gets a unique QR code: `PHC-` + 8-character hash
- Hash is generated using: `order_id + timestamp + random_bytes(16)`

**Example codes:**
- Order: `ORD3F8A2B1C`
- Reward: `PHC-A9D4E7F2`

### 2. **Database Validation**
All scanned QR codes are validated against the database:

```php
// Check if QR code exists in orders or vouchers table
// Validate status (not already paid/redeemed)
// Check expiration dates
```

### 3. **Single-Use Protection**
Once a QR code is used, it becomes **inactive**:
- Orders: `payment_status` → `paid`
- Rewards: `status` → `redeemed`
- Timestamp recorded: `paid_at` or `redeemed_at`

### 4. **Format Validation**
Only specific formats are accepted:
- `ORD[A-Z0-9]{6,}` - Order QR codes
- `PHC-[A-Z0-9]{6,}` - Reward/Voucher QR codes

Fake or random QR codes are **automatically rejected**.

---

## 📋 Implementation Guide

### Step 1: Add QR Code Column to Orders Table

```sql
ALTER TABLE orders 
ADD COLUMN order_code VARCHAR(20) UNIQUE NOT NULL DEFAULT '',
ADD INDEX idx_order_code (order_code);
```

### Step 2: Add QR Code Column to Vouchers Table

```sql
ALTER TABLE vouchers 
ADD COLUMN voucher_code VARCHAR(20) UNIQUE NOT NULL DEFAULT '',
ADD COLUMN redeemed_at DATETIME NULL,
ADD INDEX idx_voucher_code (voucher_code);
```

### Step 3: Include QR Helper in Your Code

Add to `config.php` or order creation file:

```php
require_once __DIR__ . '/includes/qr_helper.php';
```

### Step 4: Generate QR Code When Creating Order

```php
// When creating a new order
$order_id = $mysqli->insert_id; // Get newly created order ID

// Generate unique QR code
$qr_code = generate_order_qr_code($order_id);

// Save QR code to database
$stmt = $mysqli->prepare("UPDATE orders SET order_code = ? WHERE id = ?");
$stmt->bind_param('si', $qr_code, $order_id);
$stmt->execute();
```

### Step 5: Display QR Code on Receipt

```php
// In your receipt/order confirmation page
$qr_image_url = generate_qr_code_image($order['order_code'], 300);
?>

<div class="receipt-qr">
    <img src="<?= $qr_image_url ?>" alt="Order QR Code" />
    <p>Scan this QR code at POS or Kiosk</p>
    <p class="qr-code-text"><?= $order['order_code'] ?></p>
</div>
```

---

## 🛡️ Security Flow Diagram

```
[User Places Order]
       ↓
[System Generates Unique QR: ORD3F8A2B1C]
       ↓
[QR Saved to Database (orders.order_code)]
       ↓
[QR Displayed on Receipt]
       ↓
[User Shows Receipt at POS/Kiosk]
       ↓
[Scanner Reads QR Code]
       ↓
[System Validates Against Database]
       ↓
   ✓ Valid?
       ↓ YES
[Redirect to Payment]
       ↓
[Mark as Paid/Redeemed]
       ↓
[QR Becomes Inactive]

   ✗ Invalid?
       ↓ NO
[Show "Invalid QR Code" Error]
[Keep Scanner Active]
```

---

## 🚫 Invalid QR Code Examples

These will be **REJECTED**:

1. **Fake QR Code**: `ORD12345678` (not in database)
2. **Already Used**: `ORD3F8A2B1C` (payment_status = 'paid')
3. **Expired Voucher**: `PHC-A9D4E7F2` (expires_at < NOW())
4. **Cancelled Order**: `ORD7B3C1D5E` (status = 'cancelled')
5. **Random Text**: `HelloWorld123` (wrong format)
6. **Other App QR**: QR from different system

---

## 📱 POS & Kiosk Scanner Validation

### POS Scanner (`pos/api/qr_validate.php`)
```php
// Validate QR code
$validation = validate_qr_code($mysqli, $qr_code);

if ($validation === null) {
    return error('QR code not found');
}

if (isset($validation['error'])) {
    return error($validation['error']);
}

// Valid - redirect to payment
return success([
    'redirect_url' => 'payment.php?mode=' . $validation['type'] . '&id=' . $validation['id']
]);
```

### Kiosk Scanner (`admin/kiosk/api/qr_lookup.php`)
- Same validation logic
- Checks database for valid order/reward
- Redirects to: `payment.php?code=XXXXXXX`

---

## ✅ Testing Checklist

- [ ] Generate QR code when order is created
- [ ] QR code is saved to `orders.order_code` column
- [ ] QR code appears on receipt (digital/printed)
- [ ] Scan valid QR → redirects to payment
- [ ] Scan already-paid QR → shows "already paid" error
- [ ] Scan fake QR → shows "invalid" error
- [ ] After payment, QR becomes inactive
- [ ] Cannot scan same QR twice

---

## 🔐 Additional Security Recommendations

1. **Add Expiration**: QR codes expire after 24 hours
```php
ALTER TABLE orders ADD COLUMN qr_expires_at DATETIME;
```

2. **Rate Limiting**: Limit scan attempts to prevent brute force
3. **Audit Logging**: Log all QR scan attempts (success/failure)
4. **HTTPS Only**: Ensure all QR code endpoints use HTTPS
5. **Token Encryption**: Encrypt QR codes with secret key (optional)

---

## 📞 Support

If you encounter issues:
1. Check database columns exist (`order_code`, `voucher_code`)
2. Verify QR code is being generated on order creation
3. Check scanner validation logic in API files
4. Review error logs in browser console

---

**System Status**: ✅ Secure QR validation implemented
**Last Updated**: November 2, 2025
