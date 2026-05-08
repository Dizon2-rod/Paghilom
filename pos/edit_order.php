<?php require __DIR__.'/../config.php'; require_pos();
$id=(int)($_GET['id']??0);
$st=$mysqli->prepare('SELECT * FROM orders WHERE id=?'); $st->bind_param('i',$id); $st->execute(); $o=$st->get_result()->fetch_assoc(); $st->close();
if(!$o){ echo 'Order not found'; exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
  // We'll overwrite order_addons and re-create ingredient movements for addons
  $mysqli->begin_transaction();
  try{
    $del=$mysqli->prepare('DELETE FROM order_addons WHERE order_item_id IN (SELECT id FROM order_items WHERE order_id=?)'); $del->bind_param('i',$id); $del->execute(); $del->close();
    $del2=$mysqli->prepare('DELETE FROM ingredient_movements WHERE reason="order_addon" AND ref_id=?'); $del2->bind_param('i',$id); $del2->execute(); $del2->close();
    // parse posted addon quantities: addon_qty[order_item_id][addon_id]
    foreach(($_POST['addon_qty'] ?? []) as $oi=>$row){
      foreach($row as $aid=>$qty){
        $q=max(0,(int)$qty); if($q>0){
          $a=$mysqli->prepare('SELECT id,price,name FROM addons WHERE id=?'); $a->bind_param('i',$aid); $a->execute(); $ad=$a->get_result()->fetch_assoc(); $a->close();
          $ins=$mysqli->prepare('INSERT INTO order_addons(order_item_id,addon_id,qty,price_each) VALUES(?,?,?,?)'); $ins->bind_param('iiid',$oi,$aid,$q,$ad['price']); $ins->execute(); $ins->close();
          $map=['Espresso'=>1,'Syrup'=>3,'Sauce'=>4,'Milk'=>2];
          foreach($map as $n=>$ing){ if(stripos($ad['name'],$n)!==false){ $chg=-1*$q; $im=$mysqli->prepare('INSERT INTO ingredient_movements(ingredient_id,change_qty,reason,ref_id) VALUES(?,?,"order_addon",?)'); $im->bind_param('iii',$ing,$chg,$id); $im->execute(); $im->close(); break; } }
        }
      }
    }
    $mysqli->commit();
  } catch (Exception $e){ $mysqli->rollback(); }
  header('Location: edit_order.php?id='.$id); exit;
}
include __DIR__.'/../partials/header.php'; ?>
<section class="container py-4">
  <h1 class="h4">Edit Order #<?=$o['id']?></h1>
  <p>Status: <?=$o['status']?> • Payment: <?=$o['payment_status']?></p>
  <form method="post">
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>Item</th><th>Add-ons (set qty)</th></tr></thead>
        <tbody>
          <?php
          $it=$mysqli->prepare('SELECT oi.*, p.name FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE order_id=?');
          $it->bind_param('i',$id); $it->execute(); $items=$it->get_result();
          while($row=$items->fetch_assoc()):
            $allowed=$mysqli->prepare('SELECT a.* FROM product_addons pa JOIN addons a ON a.id=pa.addon_id WHERE pa.product_id=? ORDER BY a.name');
            $allowed->bind_param('i',$row['product_id']); $allowed->execute(); $allow=$allowed->get_result();
            // existing
            $existing=[]; $ex=$mysqli->prepare('SELECT addon_id, qty FROM order_addons WHERE order_item_id=?'); $ex->bind_param('i',$row['id']); $ex->execute(); $exr=$ex->get_result(); while($e=$exr->fetch_assoc()) $existing[$e['addon_id']]=$e['qty']; $ex->close();
          ?>
          <tr>
            <td><strong><?=$row['qty']?> x <?=htmlspecialchars($row['name'])?></strong><div class="small text-muted">₱<?=number_format(($row['subtotal'] ?? ($row['price']*$row['qty'])),2)?></div></td>
            <td>
              <div class="row g-2">
                <?php while($ad=$allow->fetch_assoc()): $val=$existing[$ad['id']] ?? 0; ?>
                  <div class="col-6 col-md-4">
                    <label class="form-label small"><?=$ad['name']?> (+₱<?=number_format($ad['price'],2)?>)</label>
                    <input class="form-control form-control-sm" type="number" min="0" value="<?=$val?>" name="addon_qty[<?=$row['id']?>][<?=$ad['id']?>]">
                  </div>
                <?php endwhile; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <button class="btn btn-success">Save Add-ons</button>
    <a class="btn btn-outline-secondary" href="index.php">Back</a>
  </form>
</section>
<?php include __DIR__.'/../partials/footer.php'; ?>