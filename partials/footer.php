<?php
$settings = $mysqli->query("SELECT `key`,`value` FROM settings")->fetch_all(MYSQLI_ASSOC);
$S = [];
foreach ($settings as $r) { 
  $S[$r['key']] = $r['value']; 
}
?>
</main>

<footer class="text-white mt-auto site-footer">
<style>
/* Footer Mobile Styles */
.site-footer {
    padding: 2rem 0 1.5rem 0;
    background-color: #1a1a1a;
    border-top: 1px solid #333;
    color: #e2e8f0;
}

.site-footer .col-md-4 {
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.site-footer h5 {
    font-size: 1rem;
    margin-bottom: 1rem;
    color: #032b06ff;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
    font-family: 'Poppins', sans-serif;
}

.site-footer p, 
.site-footer .list-unstyled li {
    font-size: 0.85rem;
    line-height: 1.7;
    color: #e2e8f0;
    margin-bottom: 0.5rem;
    font-weight: 400;
}

.site-footer a {
    color: #e2e8f0;
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 500;
    position: relative;
}

.site-footer a:hover {
    color: #2A5618;
    text-decoration: none;
    transform: translateX(2px);
}

.site-footer .list-unstyled li {
    margin-bottom: 0.4rem;
}

.site-footer .d-flex.gap-3 {
    margin: 1.5rem 0;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.site-footer .d-flex.gap-3 {
    margin: 1.5rem 0;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.site-footer .d-flex.gap-3 a {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.2);
    font-size: 1.25rem;
    width: 40px;
    height: 40px;
    min-width: 40px;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
    opacity: 1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Small devices (landscape phones, 576px and up) */
@media (min-width: 576px) {
    .site-footer .d-flex.gap-3 a {
        font-size: 1.375rem;
        width: 40px;
        height: 40px;
        min-width: 40px;
        min-height: 40px;
    }
}

/* Medium devices (tablets, 768px and up) */
@media (min-width: 768px) {
    .site-footer .d-flex.gap-3 {
        justify-content: flex-start;
    }
    
    .site-footer .d-flex.gap-3 a {
        font-size: 1.5rem;
        width: 44px;
        height: 44px;
        min-width: 44px;
        min-height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
        opacity: 1;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .site-footer .d-flex.gap-3 a:hover {
        background: rgba(255, 255, 255, 1);
        color: #032b06ff !important;
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
    }
}

/* Hover effects for devices with hover capability */
@media (hover: hover) {
    .site-footer .d-flex.gap-3 a:hover {
        background: rgba(255, 255, 255, 1);
        color: #032b06ff !important;
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.25);
    }
}

/* Copyright section - Mobile */
.site-footer .text-center {
    margin-top: 2rem;
    padding-top: 1.5rem;
    font-size: 0.9rem;
    color: #ffffffff;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-weight: 400;
    opacity: 0.8;
}

.site-footer .col-md-4:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

/* Desktop and Laptop styles */
@media (min-width: 768px) {
    .site-footer {
        padding: 3rem 0 2rem 0;
        background-color: #1a1a1a;
        border-top: 1px solid #333;
        color: #e2e8f0;
    }
    
    .site-footer .col-md-4 {
        border-bottom: none;
        margin-bottom: 0;
        padding: 0 1.5rem;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .site-footer .col-md-4:last-child {
        border-right: none;
    }
    
    .site-footer h5 {
        color: #032b06ff;
        font-size: 1.1rem;
        margin-bottom: 1.25rem;
    }
    
    .site-footer p,
    .site-footer .list-unstyled li {
        color: #e2e8f0;
    }
    
    .site-footer a {
        color: #e2e8f0;
        opacity: 0.9;
    }
    
    .site-footer a:hover {
        color: #032b06ff;
        text-decoration: none;
        opacity: 1;
    }
    
    .site-footer .text-center {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        color: #e2e8f0;
        padding-top: 1.5rem;
        margin-top: 1.5rem;
    }
    
    .site-footer .d-flex.gap-3 a {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.15);
        font-size: 1.5rem;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
        opacity: 1;
    }
    
    .site-footer .d-flex.gap-3 a:hover {
        background: rgba(255, 255, 255, 1);
        transform: translateY(-3px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }
}
</style>
  <div class="container">
    <div class="row g-4">
      <!-- Company Info -->
      <div class="col-md-4">
        <h5 class="fw-bold mb-3"><?= e($S['site_name'] ?? (defined('APP_NAME') ? APP_NAME : 'Paghilom Cafe')) ?></h5>
        <p class="mb-2" style="opacity: 0.9;"><?= e($S['address']) ?></p>
        <?php 
          $open = $S['open_time'] ?? '';
          $close = $S['close_time'] ?? '';
          $days = $S['open_days'] ?? 'Mon–Sun';
          if ($open && $close):
            $open_disp = date('g:i A', strtotime($open));
            $close_disp = date('g:i A', strtotime($close));
        ?>
          <p class="mb-2" style="opacity: 0.9;"><i class="bi bi-clock"></i> <?= e($days) ?> <?= e($open_disp) ?> – <?= e($close_disp) ?></p>
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
          <li class="mb-2"><a href="<?= APP_URL ?>menu.php" class="text-white text-decoration-none" style="opacity: 0.9;">Menu</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>map.php" class="text-white text-decoration-none" style="opacity: 0.9;">Location</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>pages.php?slug=about" class="text-white text-decoration-none" style="opacity: 0.9;">About Us</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>pages.php?slug=contact" class="text-white text-decoration-none" style="opacity: 0.9;">Contact</a></li>
        </ul>
      </div>

      <!-- Social Media -->
      <div class="col-md-4">
        <h5 class="fw-bold mb-3">Connect With Us</h5>
        <div class="d-flex gap-3 mb-3">
          <?php 
          $facebook_url = $S['facebook_url'] ?? '';
          $instagram_url = !empty($S['instagram_url']) ? $S['instagram_url'] : 'https://www.instagram.com/paghilom_cafe';
          ?>
          <?php if (!empty($facebook_url)): ?>
            <a class="d-flex align-items-center justify-content-center" href="<?= e($facebook_url) ?>" target="_blank" rel="noopener">
              <i class="bi bi-facebook"></i>
            </a>
          <?php endif; ?>
          <a class="d-flex align-items-center justify-content-center" href="<?= htmlspecialchars($instagram_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
            <i class="bi bi-instagram"></i>
          </a>
        </div>
    </div>

    <!-- Copyright -->
    <div class="row mt-4 pt-3" style="border-top: 1px solid rgba(255, 255, 255, 1);">
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
<!-- Session-based navigation control -->
<script src="<?= APP_URL ?>assets/js/session-check.js"></script>
<?php if (function_exists('is_logged_in') && is_logged_in()): ?>
<script src="<?= APP_URL ?>assets/js/no-back.js"></script>
<?php endif; ?>
</body>
</html>
