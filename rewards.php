<?php
require_once __DIR__.'/config.php';

// Get all active rewards
$rewards_query = $mysqli->query("
    SELECT * FROM rewards 
    WHERE is_active = 1
    ORDER BY sort_order, required_points ASC
");

include __DIR__.'/partials/header.php';
?>

<!-- FontAwesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary: #2A5618;
    --primary-light: #F6FFF6;
}

.rewards-hero {
    background: linear-gradient(135deg, var(--primary), #3a7620);
    color: white;
    padding: 4rem 0;
    margin-bottom: 3rem;
    border-radius: 0 0 50% 50% / 0 0 20px 20px;
}

.reward-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    height: 100%;
}

.reward-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(42, 86, 24, 0.2);
}

.reward-image {
    height: 220px;
    object-fit: cover;
    width: 100%;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.reward-placeholder {
    height: 220px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary-light), #e8f5e8);
    font-size: 4rem;
    color: var(--primary);
}

.points-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--primary);
    color: white;
    padding: 8px 16px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.reward-type-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.cta-button {
    background: var(--primary);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.cta-button:hover {
    background: #1f4314;
    color: white;
    transform: scale(1.05);
}

.info-section {
    background: var(--primary-light);
    border-radius: 20px;
    padding: 3rem;
    margin-top: 4rem;
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: white;
}

@media (max-width: 768px) {
    .rewards-hero {
        padding: 2rem 0;
    }
}
</style>

<!-- Hero Section -->
<section class="rewards-hero text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">🎁 Rewards Catalog</h1>
        <p class="lead mb-4">Earn points with every purchase and redeem amazing rewards!</p>
        <?php if (is_logged_in()): ?>
            <a href="my_points.php" class="btn btn-light btn-lg px-5">
                <i class="fas fa-star me-2"></i>View My Points
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-light btn-lg px-5">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Start Earning
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- Rewards Grid -->
<section class="container pb-5">
    <div class="row mb-4">
        <div class="col text-center">
            <h2 class="fw-bold mb-2" style="color: var(--primary);">Available Rewards</h2>
            <p class="text-muted">Browse our exciting collection of rewards</p>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($rewards_query && $rewards_query->num_rows > 0): ?>
            <?php while($reward = $rewards_query->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card reward-card">
                        <div class="position-relative">
                            <?php if (!empty($reward['thumb']) && file_exists(__DIR__ . '/uploads/rewards/' . $reward['thumb'])): ?>
                                <img src="<?= APP_URL ?>uploads/rewards/<?= e($reward['thumb']) ?>" class="reward-image" alt="<?= e($reward['name']) ?>">
                            <?php else: ?>
                                <div class="reward-placeholder">
                                    <i class="fas fa-gift"></i>
                                </div>
                            <?php endif; ?>
                            <span class="points-badge">
                                <?= number_format($reward['required_points']) ?> pts
                            </span>
                        </div>
                        
                        <div class="card-body p-4">
                            <h5 class="card-title fw-bold mb-3" style="color: var(--primary);"><?= e($reward['name']) ?></h5>
                            
                            <?php if (!empty($reward['description'])): ?>
                                <p class="text-muted mb-3"><?= e($reward['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <?php
                                $type_colors = [
                                    'free_item' => 'success',
                                    'discount' => 'info',
                                    'deal' => 'warning',
                                    'voucher' => 'primary'
                                ];
                                $color = $type_colors[$reward['reward_type']] ?? 'secondary';
                                ?>
                                <span class="reward-type-badge bg-<?= $color ?> text-white">
                                    <?= ucfirst(str_replace('_', ' ', $reward['reward_type'])) ?>
                                </span>
                                
                                <?php if (!empty($reward['value'])): ?>
                                    <?php if (is_numeric($reward['value'])): ?>
                                        <span class="text-muted fw-bold">₱<?= number_format($reward['value'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small"><?= e($reward['value']) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (is_logged_in()): ?>
                                <?php
                                $user_id = (int)$_SESSION['user']['id'];
                                require_once __DIR__.'/includes/points.php';
                                $user_points = get_user_points_balance($user_id);
                                $can_redeem = $user_points >= $reward['required_points'];
                                ?>
                                <?php if ($can_redeem): ?>
                                    <form method="post" action="my_points.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="reward_id" value="<?= $reward['id'] ?>">
                                        <button type="submit" name="redeem_reward" class="btn cta-button w-100">
                                            <i class="fas fa-gift me-2"></i>Redeem Now (<?= number_format($reward['required_points']) ?> pts)
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="my_points.php" class="btn btn-secondary w-100" style="border-radius: 50px;">
                                        <i class="fas fa-info-circle me-2"></i>Need <?= number_format($reward['required_points'] - $user_points) ?> more points
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn cta-button w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Redeem
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-gift fa-4x mb-3 d-block"></i>
                    <h4>No Rewards Available</h4>
                    <p class="mb-0">Check back soon for exciting rewards!</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- How It Works Section -->
<section class="container">
    <div class="info-section">
        <h2 class="text-center fw-bold mb-5" style="color: var(--primary);">How It Works</h2>
        <div class="row g-4">
            <div class="col-md-3 text-center">
                <div class="feature-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h5 class="fw-bold mb-2">1. Shop</h5>
                <p class="text-muted small">Make purchases at Paghilom Cafe</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="feature-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h5 class="fw-bold mb-2">2. Earn Points</h5>
                <p class="text-muted small">Get 1 point for every ₱5 spent</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="feature-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <h5 class="fw-bold mb-2">3. Redeem</h5>
                <p class="text-muted small">Exchange points for rewards</p>
            </div>
            <div class="col-md-3 text-center">
                <div class="feature-icon">
                    <i class="fas fa-coffee"></i>
                </div>
                <h5 class="fw-bold mb-2">4. Enjoy</h5>
                <p class="text-muted small">Claim your rewards in-store</p>
            </div>
        </div>
        
        <?php if (!is_logged_in()): ?>
            <div class="text-center mt-5">
                <a href="register.php" class="btn cta-button btn-lg px-5">
                    <i class="fas fa-user-plus me-2"></i>Sign Up Now
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__.'/partials/footer.php'; ?>
