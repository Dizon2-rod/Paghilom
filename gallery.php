<?php
require __DIR__.'/config.php';
include __DIR__.'/partials/header.php';

// Fetch gallery images
$result = $mysqli->query("SELECT id, filename, caption, created_at FROM gallery WHERE is_active=1 ORDER BY sort_order, id DESC");
$images = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<style>
/* Responsive Gallery Grid */
.gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
.gallery-item { position: relative; border-radius: 14px; overflow: hidden; background: #fff; box-shadow: 0 4px 12px rgba(16,24,40,.06); transition: transform .25s ease, box-shadow .25s ease; cursor: pointer; }
.gallery-item img { width: 100%; height: 220px; object-fit: cover; transform: scale(1); transition: transform .3s ease; display:block; }
.gallery-item:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(16,24,40,.12); }
.gallery-item:hover img { transform: scale(1.06); }
.gallery-caption { padding: .5rem .75rem; background: #fff; border-top: 1px solid rgba(16,24,40,.06); font-size: .85rem; color: #475467; }
@media (max-width: 576px){ .gallery-item img { height: 180px; } }

/* Lightbox controls */
.lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,.45); color:#fff; border:none; width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; z-index:1056; }
.lightbox-nav:hover { background: rgba(0,0,0,.6); }
.lightbox-prev { left: 10px; }
.lightbox-next { right: 10px; }
.zoom-wrap.zoomed { cursor: zoom-out; }
.zoom-wrap.zoomed img { transform: scale(2); }
</style>

<section class="container py-5">
  <div class="d-flex justify-content-between align-items-end mb-3">
    <div>
      <h1 class="h3 mb-1">Gallery</h1>
      <p class="text-muted mb-0">Browse moments from Paghilom Café</p>
    </div>
  </div>

  <?php if(empty($images)): ?>
    <div class="alert alert-info">No images in the gallery yet.</div>
  <?php else: ?>
    <div class="gallery-grid" id="galleryGrid">
      <?php $i=0; foreach($images as $img): 
        $fname = trim($img['filename']);
        $src = APP_URL . 'uploads/gallery/' . rawurlencode($fname);
        $cap = trim($img['caption'] ?? '');
        $date = !empty($img['created_at']) ? date('M d, Y', strtotime($img['created_at'])) : '';
        $label = $cap; // no fallback text
      ?>
        <figure class="gallery-item" data-index="<?= $i ?>" data-src="<?= e($src) ?>" data-caption="<?= e($label) ?>">
          <img src="<?= e($src) ?>" alt="" onerror="this.onerror=null;this.src='<?= APP_URL ?>assets/img/placeholder.php?w=600&h=400&text=No+Image';">
        </figure>
      <?php $i++; endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- Custom Lightbox Overlay (independent from Bootstrap JS) -->
<div id="lbOverlay" style="position:fixed; inset:0; background:rgba(0,0,0,.9); display:none; z-index:9999;">
  <button type="button" id="lbClose" aria-label="Close" style="position:absolute; top:14px; right:14px; background:#fff; border:none; width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,.2);"><i class="bi bi-x"></i></button>
  <button type="button" class="lightbox-nav lightbox-prev" id="lbPrev" aria-label="Previous" style="left:10px; z-index:10000;"><i class="bi bi-chevron-left"></i></button>
  <button type="button" class="lightbox-nav lightbox-next" id="lbNext" aria-label="Next" style="right:10px; z-index:10000;"><i class="bi bi-chevron-right"></i></button>
  <div id="lbWrap" style="position:absolute; inset:60px 16px 60px 16px; display:flex; align-items:center; justify-content:center; overflow:hidden; cursor:zoom-in;">
    <img id="lbImg" src="" alt="Preview" style="max-width:100%; max-height:100%; transition: transform .2s ease;">
  </div>
  <div id="lbCaption" class="text-center text-white" style="display:none; position:absolute; left:0; right:0; bottom:10px; padding:4px 12px; opacity:.9;"></div>
  <div id="lbCtrls" style="position:absolute; right:16px; bottom:16px; display:flex; gap:8px;">
    <button id="lbZoomOut" title="Zoom out" style="background:#fff; border:none; width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,.2);"><i class="bi bi-zoom-out"></i></button>
    <button id="lbZoomIn" title="Zoom in" style="background:#fff; border:none; width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,.2);"><i class="bi bi-zoom-in"></i></button>
    <button id="lbZoomReset" title="Reset" style="background:#fff; border:none; width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 10px rgba(0,0,0,.2);"><i class="bi bi-aspect-ratio"></i></button>
  </div>
</div>

<script>
(function(){
  const items = Array.from(document.querySelectorAll('.gallery-item'));
  const data = items.map(el => ({ src: el.dataset.src, caption: el.dataset.caption }));
  // Custom lightbox elements
  const ovr = document.getElementById('lbOverlay');
  const lbImg = document.getElementById('lbImg');
  const lbWrap = document.getElementById('lbWrap');
  const lbCap = document.getElementById('lbCaption');
  const lbClose = document.getElementById('lbClose');
  const lbPrev = document.getElementById('lbPrev');
  const lbNext = document.getElementById('lbNext');
  let idx = 0;
  let zoom = 1, minZ=1, maxZ=4, stepZ=0.25;

  function setZoom(z){
    zoom = Math.max(minZ, Math.min(maxZ, z));
    lbImg.style.transform = 'scale(' + zoom + ')';
    if (zoom > 1) { lbWrap.classList.add('zoomed'); lbWrap.style.cursor='grab'; }
    else { lbWrap.classList.remove('zoomed'); lbWrap.style.cursor='zoom-in'; }
  }

  function openLB(i){
    idx = (i + data.length) % data.length;
    const {src, caption} = data[idx];
    const pre = new Image();
    pre.onload = () => { lbImg.src = src; lbCap.textContent = caption || ''; ovr.style.display='block'; document.body.style.overflow='hidden'; setZoom(1); lbImg.style.transformOrigin = '50% 50%'; };
    pre.onerror = () => { lbImg.src = '<?= APP_URL ?>assets/img/placeholder.php?w=1200&h=800&text=Image+not+found'; lbCap.textContent = caption || ''; ovr.style.display='block'; document.body.style.overflow='hidden'; setZoom(1); lbImg.style.transformOrigin = '50% 50%'; };
    pre.src = src;
  }
  function closeLB(){ ovr.style.display='none'; document.body.style.overflow=''; }

  items.forEach((el,i)=> el.addEventListener('click', (e)=>{ e.preventDefault(); openLB(i); }));
  lbClose.addEventListener('click', closeLB);
  ovr.addEventListener('click', (e)=>{ if(e.target===ovr) closeLB(); });
  lbPrev.addEventListener('click', ()=> openLB(idx-1));
  lbNext.addEventListener('click', ()=> openLB(idx+1));
  window.addEventListener('keydown', (e)=>{ if(ovr.style.display==='block'){ if(e.key==='Escape') closeLB(); if(e.key==='ArrowLeft') openLB(idx-1); if(e.key==='ArrowRight') openLB(idx+1); } });

  // Zoom & pan inside custom lightbox
  function setOriginFromEvent(e){
    const rect = lbWrap.getBoundingClientRect();
    const pointX = (e.touches? e.touches[0].clientX : e.clientX) - rect.left;
    const pointY = (e.touches? e.touches[0].clientY : e.clientY) - rect.top;
    const ox = Math.max(0, Math.min(100, pointX/rect.width*100));
    const oy = Math.max(0, Math.min(100, pointY/rect.height*100));
    lbImg.style.transformOrigin = ox+'% '+oy+'%';
  }

  // Click to toggle zoom 1<->2
  lbWrap.addEventListener('click', (e)=>{ if(zoom===1){ setOriginFromEvent(e); setZoom(2); } else { setZoom(1); } });

  // Mouse drag to pan (by changing origin)
  let isPan=false; lbWrap.addEventListener('mousedown', e=>{ if(zoom===1) return; isPan=true; setOriginFromEvent(e);});
  window.addEventListener('mouseup', ()=> isPan=false);
  lbWrap.addEventListener('mousemove', e=>{ if(!isPan) return; setOriginFromEvent(e);});

  // Mouse wheel zoom
  lbWrap.addEventListener('wheel', e=>{ e.preventDefault(); setOriginFromEvent(e); setZoom(zoom + (e.deltaY<0? stepZ : -stepZ)); }, {passive:false});

  // Touch: pan and pinch zoom
  let pinchStartDist=null; lbWrap.addEventListener('touchstart', e=>{
    if(e.touches.length===2){
      const dx = e.touches[0].clientX - e.touches[1].clientX;
      const dy = e.touches[0].clientY - e.touches[1].clientY;
      pinchStartDist = Math.hypot(dx,dy);
    } else if(e.touches.length===1 && zoom>1){ isPan=true; setOriginFromEvent(e); }
  }, {passive:true});
  lbWrap.addEventListener('touchmove', e=>{
    if(e.touches.length===2 && pinchStartDist){
      e.preventDefault();
      const dx = e.touches[0].clientX - e.touches[1].clientX;
      const dy = e.touches[0].clientY - e.touches[1].clientY;
      const d = Math.hypot(dx,dy);
      const factor = d / pinchStartDist;
      setOriginFromEvent({touches:[{clientX:(e.touches[0].clientX+e.touches[1].clientX)/2, clientY:(e.touches[0].clientY+e.touches[1].clientY)/2}]});
      setZoom(zoom * factor);
      pinchStartDist = d;
    } else if(e.touches.length===1 && isPan){ setOriginFromEvent(e); }
  }, {passive:false});
  lbWrap.addEventListener('touchend', ()=>{ if(pinchStartDist) pinchStartDist=null; isPan=false; }, {passive:true});

  // Buttons
  document.getElementById('lbZoomIn').addEventListener('click', ()=> setZoom(zoom+stepZ));
  document.getElementById('lbZoomOut').addEventListener('click', ()=> setZoom(zoom-stepZ));
  document.getElementById('lbZoomReset').addEventListener('click', ()=> setZoom(1));
})();
</script>

<?php include __DIR__.'/partials/footer.php'; ?>
