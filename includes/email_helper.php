<?php
require_once 'config.php';

// Simple mail function using PHP mail() - for production, use PHPMailer
function sendVerificationEmail($to, $name, $token) {
    $subject = "Verify Your Email - " . SITE_NAME;
    $verification_link = SITE_URL . "/verify_email.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2d5016; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .button { display: inline-block; padding: 12px 30px; background: #2d5016; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . SITE_NAME . "</h1>
            </div>
            <div class='content'>
                <h2>Welcome, " . htmlspecialchars($name) . "!</h2>
                <p>Thank you for registering at " . SITE_NAME . ". Please verify your email address to activate your account.</p>
                <p>Click the button below to verify your email:</p>
                <a href='" . $verification_link . "' class='button'>Verify Email</a>
                <p>Or copy and paste this link into your browser:</p>
                <p>" . $verification_link . "</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you didn't create this account, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function sendPasswordResetEmail($to, $name, $token) {
    $subject = "Reset Your Password - " . SITE_NAME;
    $reset_link = SITE_URL . "/reset_password.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2d5016; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            .button { display: inline-block; padding: 12px 30px; background: #2d5016; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . SITE_NAME . "</h1>
            </div>
            <div class='content'>
                <h2>Password Reset Request</h2>
                <p>Hello " . htmlspecialchars($name) . ",</p>
                <p>We received a request to reset your password. Click the button below to reset it:</p>
                <a href='" . $reset_link . "' class='button'>Reset Password</a>
                <p>Or copy and paste this link into your browser:</p>
                <p>" . $reset_link . "</p>
                <p>This link will expire in 1 hour.</p>
                <p>If you didn't request a password reset, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>&copy; 2025 " . SITE_NAME . ". All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmationEmail($to, $name, $order_number, $order_details) {
    $subject = "Order Confirmation - Order #" . $order_number . " - " . SITE_NAME;
    
    $items_html = '';
    foreach ($order_details['items'] as $item) {
        $items_html .= "<tr>
            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($item['name']) . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>" . $item['quantity'] . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: right;'>₱" . number_format($item['price'], 2) . "</td>
        </tr>";
    }
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2d5016; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .total { font-weight: bold; font-size: 18px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Order Confirmation</h1>
            </div>
            <div class='content'>
                <h2>Thank you for your order, " . htmlspecialchars($name) . "!</h2>
                <p>Your order #<strong>" . $order_number . "</strong> has been received and is being processed.</p>
                <h3>Order Details:</h3>
                <table>
                    <thead>
                        <tr style='background: #f0f0f0;'>
                            <th style='padding: 10px; text-align: left;'>Item</th>
                            <th style='padding: 10px; text-align: center;'>Qty</th>
                            <th style='padding: 10px; text-align: right;'>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $items_html . "
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='2' style='padding: 15px; text-align: right;' class='total'>Total:</td>
                            <td style='padding: 15px; text-align: right;' class='total'>₱" . number_format($order_details['total'], 2) . "</td>
                        </tr>
                    </tfoot>
                </table>
                <p>We'll notify you when your order is ready!</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send order status update email
 */
function sendOrderStatusEmail($to, $name, $order_number, $status) {
    $status_messages = [
        'preparing' => 'Your order is now being prepared',
        'ready' => 'Your order is ready for pickup',
        'completed' => 'Your order has been completed',
        'cancelled' => 'Your order has been cancelled'
    ];
    
    $message_text = $status_messages[$status] ?? 'Your order status has been updated';
    $subject = "Order Update - Order #" . $order_number . " - " . SITE_NAME;
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2d5016; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Order Status Update</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($name) . ",</h2>
                <p>" . $message_text . ".</p>
                <p>Order Number: <strong>#" . $order_number . "</strong></p>
                <p>Status: <strong>" . ucfirst($status) . "</strong></p>
                <p>Track your order status at <a href='" . SITE_URL . "'>" . SITE_NAME . "</a></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send low stock alert email
 */
function sendLowStockAlert($to, $products) {
    $subject = "Low Stock Alert - " . SITE_NAME;
    
    $products_html = '';
    foreach ($products as $product) {
        $products_html .= "<tr>
            <td style='padding: 10px; border-bottom: 1px solid #ddd;'>" . htmlspecialchars($product['name']) . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>" . $product['stock_qty'] . "</td>
            <td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>" . $product['threshold'] . "</td>
        </tr>";
    }
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>⚠️ Low Stock Alert</h1>
            </div>
            <div class='content'>
                <h2>Attention: Low Stock Detected</h2>
                <p>The following products are running low on stock:</p>
                <table>
                    <thead>
                        <tr style='background: #f0f0f0;'>
                            <th style='padding: 10px; text-align: left;'>Product</th>
                            <th style='padding: 10px; text-align: center;'>Current Stock</th>
                            <th style='padding: 10px; text-align: center;'>Threshold</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $products_html . "
                    </tbody>
                </table>
                <p>Please restock these items soon.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Send custom email
 */
function sendCustomEmail($to, $subject, $body, $isHTML = true) {
    if ($isHTML) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
    } else {
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">" . "\r\n";
    }
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Get email template
 */
function getEmailTemplate($title, $content) {
    return "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #ffffff; }
            .header { background: #2d5016; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { padding: 30px; border: 1px solid #ddd; border-top: none; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            .button { display: inline-block; padding: 12px 30px; background: #2d5016; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . htmlspecialchars($title) . "</h1>
            </div>
            <div class='content'>
                " . $content . "
            </div>
            <div class='footer'>
                <p>&copy; 2025 " . SITE_NAME . ". All rights reserved.</p>
                <p>" . SITE_URL . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
