<?php
require_once __DIR__ . '/includes/db_bootstrap.php';
require_once __DIR__ . '/../../config.php';
$site_name = function_exists('get_setting') ? get_setting('site_name', 'Paghilom Café') : 'Paghilom Café';
$logo = file_exists(__DIR__ . '/../../assets/img/logo.png') ? (APP_URL . 'assets/img/logo.png') : (APP_URL . 'uploads/paghilom_logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta http-equiv="Permissions-Policy" content="camera=*" />
  <title>Scan QR | <?= htmlspecialchars($site_name) ?></title>
  <link rel="icon" type="image/png" href="<?= $logo ?>">
  <link href="<?= APP_URL ?>assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <style>
    :root { 
      --primary: #2A5618; 
      --primary-dark: #1e3d10;
      --accent: #16a34a; 
      --danger: #b00020; 
    }
    body { 
      margin: 0; 
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); 
      color: #fff; 
    }
    .wrap { 
      min-height: 100vh; 
      display: flex; 
      flex-direction: column; 
    }
    header { 
      padding: 20px 24px;
      background: rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(10px);
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .header-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .header-logo {
      width: 48px;
      height: 48px;
      object-fit: contain;
    }
    header strong { 
      font-size: 1.4rem;
      font-weight: 700;
      letter-spacing: -0.01em;
    }
    main { 
      flex: 1; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      padding: 40px 20px; 
    }
    .scanner-container {
      max-width: 700px;
      width: 100%;
      background: white;
      border-radius: 24px;
      padding: 2.5rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    .scanner-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    .scanner-title {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 0.5rem;
    }
    .scanner-subtitle {
      color: #64748b;
      font-size: 1rem;
    }
    #reader { 
      width: 100%; 
      aspect-ratio: 1; 
      border: 4px solid #e5e7eb;
      border-radius: 20px; 
      position: relative; 
      overflow: hidden; 
      transition: all 0.3s ease; 
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
      background: #f8f9fa;
    }
    #reader.valid { 
      border-color: var(--accent);
      box-shadow: 0 0 40px rgba(22, 163, 74, 0.4);
    }
    #reader.invalid { 
      border-color: var(--danger);
      box-shadow: 0 0 40px rgba(176, 0, 32, 0.4);
    }
    .hint { 
      text-align: center;
      margin: 1.5rem 0 0;
      color: #64748b;
      font-size: 1rem;
      font-weight: 500;
    }
    .hint-icon {
      font-size: 2.5rem;
      display: block;
      margin-bottom: 0.75rem;
    }
    .status { 
      text-align: center;
      min-height: 2.5em;
      margin-top: 1rem;
      font-size: 1rem;
      font-weight: 600;
      padding: 12px;
      border-radius: 12px;
      background: #f1f5f9;
      color: #0f172a;
    }
    .back { 
      color: white;
      text-decoration: none;
      padding: 10px 20px;
      border-radius: 10px;
      background: rgba(255, 255, 255, 0.15);
      transition: all 0.2s;
      font-weight: 500;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .back:hover { 
      background: rgba(255, 255, 255, 0.25);
      color: white;
      transform: translateX(-2px);
    }
    @keyframes pulse { 
      0%, 100% { opacity: 1; } 
      50% { opacity: 0.6; } 
    }
    .hint { 
      animation: pulse 2s ease-in-out infinite; 
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <div class="header-brand">
        <img src="<?= $logo ?>" alt="Logo" class="header-logo">
        <strong><?= htmlspecialchars($site_name) ?></strong>
      </div>
      <a class="back" href="index.php">
        <i class="bi bi-arrow-left"></i>
        Back
      </a>
    </header>
    <main>
      <div class="scanner-container">
        <div class="scanner-header">
          <div class="scanner-title">
            <i class="bi bi-qr-code-scan me-2"></i>Scan QR Code
          </div>
          <div class="scanner-subtitle">
            Position your QR code within the frame
          </div>
        </div>
        
        <div id="reader"></div>
        
        <p class="hint">
          <span class="hint-icon">📷</span>
          Show your Order or Reward QR Code<br>
          <small style="opacity:0.8;font-size:0.9rem;">System will automatically detect and process</small>
        </p>
        <p id="status" class="status">Initializing camera...</p>
      </div>
    </main>
  </div>

  <script src="https://unpkg.com/html5-qrcode"></script>
  <script>
  (function(){
    const beep=()=>{try{const c=new (window.AudioContext||window.webkitAudioContext)();const o=c.createOscillator(),g=c.createGain();o.type='sine';o.frequency.value=880;o.connect(g);g.connect(c.destination);g.gain.setValueAtTime(0.001,c.currentTime);g.gain.exponentialRampToValueAtTime(0.1,c.currentTime+0.01);o.start();setTimeout(()=>{g.gain.exponentialRampToValueAtTime(0.001,c.currentTime+0.05);o.stop(c.currentTime+0.06)},60)}catch(e){}};
    
    // Show loading overlay
    const showLoading = (msg) => {
      let overlay = document.getElementById('scanLoadingOverlay');
      if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'scanLoadingOverlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;color:white;font-size:1.2rem;';
        document.body.appendChild(overlay);
      }
      overlay.innerHTML = '<div style="text-align:center;"><div class="spinner-border text-light mb-3" role="status"></div><div>' + msg + '</div></div>';
      overlay.style.display = 'flex';
    };
    
    // Validate QR and redirect to payment
    const validateAndRedirect = async (qrCode) => {
      showLoading('Validating QR Code...');
      document.getElementById('status').textContent = 'Validating QR Code...';
      
      try {
        // Use universal validation API
        // Use project-relative API path to avoid APP_URL dependency
        const response = await fetch('../../api/qr_universal_validate.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ code: qrCode })
        });
        
        const data = await response.json();
        
        if (response.ok && data.redirect_url) {
          showLoading('Redirecting to payment...');
          document.getElementById('status').textContent = 'QR Code Valid! Redirecting...';
          document.getElementById('reader').classList.add('valid');
          beep();
          if(navigator.vibrate) navigator.vibrate([100, 50, 100]);
          // Immediate redirect to payment page
          setTimeout(() => {
            window.location.href = data.redirect_url;
          }, 500);
        } else {
          // Show error and hide loading
          document.getElementById('reader').classList.add('invalid');
          document.getElementById('status').textContent = data.message || 'Invalid QR Code';
          document.getElementById('status').style.color = '#dc2626';
          const overlay = document.getElementById('scanLoadingOverlay');
          if (overlay) {
            overlay.innerHTML = '<div style="text-align:center;max-width:400px;padding:30px;background:white;border-radius:12px;color:#dc2626;"><i class="bi bi-x-circle" style="font-size:3rem;"></i><h4 class="mt-3">Invalid QR Code</h4><p>' + (data.message || 'QR code not recognized') + '</p><button class="btn btn-success mt-3" onclick="location.reload()">Scan Again</button></div>';
            setTimeout(() => {
              overlay.style.display = 'none';
              document.getElementById('reader').classList.remove('invalid');
              document.getElementById('status').textContent = 'Ready to scan...';
              document.getElementById('status').style.color = '#0f172a';
            }, 3000);
          }
        }
      } catch (error) {
        console.error('QR validation error:', error);
        document.getElementById('reader').classList.add('invalid');
        document.getElementById('status').textContent = 'Connection Error';
        document.getElementById('status').style.color = '#dc2626';
        const overlay = document.getElementById('scanLoadingOverlay');
        if (overlay) {
          overlay.innerHTML = '<div style="text-align:center;max-width:400px;padding:30px;background:white;border-radius:12px;color:#dc2626;"><i class="bi bi-exclamation-triangle" style="font-size:3rem;"></i><h4 class="mt-3">Connection Error</h4><p>Unable to validate QR code. Please try again.</p><button class="btn btn-success mt-3" onclick="location.reload()">Retry</button></div>';
          setTimeout(() => {
            overlay.style.display = 'none';
            document.getElementById('reader').classList.remove('invalid');
            document.getElementById('status').textContent = 'Ready to scan...';
            document.getElementById('status').style.color = '#0f172a';
          }, 3000);
        }
      }
    };
    
    const onScanSuccess = (decodedText) => {
      validateAndRedirect(decodedText);
    };
    
    try{ 
      const scanner = new Html5QrcodeScanner('reader',{fps:10,qrbox:200}); 
      scanner.render(onScanSuccess);
      document.getElementById('status').textContent = 'Camera ready. Show your QR code...';
    }catch(e){
      console.error('Scanner init error:', e);
      document.getElementById('status').textContent = 'Camera error: ' + e.message;
      document.getElementById('status').style.color = '#dc2626';
    }
  })();
  </script>
</body>
</html>
