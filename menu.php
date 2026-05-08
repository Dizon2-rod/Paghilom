<?php
require __DIR__.'/config.php';

// If logged in as admin or staff, don't use public ordering menu
if (function_exists('is_admin') && is_admin()) {
    header('Location: ' . APP_URL . 'admin/dashboard.php');
    exit;
}
if (function_exists('is_staff') && is_staff()) {
    header('Location: ' . APP_URL . 'pos/');
    exit;
}

include __DIR__.'/partials/header.php';
if(!isset($_SESSION['cart'])) $_SESSION['cart']=[];

// Determine if current logged-in user is staff (POS role) or admin
$is_staff_user = (function_exists('is_staff') && is_staff()) || (function_exists('is_admin') && is_admin());

// Fetch categories
$cats = $mysqli->query('SELECT id, name, description FROM categories WHERE is_active=1 ORDER BY sort_order, name');
$categories = $cats ? $cats->fetch_all(MYSQLI_ASSOC) : [];

// Helper to fetch products per category
function fetch_products_for_category($mysqli, $cat_id) {
    $stmt = $mysqli->prepare("SELECT p.id, p.name, p.description, p.price,
        (SELECT filename FROM product_images WHERE product_id=p.id ORDER BY is_cover DESC, id DESC LIMIT 1) img
        FROM products p WHERE p.category_id=? AND p.is_active=1 ORDER BY p.sort_order, p.name");
    $stmt->bind_param('i', $cat_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

$cart_count = array_sum($_SESSION['cart']);
?>

<section class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h4 mb-0" style="letter-spacing: -0.01em;">Menu</h1>
      <p class="text-secondary small fw-semibold mb-0">Browse all products</p>
    </div>
    <?php if (!$is_staff_user): ?>
      <a class="btn btn-primary" href="cart.php">
        <i class="bi bi-cart3"></i> Cart (<span id="cartCount"><?= (int)$cart_count ?></span>)
      </a>
    <?php else: ?>
      <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 small">
        Staff accounts cannot place online orders. Please use the POS.
      </span>
    <?php endif; ?>
  </div>

  <?php if (empty($categories)): ?>
    <div class="alert alert-info">No categories found.</div>
  <?php endif; ?>

  <ul class="nav nav-pills mb-3" role="tablist" style="gap: .5rem;">
    <?php $i=0; foreach ($categories as $c): ?>
      <li class="nav-item" role="presentation">
        <button class="nav-link <?= $i===0?'active':'' ?>" data-bs-toggle="pill" data-bs-target="#cat<?= $c['id'] ?>" type="button">
          <?= htmlspecialchars($c['name']) ?>
        </button>
      </li>
    <?php $i++; endforeach; ?>
  </ul>

  <div class="tab-content">
    <?php $first=true; foreach ($categories as $c): $prods = fetch_products_for_category($mysqli, (int)$c['id']); ?>
      <div class="tab-pane fade show <?= $first ? 'active' : '' ?>" id="cat<?= (int)$c['id'] ?>">
        <?php if (empty($prods)): ?>
          <div class="alert alert-light border">No products in this category.</div>
        <?php else: ?>
          <div class="row g-3">
            <?php foreach ($prods as $p): 
              $img = $p['img'] ? (APP_URL.'uploads/products/'.$p['img']) : (APP_URL.'assets/img/placeholder.php?w=400&h=400&text=No+Image');
            ?>
              <div class="col-6 col-md-4 col-lg-3">
                <div class="product-card h-100">
                  <div class="position-relative" style="overflow:hidden; border-bottom:1px solid var(--gray-200); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-100" style="height: 180px; object-fit: cover; cursor:pointer;" onclick="viewImage('<?= htmlspecialchars($img) ?>','<?= htmlspecialchars($p['name']) ?>')">
                  </div>
                  <div class="p-3 d-flex flex-column">
                    <h6 class="mb-1" style="font-size: .90rem; font-weight: 600; min-height: 2.2em;">
                      <?= htmlspecialchars($p['name']) ?>
                    </h6>
                    <div class="text-muted small flex-grow-1" style="min-height: 2.8em;">
                      <?= htmlspecialchars(mb_strimwidth($p['description'] ?? '', 0, 80, '…')) ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                      <span class="product-price">₱<?= number_format($p['price'],2) ?></span>
                    </div>
                    <?php if (!$is_staff_user): ?>
                      <div class="d-flex gap-2 mt-2">
                        <form method="post" action="cart.php" class="flex-grow-1 m-0 add-form">
                          <input type="hidden" name="action" value="add">
                          <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                          <input type="hidden" name="qty" value="1">
                          <button class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> Add to Cart</button>
                        </form>
                        <a class="btn btn-outline-success flex-grow-1" href="checkout.php?buy=<?= (int)$p['id'] ?>&qty=1">Buy Now</a>
                      </div>
                    <?php else: ?>
                      <div class="mt-2">
                        <small class="text-muted">Ordering is disabled for staff accounts. Use the POS to serve customers.</small>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <?php $first=false; endforeach; ?>
  </div>
</section>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <img src="" id="modalImage" class="img-fluid rounded">
      </div>
      <div class="modal-footer">
        <a href="#" id="viewProductBtn" class="btn btn-primary"><i class="bi bi-eye"></i> View Product</a>
      </div>
    </div>
  </div>
</div>

<script>
function viewImage(src, title){
  document.getElementById('modalImage').src = src;
  document.getElementById('imageModalLabel').textContent = title || 'Preview';
  // Link to product page if title matches a card (best effort via data attributes could be better)
  const btn = document.getElementById('viewProductBtn');
  btn.onclick = function(){
    // Try to find a card with same image to get product id
    const imgs = Array.from(document.querySelectorAll('.product-card img'));
    const found = imgs.find(i => i.src === src);
    if(found){
      const pidInput = found.closest('.product-card').querySelector('input[name="product_id"]');
      if(pidInput){ window.location.href = 'product.php?id=' + pidInput.value; return; }
    }
    const q = encodeURIComponent(title||'');
    window.location.href = 'menu.php?search=' + q;
  };
  new bootstrap.Modal(document.getElementById('imageModal')).show();
}

// AJAX add-to-cart to update the cart count without leaving page
(function(){
  const cartCountEl = document.getElementById('cartCount');
  function inc(n){
    const cur = parseInt(cartCountEl.textContent||'0',10)||0;
    cartCountEl.textContent = cur + n;
  }
  document.querySelectorAll('form.add-form').forEach(f => {
    f.addEventListener('submit', function(e){
      e.preventDefault();
      const fd = new FormData(f);
      const qty = parseInt(fd.get('qty')||'1',10)||1;
      fetch('cart.php', { method:'POST', body:fd, credentials:'same-origin' })
        .then(()=> inc(qty))
        .catch(()=> inc(qty));
    });
  });
})();
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
