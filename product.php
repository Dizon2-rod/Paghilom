<?php
require __DIR__.'/config.php';
include __DIR__.'/partials/header.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: index.php');
    exit;
}

// Fetch product details
$stmt = $mysqli->prepare("SELECT p.id, p.name, p.description, p.price, p.is_active, c.name as category_name,
    (SELECT filename FROM product_images WHERE product_id=p.id AND is_cover=1 LIMIT 1) as cover_img
    FROM products p
    LEFT JOIN categories c ON c.id=p.category_id
    WHERE p.id=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product || !$product['is_active']) {
    echo '<section class="container py-5"><h1>Product not found</h1></section>';
    include __DIR__.'/partials/footer.php';
    exit;
}

// Fetch all images
$stmt = $mysqli->prepare("SELECT filename FROM product_images WHERE product_id=? ORDER BY is_cover DESC, sort_order, id");
$stmt->bind_param('i', $id);
$stmt->execute();
$images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch available addons
$stmt = $mysqli->prepare("SELECT a.id, a.name, a.price FROM product_addons pa
    JOIN addons a ON a.id=pa.addon_id
    WHERE pa.product_id=? AND a.is_active=1
    ORDER BY a.sort_order, a.name");
$stmt->bind_param('i', $id);
$stmt->execute();
$addons = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch available milks (if milks table exists)
$milks = [];
try {
    $stmt = $mysqli->prepare("SELECT m.id, m.name, m.price FROM product_milks pm
        JOIN milks m ON m.id=pm.milk_id
        WHERE pm.product_id=? AND m.is_active=1
        ORDER BY m.sort_order, m.name");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $milks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    // Milks table doesn't exist, skip
}

$cover = $product['cover_img'] ? 'uploads/products/'.$product['cover_img'] : 'assets/img/placeholder.php?w=600&h=600&text=No+Image';
?>

<section class="container py-5">
    <div class="row g-4">
        <div class="col-md-6">
            <div class="product-gallery">
                <img src="<?= e($cover) ?>" alt="<?= e($product['name']) ?>" class="img-fluid rounded shadow" id="mainImage">
                <?php if (count($images) > 1): ?>
                <div class="row g-2 mt-3">
                    <?php foreach($images as $img): ?>
                        <div class="col-3">
                            <img src="uploads/products/<?= e($img['filename']) ?>" 
                                 alt="<?= e($product['name']) ?>" 
                                 class="img-thumbnail" 
                                 style="cursor: pointer;"
                                 onclick="document.getElementById('mainImage').src=this.src">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-6">
            <h1 class="h3 mb-2"><?= e($product['name']) ?></h1>
            <?php if($product['category_name']): ?>
                <p class="text-muted small"><?= e($product['category_name']) ?></p>
            <?php endif; ?>
            
            <h2 class="h4 text-success mb-3">₱<?= number_format($product['price'], 2) ?></h2>
            
            <?php if($product['description']): ?>
                <p class="mb-4"><?= nl2br(e($product['description'])) ?></p>
            <?php endif; ?>
            
            <form method="post" action="cart.php" class="mb-4">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $id ?>">
                
                <?php if(!empty($addons)): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Add-ons</label>
                    <?php foreach($addons as $addon): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="addons[]" value="<?= $addon['id'] ?>" id="addon<?= $addon['id'] ?>">
                            <label class="form-check-label" for="addon<?= $addon['id'] ?>">
                                <?= e($addon['name']) ?> (+₱<?= number_format($addon['price'], 2) ?>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($milks)): ?>
                <div class="mb-3">
                    <label class="form-label fw-bold">Milk Options</label>
                    <?php foreach($milks as $milk): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="milk" value="<?= $milk['id'] ?>" id="milk<?= $milk['id'] ?>">
                            <label class="form-check-label" for="milk<?= $milk['id'] ?>">
                                <?= e($milk['name']) ?> (+₱<?= number_format($milk['price'], 2) ?>)
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Quantity</label>
                    <input type="number" name="qty" class="form-control" style="max-width: 120px;" min="1" value="1">
                </div>
                
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <a href="checkout.php?buy=<?= (int)$id ?>&qty=1" class="btn btn-outline-success">Buy Now</a>
                    <a href="menu.php" class="btn btn-outline-secondary">Back to Menu</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__.'/partials/footer.php'; ?>
