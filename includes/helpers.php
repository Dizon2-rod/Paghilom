<?php
/**
 * Helper Functions for Paghilom Cafe Management System
 */

/**
 * Upload and process product image
 */
function upload_product_image($file, $product_id, $mysqli) {
    $upload_dir = __DIR__ . '/../uploads/products/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        return ['success' => false, 'error' => 'File too large'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'prod_' . $product_id . '_' . time() . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Resize image if needed
    $resized = resize_image($file['tmp_name'], $filepath, 1200, 1200);
    
    if (!$resized) {
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => false, 'error' => 'Failed to move file'];
        }
    }
    
    return ['success' => true, 'filename' => $filename];
}

/**
 * Resize image to max dimensions
 */
function resize_image($source, $destination, $max_width, $max_height) {
    $image_info = getimagesize($source);
    if (!$image_info) return false;
    
    list($orig_width, $orig_height, $type) = $image_info;
    
    // Check if resize is needed
    if ($orig_width <= $max_width && $orig_height <= $max_height) {
        return false;
    }
    
    // Calculate new dimensions
    $ratio = min($max_width / $orig_width, $max_height / $orig_height);
    $new_width = intval($orig_width * $ratio);
    $new_height = intval($orig_height * $ratio);
    
    // Create image from source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $src_img = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $src_img = imagecreatefrompng($source);
            break;
        case IMAGETYPE_WEBP:
            $src_img = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    if (!$src_img) return false;
    
    // Create new image
    $dst_img = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($dst_img, false);
        imagesavealpha($dst_img, true);
    }
    
    // Resize
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height);
    
    // Save
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($dst_img, $destination, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($dst_img, $destination, 8);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($dst_img, $destination, 85);
            break;
    }
    
    imagedestroy($src_img);
    imagedestroy($dst_img);
    
    return true;
}

/**
 * Format phone number
 */
function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
        return '(' . substr($phone, 0, 4) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7);
    }
    
    return $phone;
}

/**
 * Generate order code
 */
function generate_order_code() {
    return 'ORD' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

/**
 * Generate voucher code
 */
function generate_voucher_code() {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12));
}

/**
 * Check if product is low stock
 */
function is_low_stock($product) {
    return $product['stock_qty'] <= $product['low_stock_threshold'];
}

/**
 * Get order status badge color
 */
function get_status_badge_color($status) {
    $colors = [
        'pending' => 'warning',
        'queued' => 'info',
        'in_progress' => 'primary',
        'ready' => 'success',
        'paid' => 'success',
        'fulfilled' => 'success',
        'completed' => 'success',
        'cancelled' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}

/**
 * Get payment status badge color
 */
function get_payment_badge_color($status) {
    $colors = [
        'unpaid' => 'warning',
        'paid' => 'success',
        'refunded' => 'danger'
    ];
    
    return $colors[$status] ?? 'secondary';
}

/**
 * Format date for display
 */
function format_date($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}

/**
 * Calculate order total with items and addons
 */
function calculate_order_total($order_id, $mysqli) {
    $stmt = $mysqli->prepare("
        SELECT 
            (SELECT SUM(qty * price) FROM order_items WHERE order_id = ?) +
            (SELECT IFNULL(SUM(price), 0) FROM order_item_options WHERE order_item_id IN (SELECT id FROM order_items WHERE order_id = ?))
        as total
    ");
    $stmt->bind_param('ii', $order_id, $order_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return floatval($result['total'] ?? 0);
}

/**
 * Send email notification
 */
function send_email($to, $subject, $message) {
    // For development, just log
    error_log("Email to $to: $subject");
    error_log($message);
    
    // In production, implement actual email sending using PHPMailer or similar
    return true;
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $details, $mysqli) {
    $stmt = $mysqli->prepare("INSERT INTO activity_log (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('iss', $user_id, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Sanitize filename
 */
function sanitize_filename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    return $filename;
}

/**
 * Get user's full name
 */
function get_user_name($user_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result ? $result['name'] : 'Unknown User';
}

/**
 * Check if user has permission
 */
function has_permission($permission) {
    if (!isset($_SESSION['user'])) return false;
    
    $role = $_SESSION['user']['role'] ?? '';
    
    // Define role permissions (only admin, staff, customer)
    $permissions = [
        'admin'    => ['view_orders', 'manage_products', 'manage_users', 'view_reports'],
        'staff'    => ['view_orders', 'process_orders', 'view_products'],
        'customer' => ['view_orders']
    ];
    
    return in_array($permission, $permissions[$role] ?? []);
}

/**
 * Generate QR code data
 */
function generate_qr_data($type, $data) {
    return json_encode([
        'type' => $type,
        'data' => $data,
        'timestamp' => time()
    ]);
}

/**
 * Validate QR code
 */
function validate_qr_code($qr_data, $max_age = 3600) {
    $decoded = json_decode($qr_data, true);
    
    if (!$decoded || !isset($decoded['timestamp'])) {
        return false;
    }
    
    // Check if not expired
    if ((time() - $decoded['timestamp']) > $max_age) {
        return false;
    }
    
    return $decoded;
}

/**
 * Format currency
 */
function format_currency($amount, $currency = '₱') {
    return $currency . number_format((float)$amount, 2);
}

/**
 * Get cart from session
 */
function get_cart() {
    return $_SESSION['cart'] ?? [];
}

/**
 * Add item to cart
 */
function add_to_cart($product_id, $quantity = 1, $options = []) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $cart_key = $product_id . '_' . md5(json_encode($options));
    
    if (isset($_SESSION['cart'][$cart_key])) {
        $_SESSION['cart'][$cart_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cart_key] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'options' => $options
        ];
    }
}

/**
 * Remove item from cart
 */
function remove_from_cart($cart_key) {
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
    }
}

/**
 * Clear cart
 */
function clear_cart() {
    $_SESSION['cart'] = [];
}

/**
 * Get cart total
 */
function get_cart_total($mysqli) {
    $cart = get_cart();
    $total = 0;
    
    foreach ($cart as $item) {
        $stmt = $mysqli->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->bind_param('i', $item['product_id']);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if ($product) {
            $total += $product['price'] * $item['quantity'];
        }
    }
    
    return $total;
}

/**
 * Get cart count
 */
function get_cart_count() {
    $cart = get_cart();
    $count = 0;
    
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

/**
 * Validate email format
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Philippine format)
 */
function validate_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 11 && substr($phone, 0, 2) === '09';
}

/**
 * Generate random string
 */
function generate_random_string($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)))),1,$length);
}

/**
 * Check if user is online (last activity within 5 minutes)
 */
function is_user_online($user_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT last_login FROM users WHERE id = ? AND last_login > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Get client by ID
 */
function get_client_by_id($client_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->bind_param('i', $client_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get product by ID
 */
function get_product_by_id($product_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get order by ID
 */
function get_order_by_id($order_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get order items
 */
function get_order_items($order_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Calculate points from amount
 */
function calculate_points($amount) {
    // ₱5 = 1 point
    return floor($amount / 5);
}

/**
 * Get time ago string
 */
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

/**
 * Truncate string
 */
function truncate_string($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    return substr($string, 0, $length) . $append;
}

/**
 * Get setting value
 */
function get_setting_value($key, $default = '', $mysqli) {
    $stmt = $mysqli->prepare("SELECT `value` FROM settings WHERE `key` = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? $result['value'] : $default;
}

/**
 * Update or create setting
 */
function update_setting($key, $value, $mysqli) {
    $stmt = $mysqli->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
    $stmt->bind_param('sss', $key, $value, $value);
    return $stmt->execute();
}

/**
 * Check if product is available
 */
function is_product_available($product_id, $quantity, $mysqli) {
    $stmt = $mysqli->prepare("SELECT stock_qty, is_active FROM products WHERE id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product || !$product['is_active']) {
        return false;
    }
    
    return $product['stock_qty'] >= $quantity;
}

/**
 * Deduct product stock
 */
function deduct_product_stock($product_id, $quantity, $mysqli) {
    $stmt = $mysqli->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?");
    $stmt->bind_param('iii', $quantity, $product_id, $quantity);
    return $stmt->execute() && $mysqli->affected_rows > 0;
}

/**
 * Add product stock
 */
function add_product_stock($product_id, $quantity, $mysqli) {
    $stmt = $mysqli->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
    $stmt->bind_param('ii', $quantity, $product_id);
    return $stmt->execute();
}

/**
 * Get active categories
 */
function get_active_categories($mysqli) {
    $result = $mysqli->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get products by category
 */
function get_products_by_category($category_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE category_id = ? AND is_active = 1 ORDER BY sort_order, name");
    $stmt->bind_param('i', $category_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Create notification
 */
function create_notification($user_id, $title, $message, $type = 'info', $mysqli) {
    $stmt = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param('isss', $user_id, $title, $message, $type);
    return $stmt->execute();
}

/**
 * Get unread notifications count
 */
function get_unread_notifications_count($user_id, $mysqli) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result ? (int)$result['count'] : 0;
}
?>
