# Helper Functions Documentation

This folder contains comprehensive utility functions for the Paghilom Café Management System.

## Files Overview

### 1. **config.php**
Main database and application configuration file.
- Database connection setup
- Application constants (APP_NAME, APP_URL, etc.)
- CSRF token management
- Basic authentication helpers (is_logged_in, is_admin, is_staff)
- Utility functions (e(), generate_token, validate_password_strength)
- Settings management (get_setting, set_setting)

### 2. **helpers.php**
General-purpose helper functions for common tasks.

#### Image & File Functions
- `upload_product_image()` - Upload and process product images
- `resize_image()` - Resize images to fit dimensions
- `sanitize_filename()` - Clean filenames for safe storage

#### Formatting Functions
- `format_phone()` - Format Philippine phone numbers
- `format_date()` - Format dates for display
- `format_currency()` - Format currency with ₱ symbol
- `time_ago()` - Convert dates to relative time
- `truncate_string()` - Shorten text with ellipsis

#### Order & Product Functions
- `generate_order_code()` - Create unique order codes
- `generate_voucher_code()` - Create voucher codes
- `calculate_order_total()` - Calculate order totals with items
- `is_low_stock()` - Check if product stock is low
- `is_product_available()` - Verify product availability
- `deduct_product_stock()` - Reduce product stock
- `add_product_stock()` - Increase product stock

#### Cart Functions
- `get_cart()` - Retrieve shopping cart
- `add_to_cart()` - Add items to cart
- `remove_from_cart()` - Remove cart items
- `clear_cart()` - Empty the cart
- `get_cart_total()` - Calculate cart total
- `get_cart_count()` - Get total items in cart

#### Database Retrieval
- `get_client_by_id()` - Fetch client data
- `get_product_by_id()` - Fetch product data
- `get_order_by_id()` - Fetch order data
- `get_order_items()` - Get items for an order
- `get_active_categories()` - List active categories
- `get_products_by_category()` - Products by category

#### Status & Display
- `get_status_badge_color()` - Order status colors
- `get_payment_badge_color()` - Payment status colors
- `has_permission()` - Check user permissions
- `is_user_online()` - Check if user is active

#### Validation
- `validate_email()` - Validate email format
- `validate_phone()` - Validate Philippine phone numbers
- `generate_random_string()` - Random string generation
- `calculate_points()` - Calculate loyalty points (₱5 = 1 point)

#### Notifications
- `create_notification()` - Create user notifications
- `get_unread_notifications_count()` - Count unread notifications
- `log_activity()` - Log user activities
- `send_email()` - Send email notifications

#### QR Code
- `generate_qr_data()` - Generate QR code data
- `validate_qr_code()` - Validate QR codes with expiry

### 3. **db_helper.php**
Database abstraction layer for simplified database operations.

#### Query Functions
- `db_query()` - Execute prepared query, return all results
- `db_query_single()` - Execute query, return single row
- `db_insert()` - Insert record, return insert ID
- `db_update()` - Update records
- `db_delete()` - Delete records
- `db_count()` - Count records
- `db_exists()` - Check if record exists
- `db_paginate()` - Get paginated results

#### Transaction Functions
- `db_begin_transaction()` - Start transaction
- `db_commit()` - Commit transaction
- `db_rollback()` - Rollback transaction
- `db_transaction()` - Execute callback in transaction

#### Utility Functions
- `db_escape()` - Escape strings for SQL
- `db_last_insert_id()` - Get last insert ID
- `db_affected_rows()` - Get affected rows count
- `db_build_where()` - Build WHERE clause from array
- `db_bulk_insert()` - Insert multiple records

### 4. **email_helper.php**
Email sending functions with HTML templates.

#### Email Functions
- `sendVerificationEmail()` - Send account verification email
- `sendPasswordResetEmail()` - Send password reset email
- `sendOrderConfirmationEmail()` - Send order confirmation
- `sendOrderStatusEmail()` - Send order status updates
- `sendLowStockAlert()` - Send low stock alerts
- `sendCustomEmail()` - Send custom emails
- `getEmailTemplate()` - Get standard email template

### 5. **session_helper.php**
Session management and security functions.

#### Session Management
- `init_secure_session()` - Initialize secure session
- `set_session()` - Set session value
- `get_session()` - Get session value
- `unset_session()` - Remove session value
- `has_session()` - Check if session key exists
- `destroy_session()` - Destroy all session data
- `regenerate_session()` - Regenerate session ID

#### User Session
- `set_user_session()` - Set user session data
- `get_user_session()` - Get user session data
- `is_user_logged_in()` - Check if user is logged in
- `get_current_user_id()` - Get logged-in user ID
- `get_current_user_role()` - Get user role
- `is_current_user_admin()` - Check if admin
- `is_current_user_staff()` - Check if staff
- `user_has_role()` - Check specific role

#### Flash Messages
- `set_flash()` - Set flash message
- `get_flash()` - Get and clear flash messages
- `has_flash()` - Check for flash messages
- `display_flash()` - Display flash messages HTML

#### Remember Me
- `set_remember_cookie()` - Set remember me cookie
- `get_remember_cookie()` - Get remember me token
- `delete_remember_cookie()` - Delete remember me cookie

#### Form Handling
- `set_old_input()` - Save form input
- `old()` - Get old input value
- `clear_old_input()` - Clear old input
- `set_errors()` - Set validation errors
- `get_errors()` - Get all errors
- `get_error()` - Get single error
- `has_error()` - Check for errors
- `display_error()` - Display error message

#### Security
- `check_session_timeout()` - Check for session timeout
- `prevent_session_fixation()` - Prevent session fixation attacks

### 6. **validation_helper.php**
Input validation with fluent interface.

#### Validator Class
Create validator with: `$validator = validate($data);`

**Validation Methods:**
- `required($field)` - Field is required
- `email($field)` - Valid email format
- `min($field, $length)` - Minimum length
- `max($field, $length)` - Maximum length
- `numeric($field)` - Must be numeric
- `integer($field)` - Must be integer
- `minValue($field, $min)` - Minimum value
- `maxValue($field, $max)` - Maximum value
- `matches($field, $matchField)` - Must match another field
- `url($field)` - Valid URL format
- `date($field, $format)` - Valid date format
- `phone($field)` - Valid Philippine phone number
- `in($field, $values)` - Value must be in array
- `unique($field, $table, $column)` - Unique in database
- `exists($field, $table, $column)` - Exists in database
- `regex($field, $pattern)` - Match regex pattern
- `strongPassword($field)` - Strong password requirements
- `custom($field, $callback)` - Custom validation function

**Result Methods:**
- `fails()` - Check if validation failed
- `passes()` - Check if validation passed
- `errors()` - Get all errors
- `firstError()` - Get first error
- `getError($field)` - Get error for field

#### Helper Functions
- `sanitize()` - Sanitize input string
- `sanitize_array()` - Sanitize array of inputs
- `clean_filename()` - Clean filename
- `validate_upload()` - Validate file upload
- `validate_image()` - Validate image upload
- `validate_csrf()` - Validate CSRF token
- `generate_csrf()` - Generate CSRF token
- `csrf_input()` - CSRF input field HTML
- `csrf_meta()` - CSRF meta tag HTML

## Usage Examples

### Validation Example
```php
require_once 'includes/validation_helper.php';

$validator = validate($_POST)
    ->required('email', 'Email is required')
    ->email('email', 'Invalid email address')
    ->required('password')
    ->min('password', 8)
    ->strongPassword('password');

if ($validator->fails()) {
    $errors = $validator->errors();
    set_errors($errors);
    redirect_back();
}
```

### Database Example
```php
require_once 'includes/db_helper.php';

// Insert a product
$product_id = db_insert($mysqli, 'products', [
    'name' => 'Caramel Macchiato',
    'price' => 150.00,
    'stock_qty' => 50
]);

// Update product
db_update($mysqli, 'products', 
    ['stock_qty' => 45], 
    'id = ?', 
    'i', 
    [$product_id]
);

// Query with pagination
$results = db_paginate($mysqli, 
    "SELECT * FROM products WHERE is_active = 1", 
    $page, 
    20
);
```

### Session Example
```php
require_once 'includes/session_helper.php';

// Set user session
set_user_session($user_data);

// Set flash message
set_flash('success', 'Login successful!');

// Check if admin
if (is_current_user_admin()) {
    // Admin only code
}

// Display flash messages
echo display_flash();
```

### Email Example
```php
require_once 'includes/email_helper.php';

sendOrderConfirmationEmail(
    $customer_email,
    $customer_name,
    $order_number,
    [
        'items' => $order_items,
        'total' => $order_total
    ]
);
```

## Best Practices

1. **Always use prepared statements** for database queries
2. **Sanitize user input** before displaying
3. **Validate CSRF tokens** on form submissions
4. **Use flash messages** for user feedback
5. **Check permissions** before sensitive operations
6. **Log important activities** for audit trails
7. **Use transactions** for multi-step database operations
8. **Validate file uploads** before processing
9. **Set appropriate session timeouts**
10. **Use strong password validation** for user accounts

## Security Features

- CSRF token generation and validation
- Session fixation prevention
- Secure session configuration (HTTPOnly, Secure cookies)
- SQL injection prevention via prepared statements
- XSS prevention via output escaping
- Password strength validation
- File upload validation
- Session timeout management

## Dependencies

- PHP 7.4+
- MySQL/MariaDB
- GD Library (for image processing)
- MySQLi extension

---

**Created for Paghilom Café Management System**  
For questions or issues, refer to the main project documentation.
