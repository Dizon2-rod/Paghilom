<?php
/**
 * QR Code Expiry Helper
 * Manages expiry times for order and redemption QR codes
 */

if (!function_exists('set_order_qr_expiry')) {
    /**
     * Set QR code expiry for an order (3 hours from creation)
     * @param mysqli $mysqli Database connection
     * @param int $order_id The order ID
     * @return bool Success status
     */
    function set_order_qr_expiry($mysqli, $order_id) {
        $stmt = $mysqli->prepare(
            "UPDATE orders 
             SET qr_expires_at = DATE_ADD(created_at, INTERVAL 3 HOUR) 
             WHERE id = ? AND qr_expires_at IS NULL"
        );
        $stmt->bind_param('i', $order_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

if (!function_exists('set_voucher_expiry')) {
    /**
     * Set expiry for a redemption voucher (30 minutes from creation)
     * @param mysqli $mysqli Database connection
     * @param int $voucher_id The voucher ID
     * @return bool Success status
     */
    function set_voucher_expiry($mysqli, $voucher_id) {
        $stmt = $mysqli->prepare(
            "UPDATE vouchers 
             SET expires_at = DATE_ADD(created_at, INTERVAL 30 MINUTE) 
             WHERE id = ? AND expires_at IS NULL"
        );
        $stmt->bind_param('i', $voucher_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}

if (!function_exists('get_qr_expiry_info')) {
    /**
     * Get QR code expiry information
     * @param string $type 'order' or 'reward'
     * @param string $created_at Creation timestamp
     * @param string|null $expires_at Optional explicit expiry timestamp
     * @return array Expiry information with seconds remaining
     */
    function get_qr_expiry_info($type, $created_at, $expires_at = null) {
        $created = strtotime($created_at);
        
        if ($type === 'order') {
            $validity_hours = 3;
            $expiry = $expires_at ? strtotime($expires_at) : ($created + (3 * 60 * 60));
        } else {
            $validity_hours = 0.5; // 30 minutes
            $expiry = $expires_at ? strtotime($expires_at) : ($created + (30 * 60));
        }
        
        $now = time();
        $remaining = $expiry - $now;
        $is_expired = $remaining <= 0;
        
        return [
            'created_at' => date('Y-m-d H:i:s', $created),
            'expires_at' => date('Y-m-d H:i:s', $expiry),
            'validity_minutes' => $type === 'order' ? 180 : 30,
            'seconds_remaining' => max(0, $remaining),
            'is_expired' => $is_expired,
            'formatted_time' => format_time_remaining($remaining)
        ];
    }
}

if (!function_exists('format_time_remaining')) {
    /**
     * Format time remaining in human-readable format
     * @param int $seconds Seconds remaining
     * @return string Formatted time string
     */
    function format_time_remaining($seconds) {
        if ($seconds <= 0) {
            return 'Expired';
        }
        
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %02dm %02ds', $hours, $minutes, $secs);
        } else {
            return sprintf('%02d:%02d', $minutes, $secs);
        }
    }
}

if (!function_exists('check_and_expire_qr_codes')) {
    /**
     * Mark expired QR codes in database (for cleanup jobs)
     * @param mysqli $mysqli Database connection
     * @return array Count of expired orders and vouchers
     */
    function check_and_expire_qr_codes($mysqli) {
        // Mark expired orders
        $order_stmt = $mysqli->query(
            "UPDATE orders 
             SET status = 'expired' 
             WHERE status IN ('pending', 'processing') 
             AND qr_expires_at IS NOT NULL 
             AND qr_expires_at < NOW()"
        );
        $expired_orders = $mysqli->affected_rows;
        
        // Mark expired vouchers
        $voucher_stmt = $mysqli->query(
            "UPDATE vouchers 
             SET status = 'expired' 
             WHERE status = 'issued' 
             AND expires_at IS NOT NULL 
             AND expires_at < NOW()"
        );
        $expired_vouchers = $mysqli->affected_rows;
        
        return [
            'expired_orders' => $expired_orders,
            'expired_vouchers' => $expired_vouchers,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
?>
