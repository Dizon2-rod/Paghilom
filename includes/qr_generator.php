<?php
/**
 * Enhanced QR Code Generator
 * Generates high-quality, instantly scannable QR codes
 */

if (!function_exists('generate_high_quality_qr')) {
    /**
     * Generate a high-quality QR code image
     * Uses phpqrcode library for better quality and reliability
     * 
     * @param string $data The data to encode
     * @param int $size Size in pixels (default 300)
     * @param string $format 'png' or 'svg' (default 'png')
     * @return string Base64 encoded image data URL or SVG string
     */
    function generate_high_quality_qr($data, $size = 300, $format = 'png') {
        // Check if phpqrcode library exists
        $qrlib = __DIR__ . '/../vendor/phpqrcode/qrlib.php';
        
        if (file_exists($qrlib)) {
            // Use phpqrcode library for high quality
            require_once $qrlib;
            
            ob_start();
            \QRcode::png($data, false, QR_ECLEVEL_H, 10, 2);
            $imageData = ob_get_clean();
            
            return 'data:image/png;base64,' . base64_encode($imageData);
        }
        
        // Fallback to Google Charts API with optimized settings
        $params = http_build_query([
            'chs' => $size . 'x' . $size,
            'cht' => 'qr',
            'chl' => $data,
            'choe' => 'UTF-8',
            'chld' => 'H|2' // High error correction + 2px margin
        ]);
        
        return 'https://chart.googleapis.com/chart?' . $params;
    }
}

if (!function_exists('generate_validated_order_qr')) {
    /**
     * Generate and validate an order QR code
     * Ensures uniqueness and proper format
     * 
     * @param mysqli $mysqli Database connection
     * @param int $order_id The order ID
     * @return string The generated QR code
     */
    function generate_validated_order_qr($mysqli, $order_id) {
        $max_attempts = 10;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            // Generate unique code
            $hash = strtoupper(substr(md5($order_id . microtime(true) . random_bytes(16)), 0, 8));
            $code = 'ORD' . $hash;
            
        // Check if code already exists
            $stmt = $mysqli->prepare("SELECT id FROM orders WHERE code = ? LIMIT 1");
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            
            if (!$exists) {
                return $code;
            }
            
            $attempt++;
        }
        
        // Fallback with timestamp if max attempts reached
        return 'ORD' . strtoupper(substr(md5($order_id . time() . uniqid()), 0, 8));
    }
}

if (!function_exists('generate_validated_reward_qr')) {
    /**
     * Generate and validate a reward QR code
     * Ensures uniqueness and proper format
     * 
     * @param mysqli $mysqli Database connection
     * @param int $reward_id The reward ID
     * @return string The generated QR code
     */
    function generate_validated_reward_qr($mysqli, $reward_id) {
        $max_attempts = 10;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            // Generate unique code
            $hash = strtoupper(substr(md5($reward_id . microtime(true) . random_bytes(16)), 0, 8));
            $code = 'PHC-' . $hash;
            
            // Check if code already exists
            $stmt = $mysqli->prepare("SELECT id FROM vouchers WHERE voucher_code = ? LIMIT 1");
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;
            $stmt->close();
            
            if (!$exists) {
                return $code;
            }
            
            $attempt++;
        }
        
        // Fallback with timestamp if max attempts reached
        return 'PHC-' . strtoupper(substr(md5($reward_id . time() . uniqid()), 0, 8));
    }
}

if (!function_exists('validate_qr_format')) {
    /**
     * Validate QR code format before database lookup
     * @param string $code The QR code to validate
     * @return array ['valid' => bool, 'type' => string|null, 'message' => string]
     */
    function validate_qr_format($code) {
        $code = trim($code);
        
        // Check for empty code
        if (empty($code)) {
            return ['valid' => false, 'type' => null, 'message' => 'Empty QR code'];
        }
        
        // Check for order QR (ORD + 6-12 alphanumeric)
        if (preg_match('/^ORD[A-Z0-9]{6,12}$/i', $code)) {
            return ['valid' => true, 'type' => 'order', 'message' => 'Valid order QR format'];
        }
        
        // Check for reward QR (PHC- + 6-12 alphanumeric)
        if (preg_match('/^PHC-[A-Z0-9]{6,12}$/i', $code)) {
            return ['valid' => true, 'type' => 'reward', 'message' => 'Valid reward QR format'];
        }
        
        // Check for alternative reward format (REW + alphanumeric)
        if (preg_match('/^REW[A-Z0-9]{6,12}$/i', $code)) {
            return ['valid' => true, 'type' => 'reward', 'message' => 'Valid reward QR format'];
        }
        
        // Check if it's JSON encoded
        try {
            $data = json_decode($code, true);
            if (isset($data['type']) && isset($data['code'])) {
                return ['valid' => true, 'type' => $data['type'], 'message' => 'Valid JSON QR format'];
            }
        } catch (Exception $e) {
            // Not JSON, continue
        }
        
        // Check if it's a URL with code parameter
        if (strpos($code, '?code=') !== false || strpos($code, '&code=') !== false) {
            return ['valid' => true, 'type' => 'url', 'message' => 'Valid URL QR format'];
        }
        
        return ['valid' => false, 'type' => null, 'message' => 'Invalid QR code format'];
    }
}

if (!function_exists('instant_validate_qr')) {
    /**
     * Instant QR validation optimized for speed
     * @param mysqli $mysqli Database connection
     * @param string $code The QR code to validate
     * @return array Result with validation status and redirect info
     */
    function instant_validate_qr($mysqli, $code) {
        $start_time = microtime(true);
        
        // Step 1: Format validation (instant, no DB query)
        $format_check = validate_qr_format($code);
        if (!$format_check['valid']) {
            return [
                'success' => false,
                'error' => $format_check['message'],
                'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
            ];
        }
        
        $type = $format_check['type'];
        $code = strtoupper(trim($code));
        
        // Step 2: Database validation (optimized query with index)
        if ($type === 'order') {
            $stmt = $mysqli->prepare(
                "SELECT id, code, total_amount, payment_status, status, created_at, qr_expires_at 
                 FROM orders 
                 WHERE code = ? 
                 LIMIT 1"
            );
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            $stmt->close();
            
            if (!$record) {
                return [
                    'success' => false,
                    'error' => 'Order not found',
                    'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ];
            }
            
            // Check QR expiry (3 hours from creation)
            $expiry = $record['qr_expires_at'] ? strtotime($record['qr_expires_at']) : strtotime($record['created_at'] . ' +3 hours');
            if (time() > $expiry) {
                return [
                    'success' => false,
                    'error' => 'Order QR code expired (valid for 3 hours)',
                    'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ];
            }
            
            // Check status
            if ($record['payment_status'] === 'paid') {
                return [
                    'success' => false,
                    'error' => 'Order already paid',
                    'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ];
            }
            
            if ($record['status'] === 'cancelled') {
                return [
                    'success' => false,
                    'error' => 'Order has been cancelled',
                    'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ];
            }
            
            return [
                'success' => true,
                'type' => 'order',
                'id' => $record['id'],
                'code' => $code,
                'amount' => $record['total_amount'],
                'redirect_url' => 'payment.php?code=' . urlencode($code),
                'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
            ];
        }
        
        if ($type === 'reward') {
            $stmt = $mysqli->prepare(
                "SELECT id, voucher_code, points_required, status, created_at, expires_at 
                 FROM vouchers 
                 WHERE voucher_code = ? 
                 LIMIT 1"
            );
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $result = $stmt->get_result();
            $record = $result->fetch_assoc();
            $stmt->close();
            
            if (!$record) {
                return [
                    'success' => false,
                    'error' => 'Reward voucher not found',
                    'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ];
            }
            
            // Check status
            if ($record['status'] === 'redeemed') {
                return [
                    'success' => false,
                    'error' => 'Voucher already redeemed',
                    'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ];
            }
            
            // Check expiry (30 minutes from creation)
            $expiry = !empty($record['expires_at']) ? strtotime($record['expires_at']) : strtotime($record['created_at'] . ' +30 minutes');
            if ($expiry !== false && time() > $expiry) {
                return [
                    'success' => false,
                    'error' => 'Redemption QR code expired (valid for 30 minutes)',
                    'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
                ];
            }
            
            return [
                'success' => true,
                'type' => 'reward',
                'id' => $record['id'],
                'code' => $code,
                'points' => $record['points_required'],
                'redirect_url' => 'payment.php?code=' . urlencode($code),
                'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Unknown QR type',
            'validation_time' => round((microtime(true) - $start_time) * 1000, 2) . 'ms'
        ];
    }
}
?>
