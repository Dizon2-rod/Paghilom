# QR Code Expiry Implementation

## Overview
This document describes the implementation of time-limited QR codes with different validity periods for orders and redemptions.

## Validity Periods

### Order QR Codes
- **Validity**: 3 hours from order creation
- **Purpose**: Allow customers time to complete payment
- **Display**: Shown on order receipts and payment pages
- **Timer Color Codes**:
  - 🟢 Green: More than 50% time remaining (> 1.5 hours)
  - 🟡 Yellow: 25-50% time remaining (45 min - 1.5 hours)
  - 🔴 Red: Less than 25% time remaining (< 45 minutes)

### Redemption QR Codes  
- **Validity**: 30 minutes from voucher creation
- **Purpose**: Ensure timely redemption and prevent abuse
- **Display**: Shown on redemption vouchers
- **Timer Color Codes**:
  - 🟢 Green: More than 50% time remaining (> 15 minutes)
  - 🟡 Yellow: 25-50% time remaining (7.5 - 15 minutes)
  - 🔴 Red: Less than 25% time remaining (< 7.5 minutes)

## Database Changes

### Orders Table
```sql
ALTER TABLE `orders` 
ADD COLUMN `qr_expires_at` DATETIME DEFAULT NULL 
COMMENT '3 hour expiry for order QR codes' 
AFTER `created_at`;

-- Add index for performance
ALTER TABLE `orders` 
ADD INDEX `idx_qr_expires_at` (`qr_expires_at`);
```

### Vouchers Table
```sql
-- Ensure expires_at column exists
ALTER TABLE `vouchers` 
ADD COLUMN `expires_at` DATETIME DEFAULT NULL 
COMMENT '30 minute expiry for redemption QR codes' 
AFTER `created_at`;

-- Add index for performance
ALTER TABLE `vouchers` 
ADD INDEX `idx_expires_at` (`expires_at`);
```

## Implementation Files

### Backend (PHP)

#### 1. `includes/qr_expiry_helper.php`
Helper functions for managing QR expiry times:
- `set_order_qr_expiry($mysqli, $order_id)` - Set 3-hour expiry for order
- `set_voucher_expiry($mysqli, $voucher_id)` - Set 30-minute expiry for voucher
- `get_qr_expiry_info($type, $created_at, $expires_at)` - Get expiry information
- `check_and_expire_qr_codes($mysqli)` - Cleanup expired QR codes

#### 2. `includes/qr_helper.php` (Updated)
Added expiry validation to `validate_qr_code()`:
- Checks if QR code is expired before validation
- Returns appropriate error messages for expired codes
- Includes `expires_at` in returned data

#### 3. `includes/qr_generator.php` (Updated)
Added expiry validation to `instant_validate_qr()`:
- Validates expiry time during instant QR validation
- Returns validation time in response
- Handles both order (3h) and reward (30min) expiry

### Frontend (JavaScript)

#### 1. `assets/js/qr_timer.js`
Reusable countdown timer component:
```javascript
QRTimer.init({
  type: 'order',              // or 'reward'
  createdAt: '2024-01-01 12:00:00',
  timerElement: '#timer-text',
  badgeElement: '#qr-timer',
  onExpire: function() {
    alert('QR Code has expired!');
  }
});
```

#### 2. `payment_success.php` (Updated)
- Displays countdown timer for order QR codes
- Shows remaining time in hours, minutes, seconds
- Color-coded badge based on time remaining
- Auto-updates every second

#### 3. `templates/receipt_qr.php` (Updated)
- Universal timer for both order and redemption QR codes
- Automatic type detection
- Responsive timer display
- Print-friendly (timer hidden when printing)

## Usage Examples

### Setting Expiry on Order Creation
```php
require_once __DIR__ . '/includes/qr_expiry_helper.php';

// After creating order
$order_id = $mysqli->insert_id;
set_order_qr_expiry($mysqli, $order_id);
```

### Setting Expiry on Voucher Creation
```php
require_once __DIR__ . '/includes/qr_expiry_helper.php';

// After creating voucher
$voucher_id = $mysqli->insert_id;
set_voucher_expiry($mysqli, $voucher_id);
```

### Validating QR Code
```php
require_once __DIR__ . '/includes/qr_helper.php';

$validation = validate_qr_code($mysqli, $qr_code);

if ($validation === null) {
    echo "QR code not found";
} elseif (isset($validation['error'])) {
    echo "Error: " . $validation['error'];
    // Could be: "Order QR code has expired (valid for 3 hours)"
    // or: "Redemption QR code has expired (valid for 30 minutes)"
} else {
    echo "Valid QR code!";
    echo "Expires at: " . $validation['expires_at'];
}
```

### Adding Timer to Any Page
```html
<!-- Include the timer script -->
<script src="/assets/js/qr_timer.js"></script>

<!-- Timer display elements -->
<div id="qr-timer" class="badge">
    <i class="fas fa-clock"></i>
    <span id="timer-text">Loading...</span>
</div>

<!-- Initialize timer -->
<script>
QRTimer.init({
    type: 'order',  // or 'reward'
    createdAt: '<?= $order['created_at'] ?>',
    timerElement: '#timer-text',
    badgeElement: '#qr-timer',
    onExpire: function() {
        window.location.reload();  // Refresh page on expiry
    }
});
</script>
```

## Database Migration

Run the migration script to add expiry columns and update existing records:

```bash
# Using MySQL command line
mysql -u username -p database_name < migrations/add_qr_expiry.sql

# Or using phpMyAdmin
# Import the file: migrations/add_qr_expiry.sql
```

## Cleanup Job (Optional)

To automatically mark expired QR codes in the database:

```php
// Create a cron job that runs every 5 minutes
// File: cron/expire_qr_codes.php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/qr_expiry_helper.php';

$result = check_and_expire_qr_codes($mysqli);

error_log(sprintf(
    "QR Expiry Check: %d orders expired, %d vouchers expired at %s",
    $result['expired_orders'],
    $result['expired_vouchers'],
    $result['timestamp']
));
```

Crontab entry:
```
*/5 * * * * php /path/to/paghilom/cron/expire_qr_codes.php
```

## Testing

### Test Order QR Expiry
1. Create a new order
2. Check the QR code on `payment_success.php`
3. Verify timer shows "2h 59m XX s" (approximately)
4. Wait and watch timer count down
5. Colors should change: Green → Yellow → Red
6. After 3 hours, timer should show "QR Code Expired"

### Test Redemption QR Expiry
1. Create a redemption voucher
2. Check the QR code display
3. Verify timer shows "29:XX" (approximately)
4. Colors should change: Green → Yellow → Red  
5. After 30 minutes, timer should show "QR Code Expired"

## Troubleshooting

### Timer Not Updating
- Check JavaScript console for errors
- Verify `qr_timer.js` is loaded
- Ensure timer elements exist in DOM

### Expiry Not Enforced
- Run database migration to add columns
- Verify `qr_expiry_helper.php` is included
- Check `qr_helper.php` validation logic

### Wrong Expiry Times
- Check server timezone settings
- Verify `created_at` values in database
- Ensure correct type ('order' vs 'reward') is passed

## Browser Compatibility

The timer works in:
- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS/Android)

## Performance Considerations

- Timer uses `setTimeout` for efficient updates
- Database queries use indexed columns
- Expiry validation happens before other checks (fail fast)
- Cleanup job prevents database bloat

## Security Notes

- QR expiry is enforced server-side
- Client-side timer is for UX only
- Backend validation always checks expiry
- Expired codes return error responses

## Future Enhancements

- [ ] Configurable expiry times via admin panel
- [ ] Email/SMS notifications before expiry
- [ ] Grace period for near-expired codes
- [ ] Analytics on expiry patterns
- [ ] Ability to extend expiry time

## Support

For issues or questions:
1. Check this documentation
2. Review implementation files
3. Test with provided examples
4. Contact development team
