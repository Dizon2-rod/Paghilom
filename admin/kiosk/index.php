<?php
require_once __DIR__ . '/../../config.php';
$site_name = function_exists('get_setting') ? get_setting('site_name', 'Paghilom Café') : 'Paghilom Café';
$logo = file_exists(__DIR__ . '/../../assets/img/logo.png') ? (APP_URL . 'assets/img/logo.png') : (APP_URL . 'uploads/paghilom_logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kiosk | <?= htmlspecialchars($site_name) ?></title>
  <link rel="icon" type="image/png" href="<?= $logo ?>">
  <link href="<?= APP_URL ?>assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #2A5618;
      --primary-dark: #1e3d10;
      --bg: #F6FFF6;
    }
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0;
      padding: 20px;
    }
    .kiosk-container {
      max-width: 800px;
      width: 100%;
      background: white;
      border-radius: 24px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
    }
    .kiosk-header {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      padding: 3rem 2rem;
      text-align: center;
    }
    .logo-section {
      margin-bottom: 1.5rem;
    }
    .logo-section img {
      width: 120px;
      height: 120px;
      object-fit: contain;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }
    .kiosk-header h1 {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      letter-spacing: -0.02em;
    }
    .kiosk-header p {
      font-size: 1.1rem;
      opacity: 0.9;
      margin: 0;
    }
    .kiosk-body {
      padding: 3rem 2rem;
      text-align: center;
    }
    .welcome-text {
      font-size: 1.5rem;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 1rem;
    }
    .instruction-text {
      color: #64748b;
      font-size: 1rem;
      margin-bottom: 2.5rem;
    }
    .scan-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
      color: white;
      font-size: 1.25rem;
      font-weight: 600;
      padding: 1.5rem 3rem;
      border-radius: 16px;
      text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 8px 24px rgba(42, 86, 24, 0.3);
    }
    .scan-button:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(42, 86, 24, 0.4);
      color: white;
    }
    .scan-button i {
      font-size: 1.75rem;
    }
    .features {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-top: 3rem;
      padding: 2rem;
      background: #f8f9fa;
      border-radius: 16px;
    }
    .feature {
      text-align: center;
    }
    .feature-icon {
      width: 56px;
      height: 56px;
      background: white;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      color: var(--primary);
      font-size: 1.5rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    .feature-title {
      font-weight: 600;
      color: #0f172a;
      font-size: 0.95rem;
      margin-bottom: 0.25rem;
    }
    .feature-desc {
      color: #64748b;
      font-size: 0.85rem;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    .scan-button {
      animation: pulse 2s ease-in-out infinite;
    }
  </style>
</head>
<body>
  <div class="kiosk-container">
    <div class="kiosk-header">
      <div class="logo-section">
        <img src="<?= $logo ?>" alt="<?= htmlspecialchars($site_name) ?> Logo">
      </div>
      <h1><?= htmlspecialchars($site_name) ?></h1>
      <p>Self-Service Kiosk</p>
    </div>
    
    <div class="kiosk-body">
      <div class="welcome-text">Welcome! 👋</div>
      <div class="instruction-text">
        Tap the button below to scan your Order or Reward QR code<br>
        and complete your transaction
      </div>
      
      <a href="scan_qr.php" class="scan-button">
        <i class="bi bi-qr-code-scan"></i>
        Scan QR Code
      </a>
      
      <div class="features">
        <div class="feature">
          <div class="feature-icon">
            <i class="bi bi-lightning-charge-fill"></i>
          </div>
          <div class="feature-title">Fast & Easy</div>
          <div class="feature-desc">Quick payment process</div>
        </div>
        <div class="feature">
          <div class="feature-icon">
            <i class="bi bi-shield-check"></i>
          </div>
          <div class="feature-title">Secure</div>
          <div class="feature-desc">Protected transactions</div>
        </div>
        <div class="feature">
          <div class="feature-icon">
            <i class="bi bi-phone"></i>
          </div>
          <div class="feature-title">Contactless</div>
          <div class="feature-desc">Safe & convenient</div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="<?= APP_URL ?>assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
