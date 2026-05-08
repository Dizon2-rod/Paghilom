<?php
require __DIR__.'/../config.php';
require __DIR__.'/../lib/auth.php';

// Check if user is logged in
if (!isset($_COOKIE['client_phone'])) {
    header('Location: ../login.php');
    exit;
}

$phone = $_COOKIE['client_phone'];
$client = $mysqli->query("SELECT * FROM clients WHERE phone='$phone' LIMIT 1")->fetch_assoc();

if (!$client) {
    header('Location: ../login.php');
    exit;
}

// Get products by category
$categories = $mysqli->query("SELECT * FROM categories ORDER BY id");
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE p.is_active = 1 
                   ORDER BY c.id, p.name";
$products = $mysqli->query($products_query);

$page_title = "Order Now";
include __DIR__.'/includes/header.php';
?>


<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
<h2 class="fw-bold mb-1 text-brand">Order Now</h2>
                <p class="text-muted mb-0">Browse our menu and add items to your cart</p>
            </div>
            <div class="points-banner d-inline-block">
                <div class="d-flex align-items-center gap-3">
                    <div>
<i class="fas fa-star fs-4"></i>
                    </div>
                    <div>
<div class="fw-bold fs-4">
                        <small>Points Available</small>
                    </div>
<div class="ms-3 ps-3 divider-left">
                        <i class="fas fa-gift me-2"></i>
                        <small>Earn 1 pt per ₱5</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cart Summary -->
<div class="row mb-4">
    <div class="col-12">
<div class="card cart-sticky shadow-soft border-brand border-2" id="cart-summary" style="display: none;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
<div class="bg-white rounded-circle p-3 d-flex align-items-center justify-content-center square-50">
<i class="fas fa-shopping-cart text-brand fs-5"></i>
                        </div>
                        <div>
<h5 class="mb-0 fw-bold text-brand"><span id="cart-count">0</span> Items</h5>
                            <small class="text-muted">in your cart</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <small class="text-muted d-block">Total Amount</small>
<h4 class="mb-0 fw-bold text-brand">₱<span id="cart-total">0.00</span></h4>
                        </div>
<button class="btn btn-primary btn-lg px-4" onclick="proceedToCheckout()">
                            <i class="fas fa-check-circle me-2"></i>Checkout
                        </button>
                        <button class="btn btn-outline-danger" onclick="clearCart()" title="Clear Cart">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Grid -->
<div class="row">
    <div class="col-12">
        <?php 
        $current_category = '';
        while ($product = $products->fetch_assoc()): 
            if ($current_category !== $product['category_name']): 
                if ($current_category !== '') echo '</div>';
                $current_category = $product['category_name'];
        ?>
<div class="mb-2"><span class="section-chip"><i class="fas fa-utensils me-2"></i><?= e($current_category) ?></span></div>
        <div class="row g-4 mb-5">
        <?php endif; ?>
        
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="card h-100 product-item border-0 shadow-sm">
                <?php 
                $img_query = $mysqli->query("SELECT filename FROM product_images WHERE product_id={$product['id']} AND is_cover=1 LIMIT 1");
                $img_row = $img_query->fetch_assoc();
                $img_path = $img_row ? '../uploads/products/' . $img_row['filename'] : '../assets/img/placeholder.jpg';
                ?>
<div class="position-relative">
                    <img src="<?= e($img_path) ?>" class="card-img-top" alt="<?= e($product['name']) ?>">
                    <?php if ($product['is_featured']): ?>
                        <span class="badge-new"><i class="fas fa-star me-1"></i>Featured</span>
                    <?php endif; ?>
                </div>
                <div class="card-body d-flex flex-column">
<h6 class="card-title fw-bold mb-2 text-brand"><?= e($product['name']) ?></h6>
                    <p class="card-text small text-muted flex-grow-1"><?= e(substr($product['description'], 0, 60)) ?><?= strlen($product['description']) > 60 ? '...' : '' ?></p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
<h5 class="mb-0 fw-bold text-brand">₱<?= number_format($product['price'], 2) ?></h5>
                        </div>
<button class="btn btn-primary" onclick="addToCart(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>', <?= $product['price'] ?>)">
                            <i class="fas fa-cart-plus me-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Hidden form for checkout -->
<form id="checkout-form" action="../checkout.php" method="POST" style="display: none;">
    <input type="hidden" name="cart_data" id="cart_data">
</form>

<script>
let cart = JSON.parse(localStorage.getItem('cart') || '[]');

function updateCartDisplay() {
    const count = cart.reduce((sum, item) => sum + item.quantity, 0);
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    
    document.getElementById('cart-count').textContent = count;
    document.getElementById('cart-total').textContent = total.toFixed(2);
    
    if (count > 0) {
        document.getElementById('cart-summary').style.display = 'block';
    } else {
        document.getElementById('cart-summary').style.display = 'none';
    }
}

function addToCart(id, name, price) {
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    
    // Show success message
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Added';
    btn.classList.add('btn-success');
    btn.classList.remove('btn-primary');
    
    setTimeout(() => {
        btn.innerHTML = originalHTML;
        btn.classList.add('btn-primary');
        btn.classList.remove('btn-success');
    }, 1000);
}

function clearCart() {
    if (confirm('Are you sure you want to clear your cart?')) {
        cart = [];
        localStorage.setItem('cart', JSON.stringify(cart));
        updateCartDisplay();
    }
}

function proceedToCheckout() {
    if (cart.length === 0) {
        alert('Your cart is empty!');
        return;
    }
    
    document.getElementById('cart_data').value = JSON.stringify(cart);
    document.getElementById('checkout-form').submit();
}

// Initialize display
updateCartDisplay();
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
