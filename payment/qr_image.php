<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/qr_unified.php';

$code = trim($_GET['code'] ?? '');
$size = max(120, min(800, (int)($_GET['size'] ?? 240)));
if ($code === '') { http_response_code(400); echo 'Missing code'; exit; }

// Try to render PNG via phpqrcode if available, else proxy Google Charts
$qrlib = __DIR__ . '/../vendor/phpqrcode/qrlib.php';
if (file_exists($qrlib)) {
    require_once $qrlib;
    header('Content-Type: image/png');
    // High error correction, small margin
    \QRcode::png($code, false, QR_ECLEVEL_H, max(1, (int)($size/25)), 2);
    exit;
}

// Fallback: fetch Google Chart PNG and proxy
$endpoint = 'https://chart.googleapis.com/chart?'. http_build_query([
    'chs' => $size.'x'.$size,
    'cht' => 'qr',
    'chl' => $code,
    'choe'=> 'UTF-8',
    'chld'=> 'H|2'
]);
$img = @file_get_contents($endpoint);
if ($img !== false) {
    header('Content-Type: image/png');
    echo $img; exit;
}
http_response_code(500);
echo 'QR generation failed';
