<?php
require_once __DIR__ . '/../config.php';
require_pos();

// POS payment helpers (replacing old admin/kiosk db_bootstrap)
if (!function_exists('kiosk_db_connect')) {
    function kiosk_db_connect() {
        // Reuse main app mysqli connection
        global $mysqli;
        return $mysqli;
    }
}

if (!function_exists('kiosk_safe_param')) {
    function kiosk_safe_param(string $key, string $method = 'GET', $default = null) {
        $src = (strtoupper($method) === 'POST') ? $_POST : $_GET;
        if (!isset($src[$key])) {
            return $default;
        }
        $val = $src[$key];
        return is_string($val) ? trim($val) : $val;
    }
}

if (!function_exists('table_exists')) {
    function table_exists(mysqli $conn, string $table): bool {
        // Use simple SHOW TABLES query to avoid driver-specific get_result issues
        $tableEsc = $conn->real_escape_string($table);
        $sql = "SHOW TABLES LIKE '$tableEsc'";
        $res = $conn->query($sql);
        if (!$res) {
            return false;
        }
        $exists = $res->num_rows > 0;
        $res->free();
        return $exists;
    }
}

if (!function_exists('first_existing_column')) {
    function first_existing_column(mysqli $conn, string $table, array $candidates) {
        foreach ($candidates as $col) {
            $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            if (!$stmt) continue;
            $stmt->bind_param('s', $col);
            $stmt->execute();
            $res = $stmt->get_result();
            $exists = $res && $res->num_rows > 0;
            $stmt->close();
            if ($exists) {
                return $col;
            }
        }
        return null;
    }
}

if (!function_exists('column_exists')) {
    function column_exists(mysqli $conn, string $table, string $column): bool {
        $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        if (!$stmt) return false;
        $stmt->bind_param('s', $column);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = $res && $res->num_rows > 0;
        $stmt->close();
        return $exists;
    }
}

$conn = kiosk_db_connect();

$mode = kiosk_safe_param('mode', 'GET');
$id = kiosk_safe_param('id', 'GET');
$code = kiosk_safe_param('code', 'GET'); // Get code parameter if provided

if (!$mode || !$id || !in_array($mode, ['order','reward'], true)) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

// Determine table name - check multiple possible table names for rewards/vouchers
if ($mode === 'order') {
    $table = 'orders';
    $idCol = 'order_id';
} else {
    // For rewards/vouchers, prioritize redemptions table (where records are created when users redeem points)
    $possibleTables = ['redemptions', 'vouchers', 'reward_redemptions'];
    $table = null;
    foreach ($possibleTables as $tbl) {
        if (table_exists($conn, $tbl)) {
            $table = $tbl;
            break;
        }
    }
    if (!$table) {
        echo '<p>Required table not found. Please ensure one of these tables exists: redemptions, vouchers, or reward_redemptions</p>';
        exit;
    }
    $idCol = 'reward_id';
}
$idCol = first_existing_column($conn, $table, ['id', $idCol]) ?: 'id';
$statusCol = first_existing_column($conn, $table, ['status','state']);

// Try to find record by ID first
$stmt = $conn->prepare("SELECT * FROM `{$table}` WHERE `{$idCol}` = ? LIMIT 1");
$stmt->bind_param('s', $id);
$stmt->execute();
$res = $stmt->get_result();
$record = $res ? $res->fetch_assoc() : null;
$stmt->close();

// If not found and it's an order, try looking up by code (for QR code scans)
if (!$record && $mode === 'order') {
    $lookupCode = $code ?: $id;
    $codeCol = first_existing_column($conn, 'orders', ['code', 'qr_code', 'order_code']);
    if ($codeCol) {
        $stmt = $conn->prepare("SELECT * FROM `orders` WHERE `{$codeCol}` = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $lookupCode);
            $stmt->execute();
            $res = $stmt->get_result();
            $record = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($record) {
                $table = 'orders';
                $idCol = 'id';
            }
        }
    }
}

// If not found and it's a reward, try looking up by voucher_code (prioritize redemptions table)
if (!$record && $mode === 'reward') {
    $lookupCode = $code ?: $id;
    
    // First, try redemptions table (where records are created when users redeem points)
    if (table_exists($conn, 'redemptions')) {
        $voucherCodeCol = first_existing_column($conn, 'redemptions', ['voucher_code', 'code']);
        if ($voucherCodeCol) {
            // Try by code first
            $stmt = $conn->prepare("SELECT * FROM `redemptions` WHERE `{$voucherCodeCol}` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $lookupCode);
                $stmt->execute();
                $res = $stmt->get_result();
                $record = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                if ($record) {
                    $table = 'redemptions';
                    $idCol = first_existing_column($conn, $table, ['id']) ?: 'id';
                }
            }
            
            // If code lookup failed, try by ID (in case ID was passed as numeric)
            if (!$record && is_numeric($id)) {
                $stmt = $conn->prepare("SELECT * FROM `redemptions` WHERE `id` = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('i', (int)$id);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $record = $res ? $res->fetch_assoc() : null;
                    $stmt->close();
                    if ($record) {
                        $table = 'redemptions';
                        $idCol = 'id';
                    }
                }
            }
        }
    }
    
    // If still not found in redemptions, try vouchers table as fallback
    if (!$record && table_exists($conn, 'vouchers')) {
        $voucherCodeCol = first_existing_column($conn, 'vouchers', ['voucher_code', 'code']);
        if ($voucherCodeCol) {
            $stmt = $conn->prepare("SELECT * FROM `vouchers` WHERE `{$voucherCodeCol}` = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $lookupCode);
                $stmt->execute();
                $res = $stmt->get_result();
                $record = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                if ($record) {
                    $table = 'vouchers';
                    $idCol = first_existing_column($conn, $table, ['id']) ?: 'id';
                }
            }
        }
    }
}

if (!$record) {
    echo '<p>Record not found. Table: ' . htmlspecialchars($table) . ', ID/Code: ' . htmlspecialchars($id) . ', ID Column: ' . htmlspecialchars($idCol) . '</p>';
    exit;
}

// Compute payable amount and description
$total = 0.0;
$items = [];
$desc = $mode === 'order' ? 'Order Payment' : 'Reward Redemption';

if ($mode === 'order') {
    if (isset($record['total_amount'])) $total = (float)$record['total_amount'];
    elseif (isset($record['amount'])) $total = (float)$record['amount'];
    if (table_exists($conn, 'order_items')) {
        $orderItemOrderIdCol = first_existing_column($conn, 'order_items', ['order_id','orderId','order']);
        if ($orderItemOrderIdCol) {
            $stmt = $conn->prepare("SELECT * FROM `order_items` WHERE `{$orderItemOrderIdCol}` = ?");
            $stmt->bind_param('s', $id);
            $stmt->execute();
            $ri = $stmt->get_result();
            while ($ri && ($row = $ri->fetch_assoc())) $items[] = $row;
            $stmt->close();
            // Load add-ons/options if available
            if (table_exists($conn, 'order_item_options')) {
                $itemIdCol = first_existing_column($conn, 'order_items', ['id','order_item_id']);
                if ($itemIdCol && !empty($items)) {
                    $ids = array_values(array_filter(array_map(function($r) use($itemIdCol){ return (int)($r[$itemIdCol] ?? 0); }, $items)));
                    if ($ids) {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $types = str_repeat('i', count($ids));
                        // Detect column names dynamically
                        $optItemCol = first_existing_column($conn, 'order_item_options', ['order_item_id','item_id','oi_id']);
                        $optNameCol = first_existing_column($conn, 'order_item_options', ['addon_name','name','label','option_name','title','description','option']);
                        $optPriceCol= first_existing_column($conn, 'order_item_options', ['price','amount','add_price','addon_price','value']);
                        if ($optItemCol && $optNameCol) {
                            $select = "`$optItemCol` AS order_item_id, `$optNameCol` AS name" . ($optPriceCol? ", `$optPriceCol` AS price" : ", NULL AS price");
                            $sql = "SELECT $select FROM order_item_options WHERE `$optItemCol` IN ($placeholders)";
                            $opt = $conn->prepare($sql);
                            $opt->bind_param($types, ...$ids);
                            $opt->execute();
                            $opts = $opt->get_result();
                            $optionsMap = [];
                            while ($opts && ($o = $opts->fetch_assoc())) { $optionsMap[(int)$o['order_item_id']][] = $o; }
                            $opt->close();
                        } else { $optionsMap = []; }
                        // Attach to items for rendering
                        foreach ($items as &$it) {
                            $oid = (int)($it[$itemIdCol] ?? 0);
                            if ($oid && isset($optionsMap[$oid])) { $it['__options'] = $optionsMap[$oid]; }
                        }
                        unset($it);
                    }
                }
            }
        }
    }
}

if ($mode === 'reward') {
    if (isset($record['amount_due'])) $total = (float)$record['amount_due'];
    elseif (isset($record['price_due'])) $total = (float)$record['price_due'];
    else $total = 0.0; // Free rewards redeemed with points have no amount due
    if (isset($record['reward_name'])) $desc = 'Redeem: ' . $record['reward_name'];
    
    // Check if reward is free (0 points) by checking points_spent, points_cost, or points_required
    $pointsSpent = (int)($record['points_spent'] ?? $record['points_cost'] ?? $record['points_required'] ?? 0);
    $isFreeReward = ($pointsSpent === 0);
    
    // If reward is free (total = 0 OR points = 0), automatically process it and redirect to receipt
    if (($total <= 0 || $isFreeReward) && !isset($_POST['pay'])) {
        $conn->begin_transaction();
        try {
            // Update redemptions table status to 'claimed'
            if ($table === 'redemptions' || table_exists($conn, 'redemptions')) {
                $updateTable = ($table === 'redemptions') ? $table : 'redemptions';
                $updateIdCol = ($table === 'redemptions') ? $idCol : 'id';
                $updateId = ($table === 'redemptions') ? $id : $record['id'];
                
                $statusCol = first_existing_column($conn, $updateTable, ['status']);
                if ($statusCol) {
                    // Update status from "pending" to "approved" when staff processes the voucher
                    $stmt = $conn->prepare("UPDATE `{$updateTable}` SET `{$statusCol}` = 'approved' WHERE `{$updateIdCol}` = ?");
                    if ($stmt) {
                        if (is_numeric($updateId)) {
                            $stmt->bind_param('i', (int)$updateId);
                        } else {
                            $stmt->bind_param('s', $updateId);
                        }
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                
                // Update claimed_at
                if (column_exists($conn, $updateTable, 'claimed_at')) {
                    $stmt = $conn->prepare("UPDATE `{$updateTable}` SET `claimed_at` = NOW() WHERE `{$updateIdCol}` = ?");
                    if ($stmt) {
                        if (is_numeric($updateId)) {
                            $stmt->bind_param('i', (int)$updateId);
                        } else {
                            $stmt->bind_param('s', $updateId);
                        }
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
            
            // Also update vouchers table if it exists
            if (table_exists($conn, 'vouchers')) {
                $voucherCodeCol = first_existing_column($conn, 'vouchers', ['voucher_code', 'code']);
                $voucherCode = $record['voucher_code'] ?? $code ?? $id;
                if ($voucherCodeCol && $voucherCode) {
                    $statusCol = first_existing_column($conn, 'vouchers', ['status']);
                    if ($statusCol) {
                        // Update vouchers table status to "approved" when staff processes it
                        $stmt = $conn->prepare("UPDATE `vouchers` SET `{$statusCol}` = 'approved', `claimed_at` = NOW() WHERE `{$voucherCodeCol}` = ?");
                        if ($stmt) {
                            $stmt->bind_param('s', $voucherCode);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
            
            $conn->commit();
            
            // Redirect to success page for free rewards (already paid with points)
            $voucherCode = $record['voucher_code'] ?? $code ?? $id;
            header('Location: ' . APP_URL . 'pos/success.php?mode=reward&id=' . urlencode($updateId ?? $id) . '&code=' . urlencode($voucherCode) . '&method=points&paid=0&change=0');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Error auto-processing free reward: " . $e->getMessage());
        }
    }
}

$payError = null;
$method = null;
$amountPaid = null;
$changeDue = 0.0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    $method = isset($_POST['method']) ? trim((string)$_POST['method']) : '';
    $amountPaid = isset($_POST['amount_paid']) ? (float)str_replace([',',' '],['',''], (string)$_POST['amount_paid']) : null;
    if ($method === '') $payError = 'Choose a payment method.';

    // Basic validation for cash payments
    if (!$payError) {
        if ($method === 'cash') {
            if ($amountPaid === null || $amountPaid <= 0) {
                $payError = 'Enter amount tendered.';
            } elseif ($amountPaid + 1e-9 < (float)$total) {
                $payError = 'Insufficient cash. Amount is less than total.';
            } else {
                $changeDue = max(0, round($amountPaid - (float)$total, 2));
            }
        } else {
            // Non-cash, assume exact amount
            $amountPaid = (float)$total;
            $changeDue = 0.0;
        }
    }

    if (!$payError) {
        $conn->begin_transaction();
        try {
            if ($mode === 'reward') {
                // Update redemptions table (where records are created when users redeem points)
                if ($table === 'redemptions' || table_exists($conn, 'redemptions')) {
                    // Update status to 'claimed' or 'redeemed'
                    $updateTable = ($table === 'redemptions') ? $table : 'redemptions';
                    $updateIdCol = ($table === 'redemptions') ? $idCol : 'id';
                    $updateId = ($table === 'redemptions') ? $id : $record['id'];
                    
                    // Update status from "pending" to "approved" when staff processes the voucher
                    $statusCol = first_existing_column($conn, $updateTable, ['status']);
                    if ($statusCol) {
                        $stmt = $conn->prepare("UPDATE `{$updateTable}` SET `{$statusCol}` = 'approved' WHERE `{$updateIdCol}` = ?");
                        if ($stmt) {
                            if (is_numeric($updateId)) {
                                $stmt->bind_param('i', (int)$updateId);
                            } else {
                                $stmt->bind_param('s', $updateId);
                            }
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                    
                    // Update claimed_at if column exists
                    if (column_exists($conn, $updateTable, 'claimed_at')) {
                        $stmt = $conn->prepare("UPDATE `{$updateTable}` SET `claimed_at` = NOW() WHERE `{$updateIdCol}` = ?");
                        if ($stmt) {
                            if (is_numeric($updateId)) {
                                $stmt->bind_param('i', (int)$updateId);
                            } else {
                                $stmt->bind_param('s', $updateId);
                            }
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
                
                // Also update vouchers table if it exists (for compatibility)
                if (table_exists($conn, 'vouchers') && $table !== 'redemptions') {
                    $statusCol = first_existing_column($conn, 'vouchers', ['status']);
                    if ($statusCol) {
                        $voucherCodeCol = first_existing_column($conn, 'vouchers', ['voucher_code', 'code']);
                        $voucherCode = $record['voucher_code'] ?? $code ?? $id;
                        if ($voucherCodeCol && $voucherCode) {
                            // Update vouchers table status to "approved" when staff processes it
                            $stmt = $conn->prepare("UPDATE `vouchers` SET `{$statusCol}` = 'approved', `claimed_at` = NOW() WHERE `{$voucherCodeCol}` = ?");
                            if ($stmt) {
                                $stmt->bind_param('s', $voucherCode);
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                }
            } else if ($mode === 'order') {
                // For orders: set status paid and payment_status paid
                // Use numeric ID for orders table
                $orderId = is_numeric($id) ? (int)$id : $id;
                $updateStmt = $conn->prepare("UPDATE `orders` SET `status`='paid', `payment_status`='paid', `payment_method`=?, `paid_at`=NOW() WHERE `id` = ?");
                if ($updateStmt) {
                    if (is_numeric($orderId)) {
                        $updateStmt->bind_param('si', $method, $orderId);
                    } else {
                        $updateStmt->bind_param('ss', $method, $orderId);
                    }
                    $updateStmt->execute();
                    $affected = $updateStmt->affected_rows;
                    $updateStmt->close();
                    
                    // Log if update didn't affect any rows (debugging)
                    if ($affected === 0) {
                        error_log("Payment Update Warning: No rows updated for order ID: $orderId (original id param: $id)");
                    }
                }
                // Stock deduction (finished goods)
                if (!empty($items) && table_exists($conn, 'products')) {
                    $productIdCol = first_existing_column($conn, 'order_items', ['product_id','item_id']);
                    $qtyCol = first_existing_column($conn, 'order_items', ['quantity','qty']);
                    $stockCol = first_existing_column($conn, 'products', ['stock','quantity']);
                    if ($productIdCol && $qtyCol && $stockCol) {
                        foreach ($items as $it) {
                            $pid = $it[$productIdCol];
                            $qty = (int)$it[$qtyCol];
                            $conn->query("UPDATE `products` SET `{$stockCol}` = `{$stockCol}` - " . (int)$qty . " WHERE `id` = " . intval($pid));
                        }
                    }
                }
                // Ingredients deduction based on recipes (BOM)
                if (!empty($items) && table_exists($conn,'product_recipes') && table_exists($conn,'ingredients')) {
                    $productIdCol = first_existing_column($conn, 'order_items', ['product_id','item_id']);
                    $qtyCol = first_existing_column($conn, 'order_items', ['quantity','qty']);
                    if ($productIdCol && $qtyCol) {
                        $deduct = [];
                        foreach ($items as $it){
                            $pid = (int)$it[$productIdCol]; $oqty = (float)$it[$qtyCol];
                            if ($pid>0 && $oqty>0){
                                $rs = $conn->query("SELECT ingredient_id, qty FROM product_recipes WHERE product_id=".$pid);
                                while($rs && ($r=$rs->fetch_assoc())){
                                    $iid=(int)$r['ingredient_id']; $need=(float)$r['qty']*$oqty; $deduct[$iid]=($deduct[$iid]??0)+$need;
                                }
                            }
                        }
                        foreach($deduct as $iid=>$need){
                            $conn->query("UPDATE ingredients SET quantity = quantity - ".((float)$need)." WHERE id=".(int)$iid);
                        }
                    }
                }
                // Award loyalty points to user if not yet awarded
                // Use the already-loaded record data directly instead of querying again
                if ($mode === 'order' && $record) {
                    // Get order details from the already-loaded record
                    $orderIdNum = (int)($record['id'] ?? 0);
                    $uid = (int)($record['user_id'] ?? 0); 
                    $tamt = (float)($record['total_amount'] ?? $record['amount'] ?? 0);
                    
                    if ($uid > 0 && $tamt > 0 && $orderIdNum > 0 && table_exists($conn, 'point_transactions')) {
                        // ₱10 = 5 points (₱2 = 1 point)
                        $pts = (int)floor($tamt / 2);
                        if ($pts > 0) {
                            // Check if points already awarded using numeric order ID
                            $chk = $conn->prepare("SELECT id FROM point_transactions WHERE user_id=? AND ref_type='order' AND ref_id=? LIMIT 1");
                            $chk->bind_param('ii', $uid, $orderIdNum);
                            $chk->execute();
                            $exists = $chk->get_result()->fetch_assoc();
                            $chk->close();
                            
                            if (!$exists) {
                                // Award points using numeric order ID
                                $ins = $conn->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, 'earn', 'order', ?, 'Points earned from order payment', NOW())");
                                $ins->bind_param('iii', $uid, $pts, $orderIdNum);
                                
                                if ($ins->execute()) {
                                    $ins->close();
                                    
                                    // Update orders.points_awarded if column exists
                                    if (column_exists($conn, 'orders', 'points_awarded')) {
                                        $up = $conn->prepare("UPDATE orders SET points_awarded = COALESCE(points_awarded, 0) + ? WHERE id = ?");
                                        $up->bind_param('ii', $pts, $orderIdNum);
                                        $up->execute();
                                        $up->close();
                                    }
                                    
                                    // Log successful points award for debugging
                                    error_log("Points awarded: Order #$orderIdNum, User #$uid, Points: $pts");
                                } else {
                                    // Log error but don't fail the payment
                                    error_log("Failed to insert points transaction for order #$orderIdNum, user #$uid: " . $conn->error);
                                    $ins->close();
                                }
                            } else {
                                error_log("Points already awarded for order #$orderIdNum, user #$uid");
                            }
                        }
                    } else {
                        // Log why points weren't awarded for debugging
                        if ($uid <= 0) error_log("No user_id for order #$orderIdNum - points not awarded");
                        if ($tamt <= 0) error_log("No total_amount for order #$orderIdNum - points not awarded");
                        if ($orderIdNum <= 0) error_log("No order ID found - points not awarded");
                    }
                }
            }

            if (table_exists($conn, 'payments')) {
                // Try to insert amount_tendered if column exists
                if (column_exists($conn, 'payments', 'amount_tendered')) {
                    $stmt = $conn->prepare("INSERT INTO `payments` (ref_type, ref_id, method, amount, amount_tendered) VALUES (?, ?, ?, ?, ?)");
                    $refType = $mode; $amt = (float)$total; $tendered = (float)$amountPaid;
                    $stmt->bind_param('sssdd', $refType, $id, $method, $amt, $tendered);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare("INSERT INTO `payments` (ref_type, ref_id, method, amount) VALUES (?, ?, ?, ?)");
                    $refType = $mode; $amt = (float)$total;
                    $stmt->bind_param('sssd', $refType, $id, $method, $amt);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conn->commit();
            // Redirect to receipt page after successful payment
            if ($mode === 'order') {
                // Pass change amount via URL if cash payment with change
                $redirectUrl = 'receipt.php?id=' . urlencode($id);
                if ($method === 'cash' && $changeDue > 0) {
                    $redirectUrl .= '&change=' . urlencode($changeDue);
                    $redirectUrl .= '&tendered=' . urlencode($amountPaid);
                }
                header('Location: ' . $redirectUrl);
            } else {
                // For rewards, redirect back to POS dashboard
                header('Location: index.php');
            }
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $payError = 'Payment processing failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>POS | Payment</title>
  <link rel="icon" type="image/png" href="../assets/images/logo.png">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root { --bg: var(--background); --line: var(--gray-200); --muted: var(--gray-500); }
    body { background: var(--bg); color:#000; }

    /* Hero header aligned with index */
    .pos-hero { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); color: #fff; padding: 2rem 0; margin-bottom: 1.25rem; box-shadow: 0 4px 20px rgba(42, 86, 24, 0.15); }
    .pos-hero .title { font-weight:700; letter-spacing:.2px; }
    .pos-hero .muted { opacity:.9; }

    main.container { max-width: 1100px; padding: 18px; margin: 0 auto; }

    /* Steps (reserved if needed) */
    .steps { display:flex; gap:10px; align-items:center; margin: 12px 0 18px; }
    .step { flex:1; display:flex; align-items:center; gap:10px; padding:10px 12px; border:1px solid var(--line); border-radius:10px; background:#fff; color:#0f172a; font-weight:600; }
    .step .num { width:28px; height:28px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; border:2px solid var(--line); font-size:.9rem; }
    .step.active { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(42,86,24,.08); }
    .step.active .num { border-color: var(--primary); color: var(--primary); }

    /* Layout */
    .layout { display:grid; grid-template-columns: 2fr 1.25fr; gap:18px; }

    /* Items */
    .items { list-style:none; margin:0; padding:0; max-height:52vh; overflow:auto; }
    .item { display:flex; justify-content:space-between; align-items:flex-start; padding:12px 0; border-bottom:1px dashed #eef2f7; }
    .item:last-child { border-bottom:none; }
    .item .name { font-weight:600; }
    .item .meta { color: var(--muted); font-size:.9rem; margin-top:2px; }

    /* Totals */
    .totals { margin-top: 12px; }
    .totals .row { display:flex; justify-content:space-between; margin:6px 0; }
    .totals .grand { font-weight:800; font-size:1.15rem; }

    /* Methods */
    .methods { display:grid; grid-template-columns: 1fr; gap:12px; }
    @media (min-width: 520px){ .methods { grid-template-columns: 1fr 1fr 1fr; } }
    .method { position:relative; }
    .method input { position:absolute; inset:0; opacity:0; }
    .method label { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:18px 12px; border:2px solid var(--line); border-radius:12px; cursor:pointer; transition:.15s ease; height:100%; text-align:center; background:#fff; }
    .method label:hover { border-color:#cbd5e1; background:#f8fafc; }
    .method input:checked + label, .method label.selected { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(42,86,24,.10); }
    .method .icon { font-size:28px; }
    .method small { color: var(--muted); }

    /* Actions */
    .actions { display:flex; gap:10px; margin-top:16px; }

    .muted { color: var(--muted); }
    .ref { font-size:.9rem; color: var(--muted); }

    /* Mobile */
    @media (max-width: 900px){ .layout { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <!-- Hero header aligned with index -->
  <div class="pos-hero">
    <div class="container d-flex align-items-center justify-content-between">
      <div>
        <div class="h3 title mb-1"><i class="bi bi-wallet2 me-2"></i>Payment</div>
        <div class="muted">Finalize and confirm payment</div>
      </div>
      <div class="text-end">
        <div class="small" id="currentDate"><?= date('l, F j, Y') ?></div>
        <div class="small"><i class="bi bi-clock me-1"></i><span id="currentTime"><?= date('g:i A') ?></span></div>
      </div>
    </div>
  </div>
  <main class="container">
    <div class="layout">
      <section class="card">
        <div class="card-header">Order Summary</div>
        <div class="card-body">
          <div class="ref">Reference: <?php echo htmlspecialchars($mode . '#' . $id); ?></div>
          <?php if (!empty($items)): ?>
            <ul class="items">
              <?php foreach ($items as $it): ?>
                <?php $qty = (int)($it['quantity'] ?? $it['qty'] ?? 1);
                      $price = (float)($it['price'] ?? 0);
                      $name = $it['name'] ?? ('Item ' . ($it['product_id'] ?? $it['item_id'] ?? ''));
                      $sub = $qty * $price; ?>
                <li class="item">
                  <div>
                    <div class="name"><?php echo htmlspecialchars($name); ?></div>
                    <div class="meta">x<?php echo htmlspecialchars((string)$qty); ?> @ ₱<?php echo number_format($price,2); ?></div>
                    <?php $opts = $it['__options'] ?? []; if ($opts): ?>
                      <ul class="mt-1" style="margin:6px 0 0 14px; padding:0; list-style:disc; color:#475569; font-size:.9rem;">
                        <?php foreach ($opts as $op): ?>
                          <li><?php echo htmlspecialchars($op['name'] ?? ''); ?><?php if(isset($op['price'])): ?> (+₱<?php echo number_format((float)$op['price'],2); ?>)<?php endif; ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                  <div><strong>₱<?php echo number_format($sub,2); ?></strong></div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p class="muted">No item breakdown available.</p>
          <?php endif; ?>
          <div class="totals">
            <div class="row"><span>Subtotal</span><span>₱<?php echo number_format((float)$total, 2); ?></span></div>
            <div class="row grand"><span>Total</span><span>₱<?php echo number_format((float)$total, 2); ?></span></div>
          </div>
        </div>
      </section>

      <section class="card">
        <div class="card-header">Payment</div>
        <div class="card-body">
          <p class="text-muted" style="margin-top:-4px;">Choose a method to complete this <?php echo htmlspecialchars(strtolower($desc)); ?>.</p>
          <?php if ($payError): ?>
            <p class="text-danger" style="margin-top:0;"><?php echo htmlspecialchars($payError); ?></p>
          <?php endif; ?>
          <form method="post" id="payForm">
            <div class="methods">
              <div class="method">
                <input id="m-cash" type="radio" name="method" value="cash">
                <label for="m-cash"><div class="icon">💵</div><div>Cash</div></label>
              </div>
              <div class="method">
                <input id="m-gcash" type="radio" name="method" value="gcash">
                <label for="m-gcash"><div class="icon">📱</div><div>GCash / e‑Wallet</div></label>
              </div>
              <div class="method">
                <input id="m-card" type="radio" name="method" value="card">
                <label for="m-card"><div class="icon">💳</div><div>Card</div></label>
              </div>
            </div>

            <!-- Amount Tendered -->
            <div id="cashFields" style="display:none; margin-top:12px;">
              <label class="form-label">Amount Tendered</label>
              <input class="form-control" type="number" name="amount_paid" id="amountPaid" step="0.01" min="0" placeholder="Enter amount tendered" style="width: 240px;">
              <div class="text-muted" id="changeText" style="margin-top:6px;">Change: ₱0.00</div>
            </div>

            <div class="actions">
              <a href="scan_qr.php" class="btn btn-outline-secondary">Cancel</a>
              <button class="btn btn-primary" id="payBtn" type="submit" name="pay" value="1">Pay ₱<?php echo number_format((float)$total,2); ?></button>
            </div>
          </form>
        </div>
      </section>

      <script>
        (function(){
          const total = <?php echo json_encode((float)$total); ?>;
          const cashFields = document.getElementById('cashFields');
          const amountPaid = document.getElementById('amountPaid');
          const changeText = document.getElementById('changeText');
          const payBtn = document.getElementById('payBtn');
          const radios = document.querySelectorAll('input[name="method"]');

          function format(n){ return '₱' + (Number(n||0)).toFixed(2); }
          function update(){
            const method = document.querySelector('input[name="method"]:checked')?.value;
            if(method === 'cash'){
              cashFields.style.display = '';
              const val = parseFloat(amountPaid.value||'0');
              const change = Math.max(0, (val - total));
              changeText.textContent = 'Change: ' + format(change);
              if (isNaN(val) || val < total){
                payBtn.disabled = true;
              } else {
                payBtn.disabled = false;
              }
            } else {
              cashFields.style.display = 'none';
              payBtn.disabled = !method;
            }
          }

          radios.forEach(r=>r.addEventListener('change', update));
          amountPaid.addEventListener('input', update);
          update();
        })();
      </script>
    </div>
  </main>
  <script>
    function updateClock(){
      const now = new Date();
      const dateStr = now.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
      const timeStr = now.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit', hour12:true });
      const d=document.getElementById('currentDate'), t=document.getElementById('currentTime');
      if(d) d.textContent=dateStr; if(t) t.textContent=timeStr;
    }
    setInterval(updateClock, 1000); window.addEventListener('load', updateClock);
  </script>
</body>
</html>
