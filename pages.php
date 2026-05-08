<?php
require_once __DIR__ . '/config.php';

// Sanitize slug (letters, numbers, hyphens)
$slug = isset($_GET['slug']) ? strtolower(preg_replace('/[^a-z0-9\-]/i', '', $_GET['slug'])) : '';
if ($slug === '') { $slug = 'about'; }

// Helper safe escape
if (!function_exists('e')) {
  function e($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
}

// Try to fetch page from DB if it exists and is published
$page = null;
if ($stmt = $mysqli->prepare('SELECT title, body FROM pages WHERE slug = ? AND is_published = 1 LIMIT 1')) {
  $stmt->bind_param('s', $slug);
  $stmt->execute();
  $res = $stmt->get_result();
  $page = $res ? $res->fetch_assoc() : null;
  $stmt->close();
}

// Settings for dynamic bits in fallback content
$settings = [];
if ($q = $mysqli->query('SELECT `key`,`value` FROM settings')) {
  foreach ($q->fetch_all(MYSQLI_ASSOC) as $r) { $settings[$r['key']] = $r['value']; }
}
$site_name = $settings['site_name'] ?? (defined('APP_NAME') ? APP_NAME : 'Paghilom Cafe');
$address   = $settings['address'] ?? '';
$hours     = $settings['opening_hours'] ?? '';
$phone     = $settings['contact_phone'] ?? '';
$facebook  = $settings['facebook_url'] ?? '';
$instagram = !empty($settings['instagram_url']) ? $settings['instagram_url'] : 'https://www.instagram.com/paghilom_cafe';

$title = $page['title'] ?? ucfirst($slug);

// Default ABOUT content (if not found in DB)
$default_about = function() use ($site_name, $address, $hours, $phone, $facebook, $instagram) {
  ob_start(); ?>
  <section class="py-5 bg-light">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <h1 class="display-5 fw-bold mb-4">About <?= e($site_name) ?></h1>
          <p class="lead mb-4" style="opacity:.9;">
            At <?= e($site_name) ?>, we believe in "paghilom" — moments of slow, mindful rest. 
            We serve thoughtfully crafted coffee, refreshing beverages, and hearty comfort bites 
            that bring people together in Sta. Cruz, Laguna.
          </p>
          <ul class="list-unstyled mb-4 contact-info">
            <?php if ($address): ?><li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= e($address) ?></li><?php endif; ?>
            <?php if ($hours):   ?><li class="mb-2"><i class="bi bi-clock me-2"></i><?= e($hours) ?></li><?php endif; ?>
            <?php if ($phone):   ?><li class="mb-2"><i class="bi bi-telephone me-2"></i><a class="text-decoration-none" href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
          </ul>
          <div class="d-flex gap-3 mb-4">
            <?php if ($facebook): ?>
              <a class="social-icon" target="_blank" rel="noopener" href="<?= e($facebook) ?>">
                <i class="bi bi-facebook"></i>
              </a>
            <?php endif; ?>
            <a class="social-icon" target="_blank" rel="noopener" href="<?= e($instagram) ?>">
              <i class="bi bi-instagram"></i>
            </a>
          </div>
          <style>
            /* Base styles */
            .social-icon {
              color: #ffffff !important;
              background: rgba(0, 0, 0, 0.1);
              font-size: 1.25rem;
              width: 40px;
              height: 40px;
              min-width: 40px;
              min-height: 40px;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              border-radius: 50%;
              transition: all 0.3s ease;
              text-decoration: none;
              margin-right: 0.5rem;
            }

            .social-icon:hover {
              background: rgba(0, 0, 0, 0.2);
              transform: translateY(-2px);
              box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            /* Mobile optimizations - Compact View */
            @media (max-width: 767.98px) {
              /* Typography */
              .display-5 {
                font-size: 1.8rem !important;
                line-height: 1.2;
                text-align: left !important;
                margin-bottom: 1rem !important;
              }
              
              .lead {
                font-size: 1rem;
                text-align: left !important;
                line-height: 1.5;
                margin-bottom: 1rem !important;
              }
              
              /* Layout */
              .py-5 {
                padding-top: 1.5rem !important;
                padding-bottom: 1.5rem !important;
              }
              
              /* Spacing */
              .mb-4, .mb-3, .mb-2 {
                margin-bottom: 0.75rem !important;
              }
              
              /* Images */
              .ratio-16x9 {
                --bs-aspect-ratio: 60%;
                margin: 1rem 0;
                border-radius: 0.375rem;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
              }
              
              /* Lists */
              .list-unstyled li {
                margin-bottom: 0.5rem;
                font-size: 0.95rem;
                display: flex;
                align-items: center;
              }
              
              /* Social icons */
              .d-flex.gap-3 {
                justify-content: flex-start;
                margin: 1.25rem 0;
                gap: 0.75rem !important;
              }
              
              .social-icon {
                width: 38px;
                height: 38px;
                font-size: 1.1rem;
              }
              
              /* Contact info */
              .contact-info i {
                width: 20px;
                font-size: 1em;
                color: var(--primary);
              }
              
              /* Section spacing */
              section > .container {
                padding-left: 1.25rem;
                padding-right: 1.25rem;
              }
              
              /* Text alignment */
              .text-center {
                text-align: left !important;
              }
              
              .text-md-start {
                text-align: left !important;
              }
            }
            
            /* Desktop styles */
            @media (min-width: 768px) {
              .social-icon {
                width: 44px;
                height: 44px;
                min-width: 44px;
                min-height: 44px;
                font-size: 1.25rem;
              }
            }
          </style>
        </div>
        <div class="col-lg-6">
          <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm">
            <img src="<?= APP_URL ?>assets/img/logo.png" alt="<?= e($site_name) ?>" style="object-fit:cover;">
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="p-4 h-100 border rounded-3 bg-white">
            <h3 class="h5 fw-bold mb-2">Our Story</h3>
            <p class="mb-0" style="opacity:.9;">Born from a love for coffee and community, <?= e($site_name) ?> is a neighborhood space made for unhurried conversations and simple joys.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-4 h-100 border rounded-3 bg-white">
            <h3 class="h5 fw-bold mb-2">What We Serve</h3>
            <p class="mb-0" style="opacity:.9;">Specialty coffee, fruit-based refreshers, and comfort food — prepared with care, using quality ingredients.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="p-4 h-100 border rounded-3 bg-white">
            <h3 class="h5 fw-bold mb-2">What We Value</h3>
            <p class="mb-0" style="opacity:.9;">Warm hospitality, consistency, and a calm space where you can pause, recharge, and feel at home.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="py-5 bg-light">
    <div class="container">
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <h2 class="h3 fw-bold mb-3">Visit Us</h2>
          <ul class="list-unstyled">
            <?php if ($address): ?><li class="mb-2"><i class="bi bi-geo-alt me-2"></i><?= e($address) ?></li><?php endif; ?>
            <?php if ($hours):   ?><li class="mb-2"><i class="bi bi-clock me-2"></i><?= e($hours) ?></li><?php endif; ?>
            <?php if ($phone):   ?><li class="mb-2"><i class="bi bi-telephone me-2"></i><a class="text-decoration-none" href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
          </ul>
          <a class="btn btn-dark" href="<?= APP_URL ?>map.php">View on Map</a>
        </div>
        <div class="col-lg-5">
          <div id="map" class="rounded shadow-sm" style="height:300px;"></div>
        </div>
      </div>
    </div>
  </section>

  <script>
    document.addEventListener('DOMContentLoaded', function(){
      if (typeof L !== 'undefined') {
        var map = L.map('map').setView([14.278, 121.415], 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom: 19}).addTo(map);
      }
    });
  </script>
  <?php return ob_get_clean();
};

// Render
$PAGE_BG = '';
include __DIR__ . '/partials/header.php';
?>

<section class="py-5">
  <div class="container">
    <nav aria-label="breadcrumb" class="mb-3">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($title) ?></li>
      </ol>
    </nav>
    <h1 class="h2 fw-bold mb-4"><?= e($title) ?></h1>
  </div>
</section>

<div class="container pb-5">
  <div class="row">
    <div class="col-12">
      <?php if ($page): ?>
        <div class="page-body">
          <?= $page['body'] ?>
        </div>
      <?php else: ?>
        <?php if ($slug === 'about'): ?>
          <?= $default_about(); ?>
        <?php else: ?>
          <div class="alert alert-warning">The page you are looking for was not found.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php';
