<?php
require __DIR__.'/../../admin/_guard_owner.php';
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inventory</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="p-3">
<div class="container">
  <h3 class="mb-4">Inventory</h3>
  <div class="row g-3">
    <div class="col-12 col-md-6">
      <div class="card p-3 h-100">
        <div class="fw-bold">Stock Overview</div>
        <div class="text-muted small">Monitor inventory levels and low-stock items.</div>
        <div class="mt-3">
          <a class="btn btn-primary disabled" tabindex="-1" aria-disabled="true">Overview (consolidated)</a>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card p-3 h-100">
        <div class="fw-bold">Products & Ingredients</div>
        <div class="text-muted small">Manage catalog and recipe components.</div>
        <div class="mt-3">
          <a class="btn btn-outline-secondary disabled" tabindex="-1" aria-disabled="true">Manage Items</a>
        </div>
      </div>
    </div>
  </div>
  <div class="alert alert-info mt-4">Inventory tools were reorganized during admin cleanup. Only core modules remain; detailed pages will be reintroduced within this section.</div>
  <a class="btn btn-secondary mt-3" href="<?= APP_URL ?>admin/dashboard.php">Back to Admin Dashboard</a>
</div>
</body></html>
