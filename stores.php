<?php 
require __DIR__.'/config.php'; 
$PAGE_BG = 'stores-page'; // This will add 'stores-page' class to body tag
include __DIR__.'/partials/header.php';

// Get admin-set address and location from settings
$address = get_setting('address');
$phone = get_setting('contact_phone');
$hours = get_setting('opening_hours');
$lat = trim(get_setting('lat', ''));
$lng = trim(get_setting('lng', ''));
$site_name = get_setting('site_name', 'Paghilom Cafe');

// Build map embed URL
$mapSrc = '';
if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
    $mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($lat . ',' . $lng) . '&hl=en&z=16&output=embed&cb=' . time();
} else {
    $mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($address) . '&hl=en&z=16&output=embed&cb=' . time();
}

// Build directions URL
$dest = ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng))
    ? $lat . ',' . $lng
    : $address;
$dirUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($dest);
?>

<section class="container py-5">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h1 class="h3 mb-1">Our Branch</h1>
            <p class="text-muted mb-0">Find the <?= e($site_name) ?> location</p>
        </div>
        <a class="btn btn-primary" href="<?= e($dirUrl) ?>" target="_blank" rel="noopener">
            <i class="fas fa-location-arrow me-2"></i>Get Directions
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <!-- Google Maps Embed -->
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
