<?php 
require __DIR__.'/config.php'; 

// Staff and admin accounts should not use the online ordering cart
// Exception: Allow staff access when coming from kiosk
$fromKiosk = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'kiosk.php') !== false;
if ($fromKiosk) {
    $_SESSION['from_kiosk'] = true;
}
if (((function_exists('is_staff') && is_staff()) || (function_exists('is_admin') && is_admin())) && !isset($_SESSION['from_kiosk'])) {
    header('Location: ' . APP_URL . 'index.php');
    exit;
}

if(!isset($_SESSION['cart'])) $_SESSION['cart']=[]; 

// Ensure persistent cart table exists
$mysqli->query("CREATE TABLE IF NOT EXISTS user_carts (user_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL DEFAULT 1, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY(user_id,product_id)) ENGINE=InnoDB");

function persist_session_cart_to_db($mysqli){
  if(!isset($_SESSION['user']['id'])) return; $uid=(int)$_SESSION['user']['id'];
  $stmt = $mysqli->prepare('REPLACE INTO user_carts(user_id,product_id,qty) VALUES(?,?,?)');
  foreach(($_SESSION['cart']??[]) as $pid=>$qty){ $p=(int)$pid; $q=max(0,(int)$qty); if($q>0){ $stmt->bind_param('iii',$uid,$p,$q); $stmt->execute(); } else { $del=$mysqli->prepare('DELETE FROM user_carts WHERE user_id=? AND product_id=?'); $del->bind_param('ii',$uid,$p); $del->execute(); $del->close(); } }
  $stmt->close();
}
function load_db_cart_to_session($mysqli){
  if(!isset($_SESSION['user']['id'])) return; $uid=(int)$_SESSION['user']['id'];
  $res=$mysqli->prepare('SELECT product_id,qty FROM user_carts WHERE user_id=?'); $res->bind_param('i',$uid); $res->execute(); $rs=$res->get_result(); $_SESSION['cart']=[]; while($r=$rs->fetch_assoc()){ $_SESSION['cart'][(int)$r['product_id']] = (int)$r['qty']; } $res->close();
}

// On page load: if logged in, sync session from DB
if(isset($_SESSION['user']['id'])){ load_db_cart_to_session($mysqli); }

$action=$_POST['action']??'';
if($action==='add'){
  $pid=(int)$_POST['product_id']; $qty=max(1,(int)$_POST['qty']); $_SESSION['cart'][$pid]=($_SESSION['cart'][$pid]??0)+$qty; 
  persist_session_cart_to_db($mysqli);
  header('Location:cart.php'); exit; 
}
if($action==='update'){
  foreach(($_POST['qty']??[]) as $pid=>$q){ $_SESSION['cart'][(int)$pid]=max(1,(int)$q);} 
  persist_session_cart_to_db($mysqli);
}
if($action==='clear_selected'){
  $sel = array_map('intval', $_POST['selected'] ?? []);
  foreach($sel as $pid){ unset($_SESSION['cart'][$pid]); }
  // remove from DB as well
  if(isset($_SESSION['user']['id']) && $sel){
    $uid=(int)$_SESSION['user']['id'];
    $in = implode(',', array_fill(0,count($sel),'?'));
    $types = str_repeat('i', count($sel)+1);
    $params = array_merge([$uid], $sel);
    $stmt = $mysqli->prepare('DELETE FROM user_carts WHERE user_id=? AND product_id IN ('.implode(',', array_fill(0,count($sel),'?')).')');
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
  }
}

include __DIR__.'/partials/header.php';
$ids=array_keys($_SESSION['cart']); $items=[]; $subtotal=0;
if($ids){
  $in = implode(',', array_fill(0,count($ids),'?')); $types=str_repeat('i',count($ids));
  $stmt=$mysqli->prepare("SELECT id,name,price FROM products WHERE id IN ($in)");
  $stmt->bind_param($types, ...$ids); $stmt->execute(); $res=$stmt->get_result();
  while($p=$res->fetch_assoc()){ $qty=$_SESSION['cart'][$p['id']] ?? 0; if($qty<=0) continue; $line=$p['price']*$qty; $subtotal+=$line; $items[]=$p+['qty'=>$qty,'line'=>$line]; }
  $stmt->close();
}
?>
<section class="container py-4">
  <h1 class="h4 mb-3">Cart</h1>
  <form method="post" class="card shadow-sm">
    <input type="hidden" name="action" value="update">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead><tr><th style="width:40px"><input type="checkbox" onclick="document.querySelectorAll('.selItem').forEach(cb=>cb.checked=this.checked)"></th><th>Item</th><th style="width:120px">Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach($items as $it): ?>
          <tr data-pid="<?=$it['id']?>" data-price="<?=$it['price']?>">
            <td><input class="form-check-input selItem" type="checkbox" name="selected[]" value="<?=$it['id']?>"></td>
            <td><?=htmlspecialchars($it['name'])?></td>
            <td><input class="form-control qty-input" type="number" name="qty[<?=$it['id']?>]" min="1" step="1" value="<?=$it['qty']?>"></td>
            <td>₱<?=number_format($it['price'],2)?></td>
            <td>₱<span class="line-total"><?=number_format($it['line'],2)?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card-body d-flex align-items-center justify-content-between">
      <div><strong>Subtotal:</strong> ₱<span id="subtotalAmount"><?=number_format($subtotal,2)?></span></div>
      <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-danger" name="action" value="clear_selected" type="submit">Clear Selected</button>
        <button id="btnCheckoutSelected" class="btn btn-success" type="submit" formaction="checkout.php" formmethod="post">Checkout Selected →</button>
      </div>
    </div>
    <div class="card-footer small text-muted">📦 Self‑Service: pickup at counter. Select items to checkout; unselected stay in your cart.</div>
  </form>
</section>
<script>
// Auto-update line totals and subtotal; persist to server (debounced)
(function(){
  const fmt = n => (Number(n)||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  const subtotalEl = document.getElementById('subtotalAmount');
  const rows = Array.from(document.querySelectorAll('tbody tr[data-pid]'));
  function clampQty(v){ v = parseInt(v,10); return isNaN(v)||v<1 ? 1 : v; }
  function recalc(){
    let sub = 0;
    rows.forEach(tr => {
      const price = parseFloat(tr.getAttribute('data-price'))||0;
      const qInput = tr.querySelector('.qty-input');
      const qty = clampQty(qInput?.value||'1');
      if(qInput) qInput.value = qty;
      const line = price*qty;
      tr.querySelector('.line-total').textContent = fmt(line);
      sub += line;
    });
    subtotalEl.textContent = fmt(sub);
  }
  let t;
  function persist(){
    clearTimeout(t);
    t = setTimeout(()=>{
      const fd = new FormData();
      fd.append('action','update');
      rows.forEach(tr => {
        const pid = tr.getAttribute('data-pid');
        const qInput = tr.querySelector('.qty-input');
        const qty = clampQty(qInput?.value||'1');
        fd.append(`qty[${pid}]`, qty);
      });
      fetch('cart.php', {method:'POST', body:fd, credentials:'same-origin'});
    }, 200);
  }
  document.querySelectorAll('.qty-input').forEach(inp => {
    inp.addEventListener('input', function(){ recalc(); persist(); });
    inp.addEventListener('change', function(){ recalc(); persist(); });
  });

  // Prevent checkout without selection
  const btnCheckout = document.getElementById('btnCheckoutSelected');
  if(btnCheckout){
    btnCheckout.addEventListener('click', function(e){
      const any = document.querySelector('.selItem:checked');
      if(!any){ e.preventDefault(); alert('Pumili muna ng item para i-checkout.'); }
    });
  }
})();
</script>
<?php include __DIR__.'/partials/footer.php'; ?>
