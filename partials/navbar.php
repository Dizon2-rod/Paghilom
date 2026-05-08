<?php
// Make sure you have a valid $mysqli connection before including this file
// Example: $mysqli = new mysqli("localhost", "root", "", "your_database");

// Define APP_URL safely (if not already defined)
if (!defined('APP_URL')) {
    // Automatically detect your base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    define('APP_URL', $protocol . $domain . $path . '/');
}

// Fetch settings from DB
$settings_query = $mysqli->query("SELECT `key`, `value` FROM settings");
$S = [];

if ($settings_query) {
    foreach ($settings_query->fetch_all(MYSQLI_ASSOC) as $row) {
        $S[$row['key']] = $row['value'];
    }
}

// Set defaults
$site_name = $S['site_name'] ?? 'Paghilom Cafe';

// ✅ Always use the Paghilom Café logo in assets/img/logo.png when available
if (file_exists(__DIR__ . '/../assets/img/logo.png')) {
    $logo = APP_URL . 'assets/img/logo.png';
} elseif (file_exists(__DIR__ . '/../uploads/paghilom_logo.png')) {
    $logo = APP_URL . 'uploads/paghilom_logo.png';
} elseif (!empty($S['logo']) && file_exists(__DIR__ . '/../' . $S['logo'])) {
    $logo = APP_URL . ltrim($S['logo'], '/');
} else {
    $logo = APP_URL . 'assets/img/logo.png';
}

// ✅ Use a safe escape helper if e() doesn’t exist
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
?>

<style>
/***** Mobile width cap and navbar wrapping *****/
@media (max-width: 600px){
  html, body { max-width: 600px; width: 100%; margin: 0 auto; overflow-x: hidden; }
  .navbar, .navbar > .container { max-width: 600px; width: 100%; margin: 0 auto; padding-left: 5px; padding-right: 5px; }
  .navbar .navbar-brand img { width: 40px; height: 40px; margin-top: -10px;  margin-right: 10px;}
  .navbar .navbar-brand span { font-size: 0.85rem; margin-left: -20px; margin-top: -5px; }
  .navbar .navbar-collapse { display: flex !important; justify-content: flex-end; }
  .navbar .navbar-nav { flex-wrap: nowrap; gap: 0px; margin: 0 0.6rem; }
  .navbar .nav-link { white-space: nowrap; padding: 0.25rem 0.3rem; font-size: 0.8rem; margin: 0 -0.2rem;}
  .navbar .btn { padding: .35rem .65rem !important; font-size: .85rem !important; }
  
  /* Profile picture styling for mobile */
  .navbar .dropdown-toggle img.rounded-circle {
    width: 32px !important;
    height: 32px !important;
    object-fit: cover;
    border-radius: 50% !important;
    border: px solid var(--primary);
    margin-right: 8px;
  }
  
  .navbar .dropdown-toggle .fa-user-circle {
    font-size: 1.75rem !important;
    color: var(--primary);
  }
  
  .navbar .dropdown-toggle .me-2 {
    margin-right: 0.5rem !important;
  }
}
</style>

<!-- ✅ Navbar -->
<nav class="navbar navbar-expand bg-cream shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="<?= APP_URL ?>">
      <img src="<?= e($logo) ?>" alt="<?= e($site_name) ?> Logo"
           class="rounded-circle me-2"
           style="height: 100px; width: 95px; object-fit: cover; margin-top: -40px; margin-bottom: -30px; margin-left: -8px;"
           onerror="this.style.display='none';">
      <span class="fw-bold" style="color: var(--primary);"><?= e($site_name) ?></span>
    </a>

    <button class="navbar-toggler d-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarsExample"
            aria-controls="navbarsExample" aria-expanded="true" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="navbar-collapse show" id="navbarsExample">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>menu.php">Menu</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>stores.php">Branch</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>gallery.php">Gallery</a></li>

        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
              <?php 
              $profile_photo = $_SESSION['user']['profile_photo'] ?? '';
              if(!empty($profile_photo) && file_exists(__DIR__ . '/../assets/clients/' . $profile_photo)): 
              ?>
                <img src="<?= APP_URL ?>assets/clients/<?= e($profile_photo) ?>" 
                     alt="Profile" 
                     class="rounded-circle me-2" 
                     style="width: 32px; height: 32px; object-fit: cover; border: 2px solid var(--primary);">
              <?php else: ?>
                <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
              <?php endif; ?>
              <?= e($_SESSION['user']['name'] ?? 'Account') ?>
            </a>
            <ul class="dropdown-menu">
              <?php $navRole = $_SESSION['user']['role'] ?? 'guest'; ?>
              <?php if ($navRole === 'customer'): ?>
                <li><a class="dropdown-item" href="<?= APP_URL ?>my_orders.php"><i class="fas fa-list me-2"></i>My Orders</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>my_points.php"><i class="fas fa-star me-2"></i>My Points</a></li>
                <li><a class="dropdown-item" href="<?= APP_URL ?>my_cart.php"><i class="fas fa-shopping-cart me-2"></i>My Cart</a></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="<?= APP_URL ?>profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= APP_URL ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>register.php">Register</a></li>
        <?php endif; ?>

<?php // Owner role removed – no dedicated Owner button in navbar anymore ?>
      </ul>
    </div>
  </div>
</nav>
