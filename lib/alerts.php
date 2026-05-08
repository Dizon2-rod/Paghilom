
<?php
function setting($mysqli, $k, $def=''){
  $rs = $mysqli->query("SELECT `value` FROM settings WHERE `key`='".$mysqli->real_escape_string($k)."' LIMIT 1");
  $r = $rs ? $rs->fetch_assoc() : null;
  return $r ? $r['value'] : $def;
}
function check_and_alert_low_stock($mysqli, $product_id){ // uses effective threshold: product > category > 5
  $pid = (int)$product_id;
  $p = $mysqli->query("SELECT p.id,p.name,p.stock_qty,p.low_stock_threshold,p.last_low_alert_at,c.low_stock_threshold cthr FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=$pid")->fetch_assoc();
  if(!$p) return;
  $thr = (isset($p['low_stock_threshold']) && $p['low_stock_threshold']!==null && $p['low_stock_threshold']!=='') ? (int)$p['low_stock_threshold'] : ((isset($p['cthr']) && $p['cthr']!==null && $p['cthr']!=='') ? (int)$p['cthr'] : 5);
  if((int)$p['stock_qty'] <= $thr){
    $last = strtotime($p['last_low_alert_at'] ?: '1970-01-01');
    if(time() - $last < 6*3600){ return; } // 6h cooldown
    $to = setting($mysqli, 'alert_email', 'admin@paghilom.local');
    $sub = "LOW STOCK: ".$p['name']." (".$p['stock_qty'].")";
    $msg = "Product: ".$p['name']."\nCurrent stock: ".$p['stock_qty']."\nThreshold: ".$p['low_stock_threshold']."\nTime: ".date('c');
    // basic headers; relies on PHP mail() being configured on server
    @mail($to, $sub, $msg, "From: no-reply@paghilom.local\r\n");
    $mysqli->query("UPDATE products SET last_low_alert_at=NOW() WHERE id=$pid");
  }
}
