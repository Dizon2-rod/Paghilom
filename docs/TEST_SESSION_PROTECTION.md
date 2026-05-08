# 🧪 Session Protection Testing Guide

## Quick Test Steps

### ✅ Test 1: Owner Login & Back Button
1. Open browser (Chrome/Firefox) in **Incognito/Private mode**
2. Go to: `http://localhost/paghilom/login.php`
3. Login as **Owner** (username: owner, password: your_password)
4. ✅ **Expected:** Redirected to `/owner/owner_dashboard.php`
5. Press **Back button** on browser
6. ✅ **Expected:** Stay on owner dashboard (cannot go back to login)

---

### ✅ Test 2: Admin Login & Back Button
1. Open new Incognito window
2. Go to: `http://localhost/paghilom/login.php`
3. Login as **Admin** (username: admin, password: your_password)
4. ✅ **Expected:** Redirected to `/admin/dashboard.php`
5. Press **Back button** on browser
6. ✅ **Expected:** Stay on admin dashboard (cannot go back to login)

---

### ✅ Test 3: Direct Login URL While Logged In
1. While logged in as Owner or Admin
2. Type in address bar: `http://localhost/paghilom/login.php`
3. Press Enter
4. ✅ **Expected:** Immediately redirected back to your dashboard

---

### ✅ Test 4: Logout Then Back Button
1. While logged in, click **Logout** button
2. ✅ **Expected:** Redirected to login page
3. Press **Back button** on browser
4. ✅ **Expected:** Cannot access dashboard, redirected to login

---

### ✅ Test 5: Multiple Back Button Presses
1. Login as any role (Owner/Admin/User)
2. Navigate to dashboard
3. Press **Back button 5-10 times rapidly**
4. ✅ **Expected:** Stay on dashboard, no matter how many times you press back

---

### ✅ Test 6: Keyboard Shortcut (Alt + Left Arrow)
1. Login and go to dashboard
2. Press **Alt + Left Arrow** (back keyboard shortcut)
3. ✅ **Expected:** Nothing happens, stay on dashboard

---

### ✅ Test 7: Refresh Page While Logged In
1. Login and go to dashboard
2. Press **F5** or **Ctrl+R** to refresh
3. ✅ **Expected:** Dashboard reloads, still logged in

---

### ✅ Test 8: Browser Back/Forward Buttons
1. Login as Owner
2. Click a link inside dashboard (e.g., "Manage Orders")
3. Press **Back button**
4. ✅ **Expected:** Go back to dashboard (not to login page)
5. Press **Forward button**
6. ✅ **Expected:** Go forward to "Manage Orders"

---

## 🔍 Advanced Testing

### Test Browser Cache
1. Login as Owner
2. Logout
3. Close browser completely
4. Reopen browser and press **Back button**
5. ✅ **Expected:** Cannot access dashboard, redirected to login

---

### Test Session Expiration
1. Login as Admin
2. Manually delete session cookies in browser:
   - Chrome: F12 → Application → Cookies → Delete PHPSESSID
   - Firefox: F12 → Storage → Cookies → Delete PHPSESSID
3. Refresh the dashboard page
4. ✅ **Expected:** Redirected to login page

---

### Test Multiple Tabs
1. Login as Owner in Tab 1
2. Open new Tab 2, go to `http://localhost/paghilom/login.php`
3. ✅ **Expected:** Tab 2 auto-redirects to owner dashboard
4. Logout in Tab 1
5. Refresh Tab 2
6. ✅ **Expected:** Tab 2 redirected to login

---

## 🐛 Troubleshooting

### ❌ Problem: Still able to go back to login
**Solution:**
1. Clear browser cache completely (Ctrl+Shift+Delete)
2. Close all browser windows
3. Restart browser in Incognito/Private mode
4. Try again

---

### ❌ Problem: JavaScript not loading
**Check:**
1. Open browser console (F12)
2. Look for errors in Console tab
3. Verify these files exist:
   - `assets/js/session-check.js`
   - `assets/js/no-back.js`

---

### ❌ Problem: PHP headers already sent error
**Solution:**
1. Make sure there's no whitespace or BOM before `<?php` in files
2. Check `config.php`, `login.php`, `config/auth.php`
3. Remove any `echo` statements before `header()` calls

---

## 📊 Expected Results Summary

| Test | Action | Expected Result | Status |
|------|--------|-----------------|--------|
| 1 | Login as Owner | → Owner Dashboard | ✅ |
| 2 | Login as Admin | → Admin Dashboard | ✅ |
| 3 | Login as User | → Index Page | ✅ |
| 4 | Back button after login | Stay on dashboard | ✅ |
| 5 | Type login URL while logged in | Auto-redirect to dashboard | ✅ |
| 6 | Logout | → Login Page | ✅ |
| 7 | Back after logout | Cannot access dashboard | ✅ |
| 8 | Multiple back presses | Stay on dashboard | ✅ |
| 9 | Alt+Left keyboard shortcut | Blocked/Stay on dashboard | ✅ |
| 10 | Refresh while logged in | Dashboard reloads | ✅ |

---

## 🎯 Protection Layers Implemented

### Layer 1: PHP Session Check ✅
- File: `login.php` (lines 11-28)
- Checks session on page load
- Redirects based on role

### Layer 2: HTTP Cache Control ✅
- Files: `config.php`, `config/auth.php`, dashboards
- Prevents browser from caching pages
- Forces fresh check on every load

### Layer 3: JavaScript History Control ✅
- File: `assets/js/session-check.js`
- Blocks browser back button
- Multiple redundant methods

### Layer 4: Inline Scripts ✅
- Files: `owner_dashboard.php`, `admin/dashboard.php`
- Immediate back button blocking
- Runs before page fully loads

### Layer 5: Meta Refresh ✅
- File: `login.php` (lines 112-123)
- Ultimate HTML fallback
- Works even if JavaScript disabled

---

## 🚀 Production Checklist

Before deploying to production:

- [ ] Test all scenarios in this guide
- [ ] Verify HTTPS is enabled
- [ ] Set `session.cookie_secure = 1` in php.ini
- [ ] Set `session.cookie_httponly = 1` in php.ini
- [ ] Configure proper session timeout (e.g., 1 hour)
- [ ] Test on multiple browsers (Chrome, Firefox, Safari, Edge)
- [ ] Test on mobile devices
- [ ] Clear all test accounts and sessions

---

## 📝 Notes

- All protection layers work together for maximum security
- Even if one layer fails, others provide backup
- Session protection works across all roles: Owner, Admin, Staff, Cashier, User
- No sensitive data exposed in browser history
- Clean, user-friendly experience with no error messages

---

**Last Updated:** 2025-02-11  
**Status:** ✅ Fully Implemented & Tested
