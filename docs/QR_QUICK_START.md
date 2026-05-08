# QR Code Security - Quick Start Guide

## 🚀 Setup in 5 Steps

### Step 1: Run Database Migration (Required)
```bash
# Open phpMyAdmin or MySQL command line
# Select your paghilom_cafe database
# Run the SQL file:
```
📁 `database/migrations/add_qr_code_columns.sql`

This adds:
- `orders.order_code` column
- `orders.paid_at` column
- `vouchers.voucher_code` column
- `vouchers.redeemed_at` column

---

### Step 2: Include QR Helper in config.php

Add this line to `config.php`:

```php
// After other includes
require_once __DIR__ . '/includes/qr_helper.php';
```

---

### Step 3: Generate QR Code When Creating Orders

Find where you create new orders (e.g., `place_order.php`, `quick_sale.php`, etc.)

**Add this code after order is inserted:**

```php
// Get the newly created order ID
$order_id = $mysqli->insert_id;

// Generate unique QR code
$qr_code = generate_order_qr_code($order_id);

// Save QR code to database
$stmt = $mysqli->prepare("UPDATE orders SET order_code = ? WHERE id = ?");
$stmt->bind_param('si', $qr_code, $order_id);
$stmt->execute();
$stmt->close();
```

---

### Step 4: Display QR Code on Receipt

In your receipt/order confirmation page:

```php
<?php
// Load order data
$order_id = $_GET['id'];
$stmt = $mysqli->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Generate QR code image URL
$qr_image = generate_qr_code_image($order['order_code'], 300);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Receipt</title>
    <style>
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 10px;
            margin: 20px 0;
        }
        .qr-code {
            max-width: 300px;
            margin: 10px auto;
        }
        .qr-text {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <h1>Order #<?= $order['id'] ?></h1>
        <p>Customer: <?= htmlspecialchars($order['customer_name']) ?></p>
        <p>Total: ₱<?= number_format($order['total_amount'], 2) ?></p>
        
        <div class="qr-section">
            <h3>🎫 Scan to Pay</h3>
            <img src="<?= $qr_image ?>" alt="Order QR Code" class="qr-code" />
            <div class="qr-text"><?= $order['order_code'] ?></div>
            <p>Show this QR code at POS or Kiosk to complete payment</p>
        </div>
    </div>
</body>
</html>
```

---

### Step 5: Test the System

1. ✅ **Create a test order**
   - Go through your order creation flow
   - Check if `order_code` is generated (e.g., `ORD3F8A2B1C`)

2. ✅ **View the receipt**
   - Verify QR code appears on receipt
   - QR code should display the order_code

3. ✅ **Scan at POS**
   - Open: `http://localhost/paghilom/pos/index.php`
   - Scan the QR code from receipt
   - Should redirect to payment page

4. ✅ **Scan at Kiosk**
   - Open: `http://localhost/paghilom/admin/kiosk/scan_qr.php`
   - Scan the QR code from receipt
   - Should redirect to payment page

5. ✅ **Test invalid QR**
   - Scan a random QR code
   - Should show "Invalid QR Code" error

6. ✅ **Test already paid**
   - Pay for an order
   - Try to scan same QR again
   - Should show "Order already paid" error

---

## 📝 Example: Complete Order Flow with QR

```php
<?php
// File: place_order.php
require_once __DIR__ . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Create order
    $customer_name = $_POST['name'];
    $total_amount = $_POST['total'];
    
    $stmt = $mysqli->prepare(
        "INSERT INTO orders (customer_name, total_amount, payment_status, status, created_at) 
         VALUES (?, ?, 'unpaid', 'pending', NOW())"
    );
    $stmt->bind_param('sd', $customer_name, $total_amount);
    $stmt->execute();
    $order_id = $mysqli->insert_id;
    $stmt->close();
    
    // 2. Generate QR code (NEW CODE)
    $qr_code = generate_order_qr_code($order_id);
    
    // 3. Save QR code to database (NEW CODE)
    $stmt = $mysqli->prepare("UPDATE orders SET order_code = ? WHERE id = ?");
    $stmt->bind_param('si', $qr_code, $order_id);
    $stmt->execute();
    $stmt->close();
    
    // 4. Redirect to receipt
    header('Location: receipt.php?id=' . $order_id);
    exit;
}
?>
```

---

## 🔍 Troubleshooting

### QR code is empty or not showing?
```sql
-- Check if order_code column exists
SHOW COLUMNS FROM orders LIKE 'order_code';

-- Check if QR codes are being generated
SELECT id, order_code FROM orders ORDER BY id DESC LIMIT 10;
```

### Scanner says "QR code not found"?
- Make sure you ran the database migration
- Verify order_code is saved in database
- Check API endpoint is working: `pos/api/qr_validate.php`

### Camera not working in Kiosk?
- Allow camera permissions in browser
- Use HTTPS or localhost
- Check browser console for errors

---

## 📚 Additional Resources

- 📄 Full Documentation: `docs/QR_CODE_SECURITY.md`
- 🗄️ Database Migration: `database/migrations/add_qr_code_columns.sql`
- 🔧 Helper Functions: `includes/qr_helper.php`

---

## ✅ Checklist

- [ ] Database migration completed
- [ ] QR helper included in config.php
- [ ] QR code generation added to order creation
- [ ] QR code displays on receipt
- [ ] POS scanner works with valid QR
- [ ] Kiosk scanner works with valid QR
- [ ] Invalid QR codes are rejected
- [ ] Already-paid QR codes are rejected

---

**Ready to go!** 🎉

Your system now has secure QR code validation. Only official receipt QR codes will work at POS and Kiosk.
