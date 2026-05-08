    </div>
</div>

<?php
// Get settings from database for footer
$settings = $mysqli->query("SELECT `key`,`value` FROM settings")->fetch_all(MYSQLI_ASSOC);
$S = [];
foreach ($settings as $r) { 
  $S[$r['key']] = $r['value']; 
}
?>

<footer class="text-white mt-auto site-footer" style="padding: 3rem 0 1.5rem 0;">
  <div class="container">
    <div class="row g-4">
      <!-- Company Info -->
      <div class="col-md-4">
        <h5 class="fw-bold mb-3"><?= e($S['site_name'] ?? (defined('APP_NAME') ? APP_NAME : 'Paghilom Cafe')) ?></h5>
        <p class="mb-2" style="opacity: 0.9;"><?= e($S['address'] ?? '4091 Sitio 2 Barangay Bagumbayan, Sta. Cruz, Laguna, Philippines') ?></p>
        <?php if (!empty($S['opening_hours'])): ?>
          <p class="mb-2" style="opacity: 0.9;"><i class="bi bi-clock"></i> <?= e($S['opening_hours']) ?></p>
        <?php endif; ?>
        <p class="mb-0" style="opacity: 0.9;">
          <i class="bi bi-telephone"></i> 
          <a class="text-white text-decoration-none" href="tel:<?= e(preg_replace('/\s+/', '', $S['contact_phone'] ?? '09287197722')) ?>">
            <?= e($S['contact_phone'] ?? '0928 719 7722') ?>
          </a>
        </p>
      </div>

      <!-- Quick Links -->
      <div class="col-md-4">
        <h5 class="fw-bold mb-3">Quick Links</h5>
        <ul class="list-unstyled">
          <li class="mb-2"><a href="<?= APP_URL ?>" class="text-white text-decoration-none" style="opacity: 0.9;">Home</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>#menu" class="text-white text-decoration-none" style="opacity: 0.9;">Menu</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>map.php" class="text-white text-decoration-none" style="opacity: 0.9;">Location</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>pages.php?slug=about" class="text-white text-decoration-none" style="opacity: 0.9;">About Us</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>pages.php?slug=contact" class="text-white text-decoration-none" style="opacity: 0.9;">Contact</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>pages.php?slug=faqs" class="text-white text-decoration-none" style="opacity: 0.9;">FAQs</a></li>
        </ul>
      </div>

      <!-- Social Media -->
      <div class="col-md-4">
        <h5 class="fw-bold mb-3">Connect With Us</h5>
        <div class="d-flex gap-3 mb-3">
          <?php if (!empty($S['facebook_url'])): ?>
            <a class="d-flex align-items-center justify-content-center" href="<?= e($S['facebook_url']) ?>" target="_blank" rel="noopener">
              <i class="bi bi-facebook"></i>
            </a>
          <?php endif; ?>
          <?php if (!empty($S['instagram_url'])): ?>
            <a class="d-flex align-items-center justify-content-center" href="<?= e($S['instagram_url']) ?>" target="_blank" rel="noopener">
              <i class="bi bi-instagram"></i>
            </a>
          <?php endif; ?>
        </div>
        <style>
          .site-footer .d-flex.gap-3 {
              margin: 1.5rem 0;
              gap: 1rem;
              justify-content: flex-start;
              flex-wrap: wrap;
          }

          .site-footer .d-flex.gap-3 a {
              color: #ffffff !important;
              background: rgba(255, 255, 255, 0.15);
              font-size: 1.25rem;
              width: 36px;
              height: 36px;
              min-width: 36px;
              min-height: 36px;
              display: flex;
              align-items: center;
              justify-content: center;
              border-radius: 50%;
              transition: all 0.3s ease;
              opacity: 1 !important;
          }

          @media (min-width: 576px) {
              .site-footer .d-flex.gap-3 a {
                  font-size: 1.375rem;
                  width: 40px;
                  height: 40px;
                  min-width: 40px;
                  min-height: 40px;
              }
          }

          @media (min-width: 768px) {
              .site-footer .d-flex.gap-3 a {
                  font-size: 1.5rem;
                  width: 44px;
                  height: 44px;
                  min-width: 44px;
                  min-height: 44px;
              }
          }

          @media (hover: hover) {
              .site-footer .d-flex.gap-3 a:hover {
                  background: rgba(255, 255, 255, 1);
                  color: #032b06ff !important;
                  transform: translateY(-2px);
                  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
              }
          }
        </style>
        <?php if (function_exists('is_admin') && is_admin()): ?>
          <a href="<?= APP_URL ?>admin/" class="btn btn-outline-light btn-sm mt-2">Admin Panel</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Copyright -->
    <div class="row mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.1);">
      <div class="col text-center">
        <small style="opacity: 0.8;">&copy; <span id="year"></span> <?= e($S['site_name'] ?? 'Paghilom Cafe') ?>. All rights reserved.</small>
      </div>
    </div>
  </div>
</footer>

<script src="<?= APP_URL ?>assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= APP_URL ?>assets/js/app.js"></script>
<script>
// Auto dismiss alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Update year in footer
    const yearEl = document.getElementById('year');
    if (yearEl) {
        yearEl.textContent = new Date().getFullYear();
    }
});
</script>
</body>
</html>
