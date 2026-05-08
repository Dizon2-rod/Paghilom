<?php
/**
 * QR Code Generation Helper
 * Generates secure, unique QR codes for orders and rewards
 */

if (!function_exists('generate_order_qr_code')) {
    /**
     * Generate a unique QR code for an order
     * @param int $order_id The order ID
     * @param bool $with_token Whether to return JSON with security token
     * @return string The generated QR code (format: ORD + 8 character hash or JSON)
     */
    function generate_order_qr_code($order_id, $with_token = false) {
        // Generate a unique hash using order ID + timestamp + random bytes
        $hash = strtoupper(substr(md5($order_id . time() . random_bytes(16)), 0, 8));
        $code = 'ORD' . $hash;
        
        if ($with_token) {
            // Generate security token
            $token = hash_hmac('sha256', $code . $order_id . time(), 'paghilom_secret_key');
            return json_encode([
                'type' => 'order',
                'code' => $code,
                'token' => substr($token, 0, 16)
            ]);
        }
        
        return $code;
    }
}

if (!function_exists('generate_reward_qr_code')) {
    /**
     * Generate a unique QR code for a reward/voucher
     * @param int $reward_id The reward ID
     * @param bool $with_token Whether to return JSON with security token
     * @return string The generated QR code (format: PHC- + 8 character hash or JSON)
     */
    function generate_reward_qr_code($reward_id, $with_token = false) {
        // Generate a unique hash using reward ID + timestamp + random bytes
        $hash = strtoupper(substr(md5($reward_id . time() . random_bytes(16)), 0, 8));
        $code = 'PHC-' . $hash;
        
        if ($with_token) {
            // Generate security token
            $token = hash_hmac('sha256', $code . $reward_id . time(), 'paghilom_secret_key');
            return json_encode([
                'type' => 'reward',
                'code' => $code,
                'token' => substr($token, 0, 16)
            ]);
        }
        
        return $code;
    }
}

if (!function_exists('validate_qr_code')) {
    /**
     * Validate a QR code from the database
     * @param mysqli $mysqli Database connection
     * @param string $qr_code The QR code to validate
     * @return array|null Returns order/reward data if valid, null if invalid
     */
    function validate_qr_code($mysqli, $qr_code) {
        $qr_code = trim($qr_code);
        
        // Check if it's an order QR (ORD prefix)
        if (preg_match('/^ORD[A-Z0-9]{6,}$/i', $qr_code)) {
            $stmt = $mysqli->prepare(
                "SELECT id, code, total_amount, payment_status, status, created_at, qr_expires_at 
                 FROM orders 
                 WHERE code = ? 
                 LIMIT 1"
            );
            $stmt->bind_param('s', $qr_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
            
            if (!$order) {
                return null; // QR code not found
            }
            
            // Check QR expiry (3 hours from creation)
            $expiry = $order['qr_expires_at'] ? strtotime($order['qr_expires_at']) : strtotime($order['created_at'] . ' +3 hours');
            if (time() > $expiry) {
                return ['error' => 'Order QR code has expired (valid for 3 hours)', 'code' => $qr_code];
            }
            
            // Check if already paid
            if ($order['payment_status'] === 'paid') {
                return ['error' => 'Order already paid', 'code' => $qr_code];
            }
            
            // Check if cancelled
            if ($order['status'] === 'cancelled') {
                return ['error' => 'Order has been cancelled', 'code' => $qr_code];
            }
            
            return [
                'type' => 'order',
                'id' => $order['id'],
                'code' => $qr_code,
                'amount' => $order['total_amount'],
                'expires_at' => $order['qr_expires_at'] ?: date('Y-m-d H:i:s', $expiry),
                'valid' => true
            ];
        }
        
        // Check if it's a reward QR (PHC- prefix)
        if (preg_match('/^PHC-[A-Z0-9]{6,}$/i', $qr_code)) {
            $stmt = $mysqli->prepare(
                "SELECT id, voucher_code, points_required, status, created_at, expires_at 
                 FROM vouchers 
                 WHERE voucher_code = ? 
                 LIMIT 1"
            );
            $stmt->bind_param('s', $qr_code);
            $stmt->execute();
            $result = $stmt->get_result();
            $reward = $result->fetch_assoc();
            $stmt->close();
            
            if (!$reward) {
                return null; // QR code not found
            }
            
            // Check if already redeemed
            if ($reward['status'] === 'redeemed') {
                return ['error' => 'Voucher already redeemed', 'code' => $qr_code];
            }
            
            // Check if expired (30 minutes from creation)
            $expiry = !empty($reward['expires_at']) ? strtotime($reward['expires_at']) : strtotime($reward['created_at'] . ' +30 minutes');
            if ($expiry !== false && time() > $expiry) {
                return ['error' => 'Redemption QR code has expired (valid for 30 minutes)', 'code' => $qr_code];
            }
            
            return [
                'type' => 'reward',
                'id' => $reward['id'],
                'code' => $qr_code,
                'points' => $reward['points_required'],
                'expires_at' => $reward['expires_at'] ?: date('Y-m-d H:i:s', $expiry),
                'valid' => true
            ];
        }
        
        // Invalid format
        return null;
    }
}

if (!function_exists('mark_qr_code_as_used')) {
    /**
     * Mark a QR code as used/redeemed after successful payment
     * @param mysqli $mysqli Database connection
     * @param string $qr_code The QR code that was used
     * @param string $type Either 'order' or 'reward'
     * @return bool Success status
     */
    function mark_qr_code_as_used($mysqli, $qr_code, $type) {
        if ($type === 'order') {
            $stmt = $mysqli->prepare(
                "UPDATE orders 
                 SET payment_status = 'paid', 
                     status = 'completed',
                     paid_at = NOW() 
                 WHERE order_code = ?"
            );
            $stmt->bind_param('s', $qr_code);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        
        if ($type === 'reward') {
            $stmt = $mysqli->prepare(
                "UPDATE vouchers 
                 SET status = 'redeemed', 
                     redeemed_at = NOW() 
                 WHERE voucher_code = ?"
            );
            $stmt->bind_param('s', $qr_code);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        
        return false;
    }
}

if (!function_exists('generate_qr_code_image')) {
    /**
     * Generate QR code image using Google Charts API (fallback)
     * For production, consider using a PHP QR code library like endroid/qr-code
     * @param string $qr_code The QR code text
     * @param int $size Image size in pixels
     * @return string URL to QR code image
     */
    function generate_qr_code_image($qr_code, $size = 300) {
        return 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($qr_code);
    }
}
