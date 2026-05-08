<?php
require __DIR__.'/config.php';
require_once __DIR__.'/includes/helpers.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error_msg = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please provide a valid email address.';
    } else {
        $to = get_setting('contact_email', 'admin@paghilom.cafe');
        $subject = 'New Contact Message - ' . (defined('APP_NAME') ? APP_NAME : 'Website');
        $body = "Name: {$name}\nEmail: {$email}\nIP: " . ($_SERVER['REMOTE_ADDR'] ?? '') . "\nTime: " . date('Y-m-d H:i:s') . "\n\nMessage:\n{$message}\n";
        if (send_email($to, $subject, nl2br(e($body)))) {
            $success_msg = 'Thanks! Your message has been sent to the admin.';
        } else {
            $error_msg = 'Failed to send message. Please try again later.';
        }
    }
}

include __DIR__.'/partials/header.php';

// Fetch store information
$stores = $mysqli->query("SELECT * FROM stores WHERE is_active=1 ORDER BY city, name");
$store_list = $stores->fetch_all(MYSQLI_ASSOC);

$address = get_setting('address');
$phone = get_setting('contact_phone');
$hours = get_setting('opening_hours');
$lat = trim(get_setting('lat', ''));
$lng = trim(get_setting('lng', ''));
$site_name = get_setting('site_name', 'Paghilom Cafe');
$mapSrc = '';
if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
    $mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng) . '&hl=en&z=16&output=embed&cb=' . time();
} else {
    $mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($address) . '&hl=en&z=16&output=embed&cb=' . time();
}
?>

<section class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Visit Us</h1>
            <p class="text-muted mb-0">Find the Paghilom Cafe location set by the admin</p>
        </div>
        <?php
          $dest = ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng))
            ? $lat . ',' . $lng
            : $address;
          $dirUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($dest);
        ?>
        <a class="btn btn-primary" href="<?= e($dirUrl) ?>" target="_blank" rel="noopener">
            <i class="fas fa-location-arrow me-2"></i>Get Directions
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <!-- Google Maps Embed (dynamic via settings) -->
            <iframe 
                src="<?= e($mapSrc) ?>"
                width="100%" 
                height="350" 
                style="border:0;" 
                allowfullscreen 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
    
    <!-- Branch Information -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3"><?= e($site_name) ?> - Main Branch</h5>
                    
                    <?php if (!empty($address)): ?>
                        <div class="mb-3">
                            <i class="bi bi-geo-alt-fill text-primary me-2"></i>
                            <span class="text-muted"><?= e($address) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($hours)): ?>
                        <div class="mb-3">
                            <i class="bi bi-clock-fill text-primary me-2"></i>
                            <span class="text-muted"><?= e($hours) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($phone)): ?>
                        <div class="mb-3">
                            <i class="bi bi-telephone-fill text-primary me-2"></i>
                            <a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>" class="text-decoration-none">
                                <?= e($phone) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__.'/partials/footer.php'; ?>
