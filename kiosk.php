<?php 
require __DIR__.'/config.php'; 
if (!function_exists('is_staff') || !is_staff()) { header('Location: '.APP_URL.'menu.php'); exit; }
include __DIR__.'/partials/header.php'; 
if(!isset($_SESSION['cart'])) $_SESSION['cart']=[]; 
?>
<section class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4 fade-in">
    <div>
      <h1 class="h4 mb-0" style="letter-spacing: -0.01em;">Self-Service Kiosk</h1>
      <p class="text-secondary small fw-semibold mb-0">Select items to add to cart or scan an order QR</p>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-success" id="btnScan"><i class="bi bi-qr-code-scan"></i> Scan QR</button>
      <a class="btn btn-primary" href="cart.php">
        <i class="bi bi-cart3"></i> Cart (<span id="cartCount"><?=array_sum($_SESSION['cart'])?></span>)
      </a>
    </div>
  </div>

  <div id="scanWrap" class="card mb-4" style="display:none">
    <div class="card-body">
      <div class="row g-3 align-items-center">
        <div class="col-md-6">
          <video id="preview" style="width:100%;border-radius:12px;background:#000"></video>
        </div>
        <div class="col-md-6">
          <div class="small text-muted mb-2">Scan result</div>
          <pre id="scanResult" class="p-3 bg-light rounded" style="min-height:120px"></pre>
          <div class="text-muted small">Tip: Point the camera at the QR shown on the receipt.</div>
        </div>
      </div>
    </div>
  </div>
  <div class="mb-4">
    <ul class="nav nav-pills" id="pills-tab" role="tablist" style="gap: 0.5rem;">
      <?php $cats=$mysqli->query('SELECT id,name FROM categories ORDER BY sort_order'); $i=0; while($c=$cats->fetch_assoc()): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?=$i==0?'active':''?>" data-bs-toggle="pill" data-bs-target="#cat<?=$c['id']?>" type="button" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
            <?=htmlspecialchars($c['name'])?>
          </button>
        </li>
      <?php $i++; endwhile; ?>
    </ul>
  </div>
  <div class="tab-content">
    <?php $cats=$mysqli->query('SELECT id,name FROM categories ORDER BY sort_order'); $first=true; while($c=$cats->fetch_assoc()): ?>
      <div class="tab-pane fade show <?= $first ? 'active' : '' ?>" id="cat<?=$c['id']?>">
        <div class="row g-3">
          <?php $p=$mysqli->prepare("SELECT p.id,p.name,p.description,p.price,(SELECT filename FROM product_images WHERE product_id=p.id ORDER BY is_cover DESC, id DESC LIMIT 1) img FROM products p WHERE p.category_id=? AND p.is_active=1 ORDER BY p.name"); $p->bind_param('i',$c['id']); $p->execute(); $res=$p->get_result(); while($r=$res->fetch_assoc()): ?>
            <div class="col-6 col-md-4 col-lg-3">
              <div class="product-card h-100">
                <div class="position-relative" style="overflow: hidden; border-bottom: 1px solid var(--gray-200); border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                  <img src="<?= e($r['img'] ? (APP_URL.'uploads/products/'.$r['img']) : (APP_URL.'assets/img/placeholder.php?w=400&h=400&text=No+Image')) ?>" class="w-100" alt="<?= e($r['name']) ?>" style="height: 180px; object-fit: cover;">
                </div>
                <div class="p-3">
                  <div class="badge badge-soft-primary mb-2" style="font-size: 0.65rem;"><?=htmlspecialchars($c['name'])?></div>
                  <h6 class="mb-1" style="font-size: 0.9rem; font-weight: 600;"><?=htmlspecialchars($r['name'])?></h6>
                  <div class="d-flex justify-content-between align-items-center mt-3">
                    <span class="product-price" style="font-size: 1rem;">₱<?=number_format($r['price'],2)?></span>
                    <form method="post" action="cart.php" class="m-0">
                      <input type="hidden" name="action" value="add">
                      <input type="hidden" name="product_id" value="<?=$r['id']?>">
                      <input type="hidden" name="qty" value="1">
                      <button class="btn btn-success btn-sm" style="font-size: 0.8rem;">
                        <i class="bi bi-plus-circle"></i> Add
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          <?php endwhile; $p->close(); ?>
        </div>
      </div>
      <?php $first=false; ?>
    <?php endwhile; ?>
  </div>
</section>
<script src="https://unpkg.com/@zxing/library@0.20.0"></script>
<script>
// Auto-update cart count
(function(){
  const cartCountEl = document.getElementById('cartCount');
  function inc(n){ const cur = parseInt(cartCountEl.textContent||'0',10)||0; cartCountEl.textContent = cur + n; }
  document.querySelectorAll('form[action="cart.php"]').forEach(function(f){
    f.addEventListener('submit', function(e){ e.preventDefault(); const fd=new FormData(f); const qty=parseInt(fd.get('qty')||'1',10)||1; fetch('cart.php',{method:'POST',body:fd,credentials:'same-origin'}).then(()=>inc(qty)).catch(()=>inc(qty)); });
  });
})();

// QR Scan (ORDER vs REDEMPTION)
(function(){
  const btn = document.getElementById('btnScan');
  const wrap = document.getElementById('scanWrap');
  const video = document.getElementById('preview');
  const out = document.getElementById('scanResult');
  if(!btn) return;
  let codeReader;
  let isScanning = false;
  
  btn.addEventListener('click', async ()=>{
    const isVisible = wrap.style.display !== 'none';
    wrap.style.display = isVisible ? 'none' : 'block';
    
    if(isVisible){
      // Stop scanning when hiding
      if(codeReader && isScanning){
        try{
          codeReader.reset();
          isScanning = false;
        }catch(e){}
      }
      return;
    }
    
    if(!codeReader){
      codeReader = new ZXing.BrowserMultiFormatReader();
    }
    
    try{
      // List available video input devices
      const devices = await codeReader.listVideoInputDevices();
      const cam = devices && devices.length > 0 ? devices[0].deviceId : null;
      
      out.textContent = 'Starting camera...';
      isScanning = true;
      
      await codeReader.decodeFromVideoDevice(cam, video, (result, err)=>{
        if(result){
          try{
            out.textContent = result.getText();
            const text = result.getText();
            let payload = null;
            try{ payload = JSON.parse(text); }catch(e){ payload = null; }
            
            if(payload && payload.code){
              // ORDER payload
              codeReader.reset();
              isScanning = false;
              window.location.href = 'payment_success.php?order=' + encodeURIComponent(payload.code);
            } else if(text.startsWith('REDEEM:') || (payload && payload.type==='redeem')){
              const code = payload?.voucher || text.replace('REDEEM:','');
              codeReader.reset();
              isScanning = false;
              window.location.href = 'pos/redeem.php?code=' + encodeURIComponent(code);
            }
          }catch(e){ 
            out.textContent = 'Invalid QR: ' + e.message; 
          }
        }
        if(err && !result){
          // Don't show error for every frame, only log it
          console.log('Scan error:', err);
        }
      });
    }catch(err){ 
      out.textContent = 'Camera error: ' + err.message; 
      isScanning = false;
    }
  });
})();
</script>
<?php include __DIR__.'/partials/footer.php'; ?>
