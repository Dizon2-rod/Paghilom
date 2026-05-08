<?php include __DIR__.'/../includes/header.php';
$db=db();

// Filters
$search = safe('q');
$cat = (int)(safe('cat') ?: 0);

// Fetch categories
$cats = [];
if ($db) { $r=$db->query("SELECT id,name FROM categories WHERE is_active=1 ORDER BY sort_order,name"); while($r && ($row=$r->fetch_assoc())) $cats[]=$row; }

// List products
$rows=[];
if ($db){
  $where = '1=1';
  if ($cat>0) $where .= ' AND p.category_id='.(int)$cat;
  if ($search) { $s=$db->real_escape_string($search); $where .= " AND p.name LIKE '%$s%'"; }
  $sql = "SELECT p.id,p.name,p.price,p.is_active,p.is_featured,c.name c_name FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE $where ORDER BY p.sort_order,p.name LIMIT 1000";
  $res=$db->query($sql);
  while($res && ($r=$res->fetch_assoc())) $rows[]=$r;
}
?>
<div class="topbar"><div class="title">Products</div><a class="btn primary" href="add_product.php"><i class="bi bi-plus-circle"></i> Add Product</a></div>
<div class="card"><div class="card-body">
  <form method="get" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin-bottom:12px;">
    <div><label class="label">Search</label><input class="input" name="q" value="<?= e($search) ?>" placeholder="Search products..."></div>
    <div><label class="label">Category</label><select class="input" name="cat"><option value="0">All</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div><button class="btn" type="submit"><i class="bi bi-filter"></i> Filter</button></div>
  </form>
  <div class="table-responsive-sm">
    <table class="table">
      <thead><tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Status</th><th>Featured</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= e($r['name']) ?></td>
            <td><?= e($r['c_name'] ?: '—') ?></td>
            <td><?= money($r['price']) ?></td>
            <td><?= ((int)$r['is_active'])? 'Available' : 'Not Available' ?></td>
            <td><?= ((int)$r['is_featured'] ?? 0) ? 'Yes' : 'No' ?></td>
            <td>
              <a class="btn" href="edit_product.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
              <a class="btn danger" href="delete_product.php?id=<?= (int)$r['id'] ?>" onclick="return confirm('Delete this product? This will not affect past orders.');"><i class="bi bi-trash"></i> Delete</a>
              <a class="btn" href="view_recipe.php?id=<?= (int)$r['id'] ?>"><i class="bi bi-diagram-3"></i> Recipe</a>
            </td>
          </tr>
        <?php endforeach; if(!$rows): ?>
          <tr><td colspan="6" class="small">No products found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div></div>
<?php include __DIR__.'/../includes/footer.php'; ?>


