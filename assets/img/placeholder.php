<?php
// Simple placeholder image generator
header('Content-Type: image/svg+xml');
$width = $_GET['w'] ?? 400;
$height = $_GET['h'] ?? 400;
$text = $_GET['text'] ?? 'No Image';

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg width="<?= $width ?>" height="<?= $height ?>" xmlns="http://www.w3.org/2000/svg">
  <rect width="100%" height="100%" fill="#f8f9fa"/>
  <rect x="10" y="10" width="<?= $width-20 ?>" height="<?= $height-20 ?>" fill="none" stroke="#dee2e6" stroke-width="2" stroke-dasharray="5,5"/>
  <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="16" fill="#6c757d" text-anchor="middle" dominant-baseline="middle"><?= htmlspecialchars($text) ?></text>
</svg>
