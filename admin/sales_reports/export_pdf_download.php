<?php
// Generate a real PDF file for Sales & Reports and force download
require_once __DIR__ . '/../includes/bootstrap.php';

$db   = db();
$type = safe('type') ?: 'daily';
$from = safe('from') ?: date('Y-m-01');
$to   = safe('to')   ?: date('Y-m-d');

if (strtotime($from) === false) { $from = date('Y-m-01'); }
if (strtotime($to) === false)   { $to   = date('Y-m-d'); }

$title       = 'Sales Report';
$orientation = 'portrait'; // some tables switch to landscape
$data        = [];

if ($db) {
    switch ($type) {
        case 'daily':
            $title = 'Daily Sales Report';
            $sql = "SELECT DATE(created_at) d, SUM(total_amount) total
                    FROM orders
                    WHERE payment_status='paid'
                      AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY DATE(created_at)
                    ORDER BY d DESC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) { $data[] = $r; }
            $stmt->close();
            break;

        case 'weekly':
            $title = 'Weekly Sales Report';
            $sql = "SELECT YEAR(created_at) y, WEEK(created_at, 1) w, SUM(total_amount) total
                    FROM orders
                    WHERE payment_status='paid'
                      AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY YEAR(created_at), WEEK(created_at,1)
                    ORDER BY y DESC, w DESC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) { $data[] = $r; }
            $stmt->close();
            break;

        case 'monthly':
            $title = 'Monthly Sales Report';
            $sql = "SELECT YEAR(created_at) y, MONTH(created_at) m, SUM(total_amount) total
                    FROM orders
                    WHERE payment_status='paid'
                      AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY YEAR(created_at), MONTH(created_at)
                    ORDER BY y DESC, m DESC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) { $data[] = $r; }
            $stmt->close();
            break;

        case 'yearly':
            $title = 'Yearly Sales Report';
            $sql = "SELECT YEAR(created_at) y, SUM(total_amount) total
                    FROM orders
                    WHERE payment_status='paid'
                      AND DATE(created_at) BETWEEN ? AND ?
                    GROUP BY YEAR(created_at)
                    ORDER BY y DESC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) { $data[] = $r; }
            $stmt->close();
            break;

        case 'products':
            $title       = 'Product Sales Summary';
            $orientation = 'landscape';
            $sql = "SELECT p.name, SUM(oi.qty) qty, SUM(oi.qty * oi.price) amount
                    FROM order_items oi
                    JOIN orders o   ON o.id = oi.order_id
                    LEFT JOIN products p ON p.id = oi.product_id
                    WHERE o.payment_status='paid'
                      AND DATE(o.created_at) BETWEEN ? AND ?
                    GROUP BY p.name
                    ORDER BY qty DESC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) { $data[] = $r; }
            $stmt->close();
            break;

        case 'transactions':
            $title       = 'Transaction History';
            $orientation = 'landscape';
            $sql = "SELECT id, code, customer_name, total_amount, payment_method, created_at
                    FROM orders
                    WHERE payment_status='paid'
                      AND DATE(created_at) BETWEEN ? AND ?
                    ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ss', $from, $to);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) { $data[] = $r; }
            $stmt->close();
            break;

        default:
            $title = 'Sales Report';
            break;
    }
}

// Build HTML using the existing Sales & Reports theme
ob_start();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= e($title) ?></title>
  <style>
    @page {
      size: A4 <?= $orientation ?>;
      margin: 12mm;
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, sans-serif;
      font-size: 10pt;
      color: #0f172a;
      background: #ffffff;
    }
    .report-wrapper {
      max-width: 100%;
    }
    .report-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      border-bottom: 1px solid #d1d5db;
      padding-bottom: 8px;
      margin-bottom: 12px;
    }
    .report-title {
      font-size: 16pt;
      font-weight: 700;
      color: #2A5618;
    }
    .report-meta {
      font-size: 9pt;
      color: #6b7280;
    }
    .logo-wrap img {
      height: 40px;
    }
    table.report-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      font-size: 9pt;
    }
    table.report-table th,
    table.report-table td {
      border: 1px solid #e5e7eb;
      padding: 4px 6px;
    }
    table.report-table th {
      background: #f3f4f6;
      font-weight: 600;
      text-align: left;
    }
    table.report-table td.text-end {
      text-align: right;
    }
    .footer-note {
      margin-top: 16px;
      font-size: 8.5pt;
      color: #6b7280;
      display: flex;
      justify-content: space-between;
    }
    .signature {
      margin-top: 20px;
      font-size: 9pt;
    }
    .signature-line {
      margin-top: 24px;
      border-top: 1px solid #d1d5db;
      width: 200px;
    }
  </style>
</head>
<body>
<div class="report-wrapper">
  <div class="report-header">
    <div class="logo-wrap">
      <img src="<?= e(APP_URL) ?>assets/img/logo.png" alt="<?= e(APP_NAME ?? 'Paghilom') ?> Logo">
    </div>
    <div style="flex:1;">
      <div class="report-title"><?= e($title) ?></div>
      <div class="report-meta">
        Period: <strong><?= e($from) ?></strong> to <strong><?= e($to) ?></strong><br>
        Generated: <strong><?= e(date('Y-m-d H:i')) ?></strong>
      </div>
    </div>
    <div class="report-meta" style="text-align:right;">
      <?= e(APP_NAME ?? 'Paghilom Cafe') ?><br>
      Sales &amp; Reports Module
    </div>
  </div>

  <?php if ($type === 'daily'): ?>
    <table class="report-table">
      <thead>
        <tr>
          <th>Date</th>
          <th class="text-end">Total Sales</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= e($row['d']) ?></td>
            <td class="text-end"><?= money($row['total']) ?></td>
          </tr>
        <?php endforeach; if (!$data): ?>
          <tr><td colspan="2">No data for the selected period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  <?php elseif ($type === 'weekly'): ?>
    <table class="report-table">
      <thead>
        <tr>
          <th>Year</th>
          <th>Week</th>
          <th class="text-end">Total Sales</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= (int)$row['y'] ?></td>
            <td>W<?= (int)$row['w'] ?></td>
            <td class="text-end"><?= money($row['total']) ?></td>
          </tr>
        <?php endforeach; if (!$data): ?>
          <tr><td colspan="3">No data for the selected period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  <?php elseif ($type === 'monthly'): ?>
    <table class="report-table">
      <thead>
        <tr>
          <th>Year</th>
          <th>Month</th>
          <th class="text-end">Total Sales</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= (int)$row['y'] ?></td>
            <td><?= date('F', mktime(0,0,0,(int)$row['m'],1)) ?></td>
            <td class="text-end"><?= money($row['total']) ?></td>
          </tr>
        <?php endforeach; if (!$data): ?>
          <tr><td colspan="3">No data for the selected period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  <?php elseif ($type === 'yearly'): ?>
    <table class="report-table">
      <thead>
        <tr>
          <th>Year</th>
          <th class="text-end">Total Sales</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= (int)$row['y'] ?></td>
            <td class="text-end"><?= money($row['total']) ?></td>
          </tr>
        <?php endforeach; if (!$data): ?>
          <tr><td colspan="2">No data for the selected period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  <?php elseif ($type === 'products'): ?>
    <table class="report-table">
      <thead>
        <tr>
          <th>Product</th>
          <th class="text-end">Quantity Sold</th>
          <th class="text-end">Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= e($row['name']) ?></td>
            <td class="text-end"><?= (int)$row['qty'] ?></td>
            <td class="text-end"><?= money($row['amount']) ?></td>
          </tr>
        <?php endforeach; if (!$data): ?>
          <tr><td colspan="3">No product sales for the selected period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  <?php elseif ($type === 'transactions'): ?>
    <table class="report-table">
      <thead>
        <tr>
          <th style="width:6%;">ID</th>
          <th style="width:12%;">Code</th>
          <th style="width:22%;">Customer</th>
          <th style="width:12%;" class="text-end">Amount</th>
          <th style="width:10%;">Method</th>
          <th style="width:18%;">Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= e($row['code']) ?></td>
            <td><?= e($row['customer_name'] ?: '—') ?></td>
            <td class="text-end"><?= money($row['total_amount']) ?></td>
            <td><?= e(strtoupper($row['payment_method'])) ?></td>
            <td><?= e($row['created_at']) ?></td>
          </tr>
        <?php endforeach; if (!$data): ?>
          <tr><td colspan="6">No transactions for the selected period.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>

  <?php else: ?>
    <p>No report type selected.</p>
  <?php endif; ?>

  <div class="signature">
    <div>Generated by <?= e(APP_NAME ?? 'Paghilom Cafe') ?> • Sales &amp; Reports Module</div>
    <div class="signature-line"></div>
    <div>Authorized Signature</div>
  </div>

  <div class="footer-note">
    <span><?= e(APP_NAME ?? 'Paghilom Cafe') ?></span>
    <span>Report generated on <?= e(date('Y-m-d H:i')) ?></span>
  </div>
</div>
</body>
</html>
<?php
$html = ob_get_clean();

// Try to turn HTML into a real PDF using Dompdf
$vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

if (class_exists('\\Dompdf\\Dompdf')) {
    $dompdf = new \Dompdf\Dompdf();
    $options = $dompdf->getOptions();
    $options->set('isRemoteEnabled', true); // allow logo and external assets
    $dompdf->setOptions($options);

    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', $orientation);
    $dompdf->render();

    $safeType = preg_replace('/[^a-z0-9_\-]+/i', '_', $type);
    $filename = sprintf('report_%s_%s_%s.pdf', $safeType, $from, $to);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    echo $dompdf->output();
    exit;
}

// Fallback: Use browser print-to-PDF functionality
// Since Dompdf is not available, we'll output HTML with print styles
// and JavaScript to trigger print dialog, which allows saving as PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= e($title) ?> - Print to PDF</title>
  <style>
    @media print {
      @page {
        size: A4 <?= $orientation ?>;
        margin: 12mm;
      }
      body {
        margin: 0;
        padding: 0;
      }
      .print-hint {
        display: none !important;
      }
    }
    @media screen {
      .print-hint {
        background: #fef3c7;
        border: 2px solid #f59e0b;
        border-radius: 8px;
        padding: 16px;
        margin: 20px;
        text-align: center;
      }
      .print-hint h3 {
        margin: 0 0 8px 0;
        color: #92400e;
      }
      .print-hint p {
        margin: 8px 0;
        color: #78350f;
      }
      .print-hint .btn {
        display: inline-block;
        background: #f59e0b;
        color: white;
        padding: 10px 20px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 8px;
      }
    }
  </style>
</head>
<body>
  <div class="print-hint">
    <h3>📄 PDF Generation</h3>
    <p>Dompdf library is not installed. Please use your browser's print function to save as PDF.</p>
    <p><strong>Instructions:</strong> Click the button below or press Ctrl+P (Cmd+P on Mac), then select "Save as PDF" as the destination.</p>
    <button class="btn" onclick="window.print(); return false;">🖨️ Print / Save as PDF</button>
  </div>
  <?= $html ?>
  <script>
    // Auto-trigger print dialog after page loads
    window.addEventListener('load', function() {
      setTimeout(function() {
        window.print();
      }, 500);
    });
  </script>
</body>
</html>
<?php
