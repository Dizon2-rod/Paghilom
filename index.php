
<?php 
require __DIR__.'/config.php'; 

// Prevent caching for logged-in users
if (is_logged_in()) {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

include __DIR__.'/partials/header.php'; 
?>
<section class="hero d-flex align-items-center justify-content-center" style="background: linear-gradient(to bottom, rgba(12, 36, 2, 0.7), rgba(24, 61, 9, 0.5)), url('assets/img/Hero.jpeg'); height: 500px; background-size: cover; background-position: center;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center fade-in-up">
        <h1 class="display-3 fw-bold text-white mb-3" style="letter-spacing: -0.02em;">Paghilom Cafe</h1>
        <p class="lead mb-4 text-white" style="opacity: 0.95;">Hayaang sarili ay MAGHILOM</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
          <a href="menu.php" class="btn btn-lg btn-primary px-4">Explore Menu</a>
          <a href="map.php" class="btn btn-lg btn-outline-light px-4">Find Us</a>
          <?php // Owner role removed – no Owner dashboard button ?>
          <?php if (function_exists('is_admin_role') && is_admin_role()): ?>
            <a href="<?= e(APP_URL) ?>admin/dashboard.php" class="btn btn-lg btn-success px-4">
              Admin Dashboard
            </a>
          <?php endif; ?>
          <?php if (function_exists('is_staff') && is_staff()): ?>
          <a href="pos/" class="btn btn-lg btn-outline-light px-4">
            <i class="fas fa-cash-register me-2"></i>POS System
          </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php $table = isset($_GET['table'])? ('&table='.urlencode($_GET['table'])): ''; ?>
<section id="menu" class="py-5">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-4 fade-in">
      <div>
        <h2 class="h4 mb-0" style="letter-spacing: -0.01em;">Featured Drinks & Bites</h2>
        <p class="text-muted small mb-0">Handpicked selections</p>
      </div>
      <a href="gallery.php" class="btn btn-outline-secondary btn-sm">See Gallery</a>
    </div>
    <div class="row g-4">
      <?php
      $q=$mysqli->query("SELECT p.id,p.name,p.description,p.price,
              (SELECT filename FROM product_images WHERE product_id=p.id AND is_cover=1 ORDER BY id DESC LIMIT 1) img
            FROM products p
            WHERE p.is_featured=1 AND p.is_active=1
            ORDER BY p.sort_order,p.id LIMIT 8");
      while($r=$q->fetch_assoc()):
        $img = $r['img'] ? ('uploads/products/'.$r['img']) : 'assets/img/placeholder.php?w=400&h=400&text=No+Image';
      ?>
      <div class="col-6 col-md-4 col-lg-3 fade-in delay-<?= ($r['id'] % 5) * 100 ?>">
        <div class="product-card position-relative">
          <div class="position-relative" style="overflow: hidden; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
            <span class="product-badge">Featured</span>
            <img src="<?= e($img) ?>" class="w-100" alt="<?= e($r['name']) ?>" style="height: 200px; object-fit: cover;">
          </div>
          <div class="p-3">
            <h6 class="mb-1 fw-semibold" style="font-size: 0.9rem;"><?= e($r['name']) ?></h6>
            <p class="text-muted small mb-2" style="font-size: 0.8rem; line-height: 1.4;"><?= e($r['description']) ?></p>
            <div class="d-flex justify-content-between align-items-center">
              <span class="product-price">₱<?= number_format($r['price'],2) ?></span>
              <a href="product.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-primary">View</a>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<section id="about" class="py-5 bg-white">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-md-6 fade-in">
        <img src="<?= e(APP_URL) ?>assets/img/cafe_interior.jpg" class="w-100 rounded-lg shadow-sm" alt="Cafe interior">
      </div>
      <div class="col-md-6 fade-in delay-200">
        <h2 class="h3 mb-3" style="letter-spacing: -0.01em;">About Paghilom</h2>
        <p class="text-muted" style="line-height: 1.7;">Born in Sta. Cruz, Laguna, Paghilom Cafe is a cozy nook for conversations and quiet moments. We serve specialty coffee, seasonal drinks, and comfort food, using local ingredients whenever possible.</p>
        <a href="map.php" class="btn btn-primary mt-2">Visit Us</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__.'/partials/footer.php'; ?>
