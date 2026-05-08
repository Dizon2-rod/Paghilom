// Kiosk QR scanner controller
// Requirements implemented:
// - Continuous camera scanning with html5-qrcode
// - Accept only ORDER-##### or REWARD-##### patterns (client pre-check)
// - Calls backend for strict validation; ignores invalid and keeps scanning
// - Visual feedback (border flash) and beep on success

function initKioskQrScanner({ elementId, statusId, endpoint, onRedirect, processingDelayMs = 700 }) {
  const readerEl = document.getElementById(elementId);
  const statusEl = statusId ? document.getElementById(statusId) : null;
  let html5Qr = null;
  let running = false;
  let lastText = null;
  let lastAt = 0;
  // lightweight processing overlay
  let overlay = document.getElementById('qr-processing-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'qr-processing-overlay';
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.background = 'rgba(0,0,0,.6)';
    overlay.style.display = 'none';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '9999';
    overlay.innerHTML = '<div style="color:#fff; font-size:18px; text-align:center;">Processing…<div style="margin-top:8px; width:36px; height:36px; border:4px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin 1s linear infinite;"></div></div>';
    document.body.appendChild(overlay);
    const style = document.createElement('style');
    style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(style);
  }

  // Accept multiple QR code formats:
  // - ORDER-##### or ORD##### (orders)
  // - REWARD-##### or PHC-##### (rewards/vouchers)
  // - JSON format with type and code
  const ORDER_RE = /^(ORDER-|ORD)[A-Z0-9]{5,}$/i;
  const REWARD_RE = /^(REWARD-|PHC-|REW)[A-Z0-9]{5,}$/i;

  function setStatus(msg, isError=false) {
    if (!statusEl) return;
    statusEl.textContent = msg || '';
    statusEl.style.color = isError ? '#ff8a80' : '#ffffff';
  }

  function flash(className, ms=200) {
    if (!readerEl) return;
    readerEl.classList.remove('valid','invalid');
    readerEl.classList.add(className);
    setTimeout(() => readerEl.classList.remove(className), ms);
  }

  function beep() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator();
      const g = ctx.createGain();
      o.type = 'sine';
      o.frequency.value = 880;
      o.connect(g); g.connect(ctx.destination);
      o.start();
      g.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.2);
      setTimeout(() => { o.stop(); ctx.close(); }, 220);
    } catch (_) { /* ignore */ }
  }

  async function verify(code) {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code })
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || 'Invalid QR');
    return data;
  }

  function isValidQrFormat(text) {
    // Check if it matches known QR patterns
    if (ORDER_RE.test(text) || REWARD_RE.test(text)) return true;
    
    // Check if it's JSON with type and code
    try {
      const data = JSON.parse(text);
      if (data.type && data.code) return true;
    } catch (e) { /* not JSON */ }
    
    // Check if it's a URL with code parameter
    if (text.includes('?code=') || text.includes('&code=')) return true;
    
    return false;
  }

  function shouldIgnore(text) {
    // Client-side pre-filter to avoid spamming backend
    if (!isValidQrFormat(text)) return true;
    
    // Debounce duplicates within 0.8s for faster re-scanning
    const now = Date.now();
    if (text === lastText && now - lastAt < 800) return true;
    lastText = text; lastAt = now; return false;
  }

  async function onDecode(text) {
    if (shouldIgnore(text)) return;
    setStatus('QR detected. Validating…');
    try {
      const data = await verify(text);
      flash('valid');
      beep();
      await stop();
      // brief processing overlay for smooth UX
      overlay.style.display = 'flex';
      const url = data.redirect_url || (`payment.php?mode=${encodeURIComponent(data.type)}&id=${encodeURIComponent(data.id)}`);
      setTimeout(() => { if (onRedirect) onRedirect(url); else window.location.href = url; }, processingDelayMs);
    } catch (e) {
      console.error(e);
      flash('invalid');
      setStatus(e.message || 'Invalid QR Code. Please scan a valid Order or Reward QR.', true);
      // keep scanning
    }
  }

  async function start() {
    if (running) return;
    html5Qr = new Html5Qrcode(elementId);
    const config = { 
      fps: 30, // Increased FPS for faster detection
      qrbox: (viewW, viewH) => Math.min(400, Math.floor(Math.min(viewW, viewH) * 0.8)), 
      aspectRatio: 1.0, 
      rememberLastUsedCamera: true,
      // Advanced settings for instant scanning
      experimentalFeatures: {
        useBarCodeDetectorIfSupported: true
      },
      formatsToSupport: [
        Html5QrcodeSupportedFormats.QR_CODE
      ],
      verbose: false
    };
    
    // Try different camera configurations
    const cameraConfigs = [
      { facingMode: 'environment' },  // Try rear camera first (without 'exact')
      { facingMode: 'user' },         // Try front camera
      undefined                        // Let browser choose default
    ];
    
    let started = false;
    for (const cameraConfig of cameraConfigs) {
      try {
        await html5Qr.start(cameraConfig || {}, config, onDecode);
        running = true;
        started = true;
        setStatus('Align your QR within the frame…');
        break;
      } catch (e) {
        console.warn('Camera config failed:', cameraConfig, e);
        continue;
      }
    }
    
    if (!started) {
      setStatus('❌ Camera access denied or not available. Please enable camera permissions in your browser settings.', true);
      // Show instructions
      if (statusEl) {
        setTimeout(() => {
          statusEl.innerHTML = '<div style="text-align:left; padding:20px; background:rgba(255,255,255,0.1); border-radius:8px; margin-top:20px;">' +
            '<strong>To enable camera:</strong><br>' +
            '1. Click the 🔒 or camera icon in the address bar<br>' +
            '2. Select "Allow" for Camera<br>' +
            '3. Refresh this page</div>';
        }, 500);
      }
    }
  }

  async function stop() {
    if (!running || !html5Qr) return;
    try { await html5Qr.stop(); await html5Qr.clear(); } catch (_) {}
    running = false;
  }

  window.addEventListener('beforeunload', stop);
  start();
}
