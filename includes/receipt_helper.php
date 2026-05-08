<?php
/**
 * Receipt Generation Helper
 * Makes it easy to generate receipts with valid QR codes
 */

require_once __DIR__ . '/qr_helper.php';

if (!function_exists('generate_order_receipt')) {
    /**
     * Generate a complete order receipt with QR code
     * 
     * @param mysqli $mysqli Database connection
     * @param int $order_id The order ID
     * @param bool $return_html Whether to return HTML or output directly
     * @return string|void HTML string if $return_html is true
     */
    function generate_order_receipt($mysqli, $order_id, $return_html = false) {
        // Fetch order details
        $stmt = $mysqli->prepare(
            "SELECT o.*, u.name as customer_name 
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.id 
             WHERE o.id = ? 
             LIMIT 1"
        );
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if (!$order) {
            return 'Order not found';
        }
        
        // Generate or retrieve QR code
        $qr_code = $order['order_code'];
        if (empty($qr_code)) {
            // Generate new QR code if not exists
            $qr_code = generate_order_qr_code($order_id);
            $update = $mysqli->prepare("UPDATE orders SET order_code = ? WHERE id = ?");
            $update->bind_param('si', $qr_code, $order_id);
            $update->execute();
            $update->close();
        }
        
        // Fetch order items
        $items = [];
        $stmt = $mysqli->prepare(
            "SELECT oi.*, p.name 
             FROM order_items oi 
             JOIN products p ON oi.product_id = p.id 
             WHERE oi.order_id = ?"
        );
        $stmt->bind_param('i', $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = [
                'name' => $row['name'],
                'quantity' => $row['qty'],
                'price' => $row['qty'] * $row['price']
            ];
        }
        $stmt->close();
        
        // Prepare transaction data
        $transaction_data = [
            'customer_name' => $order['customer_name'] ?? 'Guest',
            'total_amount' => $order['total_amount'],
            'status' => $order['payment_status'] ?? $order['status'],
            'items' => $items
        ];
        
        // Set variables for template
        $type = 'order';
        $transaction_id = $order_id;
        
        // Capture template output
        if ($return_html) {
            ob_start();
            include __DIR__ . '/../templates/receipt_qr.php';
            return ob_get_clean();
        } else {
            include __DIR__ . '/../templates/receipt_qr.php';
        }
    }
}

if (!function_exists('generate_reward_receipt')) {
    /**
     * Generate a complete reward receipt with QR code
     * 
     * @param mysqli $mysqli Database connection
     * @param int $reward_id The reward/voucher ID
     * @param bool $return_html Whether to return HTML or output directly
     * @return string|void HTML string if $return_html is true
     */
    function generate_reward_receipt($mysqli, $reward_id, $return_html = false) {
        // Fetch reward details
        $stmt = $mysqli->prepare(
            "SELECT v.*, u.name as customer_name 
             FROM vouchers v 
             LEFT JOIN users u ON v.user_id = u.id 
             WHERE v.id = ? 
             LIMIT 1"
        );
        $stmt->bind_param('i', $reward_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reward = $result->fetch_assoc();
        $stmt->close();
        
        if (!$reward) {
            return 'Reward not found';
        }
        
        // Generate or retrieve QR code
        $qr_code = $reward['voucher_code'];
        if (empty($qr_code)) {
            // Generate new QR code if not exists
            $qr_code = generate_reward_qr_code($reward_id);
            $update = $mysqli->prepare("UPDATE vouchers SET voucher_code = ? WHERE id = ?");
            $update->bind_param('si', $qr_code, $reward_id);
            $update->execute();
            $update->close();
        }
        
        // Prepare transaction data
        $transaction_data = [
            'customer_name' => $reward['customer_name'] ?? 'Valued Customer',
            'status' => $reward['status'] ?? 'active',
            'points_required' => $reward['points_required'] ?? 0,
            'expires_at' => $reward['expires_at'] ?? null
        ];
        
        // Set variables for template
        $type = 'reward';
        $transaction_id = $reward_id;
        
        // Capture template output
        if ($return_html) {
            ob_start();
            include __DIR__ . '/../templates/receipt_qr.php';
            return ob_get_clean();
        } else {
            include __DIR__ . '/../templates/receipt_qr.php';
        }
    }
}

if (!function_exists('display_receipt_with_qr')) {
    /**
     * Quick helper to display any receipt
     * 
     * @param string $type 'order' or 'reward'
     * @param int $id Transaction ID
     * @param mysqli $mysqli Database connection
     */
    function display_receipt_with_qr($type, $id, $mysqli) {
        if ($type === 'order') {
            generate_order_receipt($mysqli, $id, false);
        } elseif ($type === 'reward') {
            generate_reward_receipt($mysqli, $id, false);
        } else {
            echo 'Invalid receipt type';
        }
    }
}

if (!function_exists('ensure_qr_code_exists')) {
    /**
     * Ensure a transaction has a QR code, generate if missing
     * 
     * @param mysqli $mysqli Database connection
     * @param string $type 'order' or 'reward'
     * @param int $id Transaction ID
     * @return string The QR code
     */
    function ensure_qr_code_exists($mysqli, $type, $id) {
        if ($type === 'order') {
            $stmt = $mysqli->prepare("SELECT order_code FROM orders WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            $code = $row['order_code'] ?? null;
            if (empty($code)) {
                $code = generate_order_qr_code($id);
                $update = $mysqli->prepare("UPDATE orders SET order_code = ? WHERE id = ?");
                $update->bind_param('si', $code, $id);
                $update->execute();
                $update->close();
            }
            return $code;
        }
        
        if ($type === 'reward') {
            $stmt = $mysqli->prepare("SELECT voucher_code FROM vouchers WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            $code = $row['voucher_code'] ?? null;
            if (empty($code)) {
                $code = generate_reward_qr_code($id);
                $update = $mysqli->prepare("UPDATE vouchers SET voucher_code = ? WHERE id = ?");
                $update->bind_param('si', $code, $id);
                $update->execute();
                $update->close();
            }
            return $code;
        }
        
        return null;
    }
}
?>
