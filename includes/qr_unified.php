<?php
/**
 * Unified QR Code System
 * Ensures ALL QR codes generated are scannable and valid
 * 
 * This system provides:
 * - Consistent QR code format across all modules
 * - Secure token-based validation
 * - High-quality scannable QR images
 * - Automatic payment redirection
 */

// Secret key for generating security tokens (change this in production!)
define('QR_SECRET_KEY', 'paghilom_qr_secret_2024_change_this_key');

if (!function_exists('generate_secure_qr_code')) {
    /**
     * Generate a secure, scannable QR code for orders or rewards
     * 
     * @param mysqli $mysqli Database connection
     * @param string $type Type: 'order' or 'reward'
     * @param int $id Database ID of order or reward
     * @param array $data Additional data to include
     * @return array QR code data with code, token, and image URL
     */
    function generate_secure_qr_code($mysqli, $type, $id, $data = []) {
        // Generate unique reference code
        if ($type === 'order') {
            $prefix = 'ORD';
            $table = 'orders';
            $code_col = 'code'; // Using 'code' column as per checkout.php
        } elseif ($type === 'reward') {
            $prefix = 'PHC';
            $table = 'vouchers';
            $code_col = 'voucher_code';
        } else {
            return ['error' => 'Invalid QR type'];
        }
        
        // Check if code already exists
        $stmt = $mysqli->prepare("SELECT $code_col FROM $table WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing && !empty($existing[$code_col])) {
            // Use existing code
            $code = $existing[$code_col];
        } else {
            // Generate new unique code
            $attempts = 0;
            do {
                $hash = strtoupper(substr(md5($id . microtime(true) . random_bytes(8)), 0, 8));
                $code = $prefix . ($prefix === 'PHC' ? '-' : '') . $hash;
                
                // Check uniqueness
                $stmt = $mysqli->prepare("SELECT id FROM $table WHERE $code_col = ? LIMIT 1");
                $stmt->bind_param('s', $code);
                $stmt->execute();
                $exists = $stmt->get_result()->num_rows > 0;
                $stmt->close();
                
                $attempts++;
            } while ($exists && $attempts < 10);
            
            // Update database with generated code
            $stmt = $mysqli->prepare("UPDATE $table SET $code_col = ? WHERE id = ?");
            $stmt->bind_param('si', $code, $id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Generate secure token
        $token = generate_qr_token($code, $type, $id);
        
        // Build QR data payload
        $qr_payload = json_encode([
            'type' => $type,
            'code' => $code,
            'token' => $token,
            'id' => $id,
            'timestamp' => time()
        ], JSON_UNESCAPED_SLASHES);
        
        // Generate high-quality QR image
        $qr_image = generate_qr_image($qr_payload);
        
        return [
            'success' => true,
            'code' => $code,
            'token' => $token,
            'qr_payload' => $qr_payload,
            'qr_image' => $qr_image,
            'type' => $type,
            'id' => $id
        ];
    }
}

if (!function_exists('generate_qr_token')) {
    /**
     * Generate secure token for QR code validation
     * 
     * @param string $code QR code
     * @param string $type Type (order/reward)
     * @param int $id Database ID
     * @return string Secure token
     */
    function generate_qr_token($code, $type, $id) {
        $data = $code . '|' . $type . '|' . $id . '|' . date('Y-m-d');
        return substr(hash_hmac('sha256', $data, QR_SECRET_KEY), 0, 16);
    }
}

if (!function_exists('validate_qr_token')) {
    /**
     * Validate QR code security token
     * 
     * @param string $code QR code
     * @param string $type Type (order/reward)
     * @param int $id Database ID
     * @param string $token Token to validate
     * @return bool True if valid
     */
    function validate_qr_token($code, $type, $id, $token) {
        // Check today's token
        $expected_today = generate_qr_token($code, $type, $id);
        if (hash_equals($expected_today, $token)) {
            return true;
        }
        
        // Check yesterday's token (grace period for timezone issues)
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $data_yesterday = $code . '|' . $type . '|' . $id . '|' . $yesterday;
        $expected_yesterday = substr(hash_hmac('sha256', $data_yesterday, QR_SECRET_KEY), 0, 16);
        if (hash_equals($expected_yesterday, $token)) {
            return true;
        }
        
        return false;
    }
}

if (!function_exists('generate_qr_image')) {
    /**
     * Generate high-quality QR code image
     * 
     * @param string $data Data to encode
     * @param int $size Image size in pixels
     * @return string Base64 data URL or external URL
     */
    function generate_qr_image($data, $size = 300) {
        // Check if phpqrcode library exists
        $qrlib = __DIR__ . '/../vendor/phpqrcode/qrlib.php';
        
        if (file_exists($qrlib)) {
            require_once $qrlib;
            
            ob_start();
            \QRcode::png($data, false, QR_ECLEVEL_H, 10, 2);
            $imageData = ob_get_clean();
            
            return 'data:image/png;base64,' . base64_encode($imageData);
        }
        
        // Fallback to Google Charts API
        $params = http_build_query([
            'chs' => $size . 'x' . $size,
            'cht' => 'qr',
            'chl' => $data,
            'choe' => 'UTF-8',
            'chld' => 'H|2'
        ]);
        
        return 'https://chart.googleapis.com/chart?' . $params;
    }
}

if (!function_exists('validate_unified_qr')) {
    /**
     * Universal QR code validation for POS and Kiosk
     * Validates both order and reward QR codes
     * 
     * @param mysqli $mysqli Database connection
     * @param string $qr_data Raw QR code data (string or JSON)
     * @return array Validation result with redirect URL
     */
    function validate_unified_qr($mysqli, $qr_data) {
        $qr_data = trim($qr_data);
        
        if (empty($qr_data)) {
            return [
                'success' => false,
                'error' => 'Empty QR code',
                'message' => 'No QR code data provided'
            ];
        }
        
        // If it's a URL, try to extract code parameters first (supports legacy receipts)
        if (filter_var($qr_data, FILTER_VALIDATE_URL)) {
            $u = parse_url($qr_data);
            if (!empty($u['query'])) {
                parse_str($u['query'], $q);
                // Common params used across the app
                $candidate = $q['code'] ?? $q['order'] ?? $q['voucher'] ?? null;
                if ($candidate) {
                    // Replace raw data with extracted code for downstream detection
                    $qr_data = trim((string)$candidate);
                }
            }
        }
        
        // Try to parse as JSON first
        $parsed = null;
        $is_json = false;
        
        try {
            $parsed = json_decode($qr_data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Accept both unified JSON ({type, code}) and legacy JSON ({code, ...})
                if (isset($parsed['type']) && isset($parsed['code'])) {
                    $is_json = true;
                } elseif (isset($parsed['code'])) {
                    // Infer type from code prefix if possible (legacy payload)
                    $is_json = true;
                    $parsed['type'] = preg_match('/^ORD/i', $parsed['code'] ?? '') ? 'order' : (preg_match('/^PHC-/i', $parsed['code'] ?? '') ? 'reward' : null);
                }
            }
        } catch (Exception $e) {
            // Not JSON, continue
        }
        
        // Extract data
        if ($is_json) {
            $type = $parsed['type'] ?? null;
            $code = $parsed['code'] ?? '';
            $token = $parsed['token'] ?? null;
            $id = $parsed['id'] ?? null;
            // If type is still unknown, infer from code pattern
            if (!$type) {
                if (preg_match('/^ORD[A-Z0-9]{6,}$/i', $code)) $type = 'order';
                elseif (preg_match('/^PHC-[A-Z0-9]{6,}$/i', $code)) $type = 'reward';
            }
            if (!$code) {
                // Try to extract code from raw JSON text
                if (preg_match('/(ORD[A-Z0-9]{6,})/i', $qr_data, $m)) {
                    $code = strtoupper($m[1]);
                } elseif (preg_match('/(PHC-[A-Z0-9]{6,})/i', $qr_data, $m)) {
                    $code = strtoupper($m[1]);
                } else {
                    // Try common JSON keys as fallback
                    foreach (['order','voucher','reference','ref','qr','qr_code'] as $k) {
                        if (!empty($parsed[$k]) && is_string($parsed[$k])) {
                            $cand = strtoupper($parsed[$k]);
                            if (preg_match('/^ORD[A-Z0-9]{6,}$/', $cand) || preg_match('/^PHC-[A-Z0-9]{6,}$/', $cand)) {
                                $code = $cand; break;
                            }
                        }
                    }
                }
            }
            if (!$type) {
                if (preg_match('/^ORD[A-Z0-9]{6,}$/i', $code)) $type = 'order';
                elseif (preg_match('/^PHC-[A-Z0-9]{6,}$/i', $code)) $type = 'reward';
            }
            if (!$type || !$code) {
                return [
                    'success' => false,
                    'error' => 'Invalid QR format',
                    'message' => 'QR JSON missing required fields'
                ];
            }
        } else {
            // Plain code format (ORD##### or PHC-#####)
            $code = $qr_data;
            $token = null;
            $id = null;
            
            // Detect type from code format
            if (preg_match('/^ORD[A-Z0-9]{6,}$/i', $code)) {
                $type = 'order';
            } elseif (preg_match('/^PHC-[A-Z0-9]{6,}$/i', $code)) {
                $type = 'reward';
            } else {
                // Lenient fallback: try to extract known patterns from raw string
                if (preg_match('/(ORD[A-Z0-9]{6,})/i', $qr_data, $m)) {
                    $type = 'order';
                    $code = strtoupper($m[1]);
                } elseif (preg_match('/(PHC-[A-Z0-9]{6,})/i', $qr_data, $m)) {
                    $type = 'reward';
                    $code = strtoupper($m[1]);
                } else {
                    return [
                        'success' => false,
                        'error' => 'Invalid QR format',
                        'message' => 'QR code format not recognized'
                    ];
                }
            }
        }
        
        // Validate based on type
        if ($type === 'order') {
            return validate_order_qr($mysqli, $code, $token, $id);
        } elseif ($type === 'reward') {
            return validate_reward_qr($mysqli, $code, $token, $id);
        } else {
            return [
                'success' => false,
                'error' => 'Unknown type',
                'message' => 'QR code type not supported'
            ];
        }
    }
}

if (!function_exists('validate_order_qr')) {
    /**
     * Validate order QR code
     */
    function validate_order_qr($mysqli, $code, $token = null, $id = null) {
        require_once __DIR__ . '/qr_expiry_helper.php';
        
        $stmt = $mysqli->prepare(
            "SELECT id, code, total_amount, payment_status, status, created_at, qr_expires_at 
             FROM orders 
             WHERE code = ? 
             LIMIT 1"
        );
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            return [
                'success' => false,
                'error' => 'Order not found',
                'message' => 'This order code does not exist in our system'
            ];
        }
        
        // Validate token if provided
        if ($token && $id) {
            if (!validate_qr_token($code, 'order', $id, $token)) {
                return [
                    'success' => false,
                    'error' => 'Invalid token',
                    'message' => 'QR code security validation failed'
                ];
            }
        }
        
        // Check expiry
        $expiry = $order['qr_expires_at'] ? strtotime($order['qr_expires_at']) : strtotime($order['created_at'] . ' +3 hours');
        if (time() > $expiry) {
            return [
                'success' => false,
                'error' => 'QR expired',
                'message' => 'Order QR code has expired (valid for 3 hours)'
            ];
        }
        
        // Check payment status
        if ($order['payment_status'] === 'paid') {
            return [
                'success' => false,
                'error' => 'Already paid',
                'message' => 'This order has already been paid'
            ];
        }
        
        // Check order status
        if ($order['status'] === 'cancelled') {
            return [
                'success' => false,
                'error' => 'Order cancelled',
                'message' => 'This order has been cancelled'
            ];
        }
        
        // Valid - return redirect URL
        return [
            'success' => true,
            'type' => 'order',
            'id' => $order['id'],
            'code' => $code,
            'amount' => $order['total_amount'],
            'redirect_url' => 'payment/payment_process.php?code=' . urlencode($code) . '&type=order',
            'message' => 'Valid order QR code'
        ];
    }
}

if (!function_exists('validate_reward_qr')) {
    /**
     * Validate reward/voucher QR code
     */
    function validate_reward_qr($mysqli, $code, $token = null, $id = null) {
        require_once __DIR__ . '/qr_expiry_helper.php';
        
        $stmt = $mysqli->prepare(
            "SELECT id, voucher_code, points_required, status, created_at, expires_at 
             FROM vouchers 
             WHERE voucher_code = ? 
             LIMIT 1"
        );
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $voucher = $result->fetch_assoc();
        $stmt->close();
        
        if (!$voucher) {
            return [
                'success' => false,
                'error' => 'Voucher not found',
                'message' => 'This reward voucher does not exist in our system'
            ];
        }
        
        // Validate token if provided
        if ($token && $id) {
            if (!validate_qr_token($code, 'reward', $id, $token)) {
                return [
                    'success' => false,
                    'error' => 'Invalid token',
                    'message' => 'QR code security validation failed'
                ];
            }
        }
        
        // Check expiry
        $expiry = !empty($voucher['expires_at']) ? strtotime($voucher['expires_at']) : strtotime($voucher['created_at'] . ' +30 minutes');
        if ($expiry !== false && time() > $expiry) {
            return [
                'success' => false,
                'error' => 'QR expired',
                'message' => 'Reward QR code has expired (valid for 30 minutes)'
            ];
        }
        
        // Check status
        if ($voucher['status'] === 'redeemed') {
            return [
                'success' => false,
                'error' => 'Already redeemed',
                'message' => 'This voucher has already been redeemed'
            ];
        }
        
        // Valid - return redirect URL
        return [
            'success' => true,
            'type' => 'reward',
            'id' => $voucher['id'],
            'code' => $code,
            'points' => $voucher['points_required'],
            'redirect_url' => 'payment/payment_process.php?code=' . urlencode($code) . '&type=reward',
            'message' => 'Valid reward QR code'
        ];
    }
}
?>
