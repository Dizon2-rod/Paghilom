<?php
require __DIR__.'/../config.php';
require_login();

$uid = (int)($_SESSION['user']['id'] ?? 0);
if(!$uid){ header('Location: ../login.php'); exit; }

// Pagination
$page = isset($_GET['page']) ? max(1,(int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Search
$q = trim($_GET['q'] ?? '');
$search_by_code = false;

// Get total orders for this user (with optional search)
if ($q !== '') {
    // Try searching by code if column exists; fallback to id cast search
    $sql_cnt = 'SELECT COUNT(*) as c FROM orders WHERE user_id=? AND (code LIKE CONCAT("%",?,"%") OR CAST(id AS CHAR) LIKE CONCAT("%",?,"%"))';
    $st_cnt = $mysqli->prepare($sql_cnt);
    if ($st_cnt) {
        $search_by_code = true;
        $st_cnt->bind_param('iss', $uid, $q, $q);
    } else {
        $sql_cnt = 'SELECT COUNT(*) as c FROM orders WHERE user_id=? AND CAST(id AS CHAR) LIKE CONCAT("%",?,"%")';
        $st_cnt = $mysqli->prepare($sql_cnt);
        $st_cnt->bind_param('is', $uid, $q);
    }
    $st_cnt->execute();
    $total_orders = (int)($st_cnt->get_result()->fetch_assoc()['c'] ?? 0);
    $st_cnt->close();
} else {
    $st_cnt = $mysqli->prepare('SELECT COUNT(*) as c FROM orders WHERE user_id=?');
    $st_cnt->bind_param('i', $uid);
    $st_cnt->execute();
    $total_orders = (int)($st_cnt->get_result()->fetch_assoc()['c'] ?? 0);
    $st_cnt->close();
}
$total_pages = max(1, (int)ceil($total_orders / $per_page));

// Get orders list
if ($q !== '') {
    if ($search_by_code) {
        $st = $mysqli->prepare('SELECT * FROM orders WHERE user_id=? AND (code LIKE CONCAT("%",?,"%") OR CAST(id AS CHAR) LIKE CONCAT("%",?,"%")) ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $st->bind_param('issii', $uid, $q, $q, $per_page, $offset);
    } else {
        $st = $mysqli->prepare('SELECT * FROM orders WHERE user_id=? AND CAST(id AS CHAR) LIKE CONCAT("%",?,"%") ORDER BY created_at DESC LIMIT ? OFFSET ?');
        $st->bind_param('isii', $uid, $q, $per_page, $offset);
    }
} else {
    $st = $mysqli->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?');
    $st->bind_param('iii', $uid, $per_page, $offset);
}
$st->execute();
$orders = $st->get_result();

include __DIR__.'/../partials/header.php';
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
:root{ --brand:#2A5618; --ink:#101828; --muted:#667085; }
body{ font-family:'Inter', system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; }
.section-head{ background: linear-gradient(180deg, #fff, #f6faf7); border-bottom:1px solid #eef2f7; }
.card-elev{ border:1px solid rgba(16,24,40,.06); border-radius:16px; box-shadow:0 8px 28px rgba(16,24,40,.08); transition:transform .15s ease, box-shadow .15s ease; }
.card-elev:hover{ transform: translateY(-2px); box-shadow:0 16px 40px rgba(16,24,40,.12); }
.order-code{ letter-spacing:.04em; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono','Courier New', monospace; }
.badge-paid{ background:#e7f8ee; color:#117a36; border:1px solid #cdeed9; font-weight:700; }
.badge-pending{ background:#fff7e6; color:#a15c07; border:1px solid #ffe0a6; font-weight:700; }
.badge-failed{ background:#fde7e9; color:#b42318; border:1px solid #f2b8bc; font-weight:700; }
.btn-pill{ border-radius:9999px; padding-inline:1rem; font-weight:600; }
.btn-brand{ background:var(--brand); border-color:var(--brand); }
.btn-brand:hover{ filter:brightness(.92); }
.search-wrap{ border:1px solid #e5e7eb; border-radius:12px; padding:.5rem .75rem; background:#fff; }
.search-wrap:focus-within{ box-shadow:0 0 0 4px rgba(42,86,24,.15); border-color:#cdd9ce; }
</style>

<section class="section-head py-4">
  <div class="container d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width:40px;height:40px;background:#e9f3ea;color:var(--brand)">
        <i class="bi bi-bag"></i>
      </div>
      <div>
        <h1 class="h4 mb-0" style="letter-spacing:-.01em;">My Orders</h1>
        <small class="text-muted">Track your purchases and receipts</small>
      </div>
    </div>
    <form method="get" class="search-wrap d-flex align-items-center gap-2" style="min-width:260px">
      <i class="bi bi-search text-muted"></i>
      <input class="form-control border-0 p-0" name="page" value="<?= (int)$page ?>" type="hidden">
      <input name="q" class="form-control border-0 p-0" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Search by code...">
      <?php if(!empty($_GET['q'])): ?><a href="orders.php" class="text-muted small text-decoration-none">Clear</a><?php endif; ?>
    </form>
    <a href="../menu.php" class="btn btn-success btn-pill"><i class="bi bi-plus-circle me-1"></i> Order Again</a>
  </div>
</section>

<section class="py-4">
  <div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="h6 text-muted mb-0">Recent Orders</h2>
    </div>

    <?php if ($orders->num_rows > 0): ?>
    <div class="row g-3">
      <?php while ($order = $orders->fetch_assoc()): ?>
      <?php
        $items_count = $mysqli->query("SELECT COUNT(*) as c FROM order_items WHERE order_id={$order['id']}")->fetch_assoc()['c'];
        $ordStatus = $order['order_status'] ?? $order['status'] ?? 'pending';
        $ps = strtolower($order['payment_status'] ?? '');
        $pdisp = in_array($ps,['paid','successful']) ? 'PAID' : ($ps==='failed'?'FAILED':'PENDING');
        $pcls  = in_array($ps,['paid','successful']) ? 'badge-paid' : ($ps==='failed'?'badge-failed':'badge-pending');
      ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card card-elev h-100">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <div>
                <div class="small text-muted">Order Code</div>
                <div class="order-code fw-bold"><?= e($order['code'] ?? $order['order_number'] ?? $order['id']) ?></div>
              </div>
              <span class="badge <?= $pcls ?>"><?= $pdisp ?></span>
            </div>
            <div class="mb-2 small text-muted"><i class="bi bi-clock"></i> <?= date('M d, Y g:i A', strtotime($order['created_at'])) ?></div>
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="small text-muted">
                <i class="bi bi-credit-card-2-back me-1"></i><?= e(ucfirst($order['payment_method'])) ?>
              </div>
              <div class="h5 mb-0 text-success">₱<?= number_format($order['total_amount'], 2) ?></div>
            </div>
            <div class="small text-muted mb-3"><?= (int)$items_count ?> item<?= $items_count>1?'s':'' ?> • Points +<?= (int)($order['points_awarded'] ?? $order['points_earned'] ?? 0) ?></div>
            <div class="mt-auto d-flex gap-2">
<a class="btn btn-outline-secondary btn-sm btn-pill flex-grow-1" href="user_view_order.php?id=<?= (int)$order['id'] ?>"><i class="bi bi-eye"></i> View</a>
              <a class="btn btn-brand text-white btn-sm btn-pill flex-grow-1" href="../payment_success.php?order=<?= urlencode($order['code'] ?? '') ?>" target="_blank"><i class="bi bi-receipt"></i> Receipt</a>
            </div>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
<li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?><?= $q!=='' ? '&q='.urlencode($q) : '' ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
      </ul>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="card">
      <div class="card-body text-center py-5">
<i class="bi bi-bag fa-4x text-muted mb-3"></i>
        <h4>No Orders Yet</h4>
        <p class="text-muted">You haven't placed any orders yet. Start ordering to earn rewards!</p>
<a href="../menu.php" class="btn btn-lg btn-primary mt-3"><i class="bi bi-cart me-2"></i>Start Ordering</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetails">
                <div class="text-center py-4">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Robust modal open with Bootstrap fallback to avoid black screens
function openOrderModal(){
  const el = document.getElementById('orderModal');
  if (window.bootstrap && window.bootstrap.Modal) {
    return new window.bootstrap.Modal(el);
  }
  // Manual fallback
  el.classList.add('show');
  el.style.display = 'block';
  el.removeAttribute('aria-hidden');
  document.body.classList.add('modal-open');
  let bd = document.querySelector('.modal-backdrop');
  if(!bd){ bd = document.createElement('div'); bd.className = 'modal-backdrop fade show'; document.body.appendChild(bd); }
  return { show(){}, hide(){ el.classList.remove('show'); el.style.display='none'; el.setAttribute('aria-hidden','true'); document.body.classList.remove('modal-open'); const b=document.querySelector('.modal-backdrop'); if(b) b.remove(); } };
}

function peso(n){ n=parseFloat(n||0); return isNaN(n)?'0.00':n.toFixed(2); }

function viewOrder(orderId) {
    const modal = openOrderModal();
    // If Bootstrap modal object, explicitly show it
    try { if (modal && typeof modal.show === 'function') modal.show(); } catch(e) {}
    const body = document.getElementById('orderDetails');
    body.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    fetch('../api/order_details.php?id=' + encodeURIComponent(orderId))
        .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP '+r.status)))
        .then(data => {
            if (data && data.success) {
                const o = data.order;
                const subtotal = (parseFloat(o.total_amount||0) + parseFloat(o.discount_amount||0));
                const itemsHtml = (o.items||[]).map(it => `
                    <tr>
                      <td>${it.product_name}</td>
                      <td>${it.quantity}</td>
                      <td>₱${peso(it.price)}</td>
                      <td>₱${peso(it.subtotal)}</td>
                    </tr>`).join('');
                const html = `
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <strong>Order Code:</strong> <code>#${o.order_number||o.code||orderId}</code><br>
                      <strong>Date:</strong> ${o.date||o.created_at||''}<br>
                      <strong>Status:</strong> <span class="badge bg-${o.status_color||'secondary'}">${o.order_status||o.status||''}</span>
                    </div>
                    <div class="col-md-6 text-end">
                      <strong>Payment:</strong> ${o.payment_method||''} • <span class="badge bg-${o.payment_color||'warning'}">${o.payment_status||''}</span><br>
                      <strong>Points Earned:</strong> <span class="text-success">+${o.points_earned||0}</span>
                    </div>
                  </div>
                  <div class="row g-3 align-items-center mb-3">
                    <div class="col-md-6">
                      <div class="border rounded p-2 text-center">
                        <img src="../payment/qr_image.php?code=${encodeURIComponent(o.order_number||'')}" alt="QR" style="width:160px;height:160px">
                        <div class="small text-muted mt-1">Scan to verify</div>
                      </div>
                    </div>
                    <div class="col-md-6 text-end">
                      <a class="btn btn-outline-secondary btn-sm" href="user_view_order.php?id=${orderId}" target="_blank">Open full page</a>
                    </div>
                  </div>
                  <hr>
                  <h6 class="fw-bold mb-3">Order Items</h6>
                  <div class="table-responsive">
                  <table class="table">
                    <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                    <tbody>${itemsHtml}</tbody>
                  </table></div>
                  <div class="row mt-3">
                    <div class="col-md-6 offset-md-6">
                      <table class="table table-sm">
                        ${parseFloat(o.discount_amount||0) > 0 ? `
                        <tr><td>Subtotal:</td><td class="text-end">₱${peso(subtotal)}</td></tr>
                        <tr><td>Discount:</td><td class="text-end text-danger">-₱${peso(o.discount_amount)}</td></tr>` : ''}
                        <tr class="fw-bold"><td>Total:</td><td class="text-end">₱${peso(o.total_amount)}</td></tr>
                      </table>
                    </div>
                  </div>
                  ${o.notes ? `<hr><strong>Notes:</strong><p class="text-muted">${o.notes}</p>` : ''}
                `;
                body.innerHTML = html;
            } else {
                body.innerHTML = '<div class="alert alert-danger">Failed to load order details.</div>';
            }
        })
        .catch(() => {
            body.innerHTML = '<div class="alert alert-danger">Error loading order details. Please try again.</div>';
        });
}
</script>

<?php include __DIR__.'/../partials/footer.php'; ?>
