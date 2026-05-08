# 🧪 QR Code Instant Scanning - Testing Guide

## Quick Test Checklist

### ✅ Setup (Do Once)
1. Run database migration:
   ```sql
   -- In phpMyAdmin, execute: database/migrations/optimize_qr_lookups.sql
   ```
2. Verify scanner pages work:
   - Kiosk: `http://localhost/paghilom/admin/kiosk/scan_qr.php`
   - POS: `http://localhost/paghilom/pos/scan_qr.php`

---

## 🎯 Test 1: Generate Valid QR Code

### Steps:
1. Create a test order in your system
2. Check if `order_code` is generated in database
3. View receipt/order confirmation page
4. Verify QR code image displays

### Expected Results:
- ✅ QR code starts with `ORD` (e.g., `ORD3F8A2B1C`)
- ✅ QR image displays clearly
- ✅ Code is saved in `orders.order_code` column

### How to Verify:
```sql
SELECT id, order_code, total_amount, payment_status 
FROM orders 
ORDER BY id DESC 
LIMIT 5;
```

---

## ⚡ Test 2: Instant Camera Scanning

### Steps:
1. Open: `http://localhost/paghilom/admin/kiosk/scan_qr.php`
2. Allow camera access when prompted
3. Show QR code to camera (on screen or printed)
4. Wait for detection

### Expected Results:
- ✅ Camera starts within 2 seconds
- ✅ QR detected within 1 second
- ✅ Beep sound plays
- ✅ Green border flash
- ✅ Auto-redirect to payment page
- ✅ Total time: < 2 seconds

### Troubleshooting:
- **No camera?** Allow permissions in browser
- **Slow scanning?** Check lighting, hold steady
- **Not detecting?** Try moving closer/farther

---

## 📱 Test 3: Phone Camera Recognition

### Steps:
1. Display QR code on computer screen OR print it
2. Open phone camera app (iPhone Camera, Google Lens, etc.)
3. Point camera at QR code

### Expected Results:
- ✅ Phone recognizes QR code instantly
- ✅ Shows notification or popup with code
- ✅ Can copy the code text

### Note:
- Phone camera apps just read the text (e.g., `ORD3F8A2B1C`)
- They don't process the payment
- Use webcam scanner for actual payments

---

## 🚫 Test 4: Invalid QR Code

### Steps:
1. Generate random QR code (use online generator)
2. Scan it with your system scanner
3. Observe the error message

### Expected Results:
- ✅ Shows "Invalid QR Code" error message
- ✅ Red border flash
- ✅ Scanner continues running (doesn't freeze)
- ✅ Can scan another code immediately

### Test with:
- Random text QR code
- Website URL QR code
- WiFi password QR code

---

## 💳 Test 5: Already Paid Order

### Steps:
1. Create an order and generate QR code
2. Complete payment for that order
3. Try to scan the same QR code again

### Expected Results:
- ✅ Shows "Order already paid" error
- ✅ Does NOT redirect to payment page
- ✅ Scanner continues running
- ✅ Error message displayed for 3-5 seconds

---

## 🎁 Test 6: Reward/Voucher QR

### Steps:
1. Generate a reward voucher with code `PHC-########`
2. Scan the voucher QR code
3. Verify it redirects to reward redemption

### Expected Results:
- ✅ Detects within 1 second
- ✅ Redirects to reward/voucher page
- ✅ Shows voucher details

### After Redemption:
1. Scan same QR again
2. ✅ Should show "Voucher already redeemed"

---

## ⏱️ Test 7: Performance Test

### Steps:
1. Generate 5 different order QR codes
2. Open browser DevTools (F12) → Console
3. Scan each QR code one by one
4. Note the `validation_time` in console

### Expected Results:
- ✅ Each scan completes in < 2 seconds
- ✅ Validation time: < 100ms per QR
- ✅ No errors in console
- ✅ Smooth transitions

### Console Output Example:
```
QR detected: ORD3F8A2B1C
Validation time: 45ms
Redirecting...
```

---

## 🖨️ Test 8: Printed QR Code

### Steps:
1. Print a QR code on paper (any printer)
2. Scan with webcam scanner
3. Test at different distances

### Expected Results:
- ✅ Scans successfully
- ✅ Works from 10-50cm distance
- ✅ Same speed as digital QR

### Print Quality Tips:
- Use 300 DPI or higher
- Print at least 2x2 inches
- Avoid glossy paper (causes glare)
- Black ink on white paper

---

## 🌐 Test 9: Different Browsers

### Test on:
- ✅ Chrome
- ✅ Firefox
- ✅ Edge
- ✅ Safari (Mac/iOS)

### Expected Results:
- ✅ All browsers detect QR codes
- ✅ Performance is consistent
- ✅ Camera permissions work

### Known Issues:
- Safari may require HTTPS for camera
- Firefox may need manual permission allow
- Mobile browsers work same as desktop

---

## 💻 Test 10: Database Performance

### Run this query:
```sql
EXPLAIN SELECT id, order_code, total_amount, payment_status, status 
FROM orders 
WHERE order_code = 'ORD3F8A2B1C' 
LIMIT 1;
```

### Expected Results:
- ✅ Uses index: `idx_order_code` or `idx_order_validation`
- ✅ Type: `ref` or `const`
- ✅ Rows: 1
- ✅ Extra: `Using where`

### If Not Using Index:
Run migration again:
```sql
ALTER TABLE orders ADD INDEX idx_order_code (order_code);
```

---

## 📊 Performance Benchmarks

| Test | Target | Pass/Fail |
|------|--------|-----------|
| Camera startup | < 2s | ⬜ |
| QR detection | < 1s | ⬜ |
| Validation | < 100ms | ⬜ |
| Total scan→redirect | < 2s | ⬜ |
| Print quality scan | Works | ⬜ |
| Invalid QR error | Shows | ⬜ |
| Paid order error | Shows | ⬜ |
| Multi-browser support | All work | ⬜ |

---

## 🐛 Common Issues & Fixes

### Issue: "Camera not found"
**Fix:**
1. Check browser permissions
2. Connect external webcam if no built-in camera
3. Use HTTPS (required by some browsers)

### Issue: Slow scanning (> 3 seconds)
**Fix:**
1. Run database migration (adds indexes)
2. Improve lighting
3. Clear browser cache
4. Check network speed

### Issue: QR not detected at all
**Fix:**
1. Verify QR code quality (300x300px min)
2. Check if code is in database
3. Test with different QR code
4. Try different camera angle

### Issue: Multiple redirects
**Fix:**
1. Clear browser cookies
2. Check for JavaScript errors
3. Verify debounce is working (800ms)

---

## ✅ Test Completion Checklist

Before marking as complete, verify:

- [ ] Database migration executed
- [ ] All tables have proper indexes
- [ ] QR codes generate with correct format
- [ ] Scanner detects QR in < 1 second
- [ ] Invalid QR shows error (doesn't crash)
- [ ] Paid orders show "already paid" error
- [ ] Performance metrics meet targets
- [ ] Works on Chrome, Firefox, Edge
- [ ] Printed QR codes scan successfully
- [ ] No console errors during scanning

---

## 📞 Support

If any test fails:
1. Check `docs/QR_INSTANT_SCANNING.md` for detailed troubleshooting
2. Review console errors (F12)
3. Verify database migration ran successfully
4. Test with different QR codes
5. Try different browser/camera

---

**Testing Complete?** All tests passing means your instant QR scanning system is production-ready! 🎉

**Last Updated:** February 11, 2025  
**Version:** 1.0  
**Status:** ✅ Ready for Testing
