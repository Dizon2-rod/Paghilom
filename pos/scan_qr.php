<?php
require_once __DIR__ . '/../config.php';
require_pos();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>POS | Scan QR</title>
  <link rel="icon" type="image/png" href="../assets/images/logo.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
  <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
  <style>
    :root { --accent:#16a34a; --danger:#b00020; }
    body { margin:0; background:#000; color:#fff; }
    .wrap { min-height:100vh; display:flex; flex-direction:column; }
    header { padding: 12px 16px; background:#111; display:flex; justify-content:space-between; align-items:center; }
    main { flex:1; display:flex; align-items:center; justify-content:center; }
    .scanner { width: min(94vw, 680px); }
    #reader { width: 100%; aspect-ratio: 3/3; border:4px solid rgba(255,255,255,0.2); border-radius: 12px; position:relative; overflow:hidden; transition: border-color .2s ease; }
    #reader.valid { border-color: var(--accent); }
    #reader.invalid { border-color: var(--danger); }
    .hint { text-align:center; margin: 12px 0 0; opacity:.9; }
    .status { text-align:center; min-height: 1.5em; margin-top:8px; }
    .back { color:#ccc; }
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <strong>POS Scanner</strong>
      <a class="back" href="index.php">Back</a>
    </header>
    <main>
      <section class="scanner">
        <div id="reader"></div>
        <p class="hint">📷 Show your QR Code to the camera. The system will automatically proceed to payment.</p>
        <p id="status" class="status"></p>
      </section>
    </main>
  </div>

  <script>
    (function(){
      const statusEl = document.getElementById('status');
      const readerEl = document.getElementById('reader');

      const showStatus = (msg, ok=false) => {
        statusEl.textContent = msg;
        statusEl.style.color = ok ? '#16a34a' : '#fff';
      };

      const beep=()=>{try{const c=new (window.AudioContext||window.webkitAudioContext)();const o=c.createOscillator(),g=c.createGain();o.type='sine';o.frequency.value=880;o.connect(g);g.connect(c.destination);g.gain.setValueAtTime(0.001,c.currentTime);g.gain.exponentialRampToValueAtTime(0.1,c.currentTime+0.01);o.start();setTimeout(()=>{g.gain.exponentialRampToValueAtTime(0.001,c.currentTime+0.05);o.stop(c.currentTime+0.06)},60)}catch(e){}};

      async function validateAndRedirect(decodedText){
        showStatus('Validating QR Code...');
        readerEl.classList.remove('valid','invalid');
        try{
          // Use project-relative API path to avoid APP_URL dependency
          const resp = await fetch('../api/qr_universal_validate.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ code: decodedText })
          });
          const data = await resp.json();
          if(resp.ok && data.success && data.redirect_url){
            readerEl.classList.add('valid');
            beep(); if(navigator.vibrate) navigator.vibrate([80,40,80]);
            showStatus('QR valid. Redirecting...', true);
            // POS payment page (relative redirect works: payment.php -> pos/payment.php)
            setTimeout(()=>{ window.location.href = data.redirect_url; }, 400);
          } else {
            readerEl.classList.add('invalid');
            showStatus(data.message || 'Invalid QR Code', false);
          }
        }catch(e){
          readerEl.classList.add('invalid');
          showStatus('Connection error. Please try again.', false);
        }
      }

      try{
        const scanner = new Html5QrcodeScanner('reader',{ fps:10, qrbox: 220 });
        scanner.render((txt)=>validateAndRedirect(txt));
        showStatus('Camera ready. Show your QR code...');
      }catch(e){
        showStatus('Camera error: '+e.message, false);
      }
    })();
  </script>
</body>
</html>
