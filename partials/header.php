<?php
// Ensure config and database connection are loaded
require_once __DIR__ . '/../config.php';

// Safe escape function
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// ✅ Dynamic values from settings (with fallbacks)
$site_name = function_exists('get_setting') ? get_setting('site_name', (defined('APP_NAME') ? APP_NAME : 'Paghilom Café')) : (defined('APP_NAME') ? APP_NAME : 'Paghilom Café');
$site_tagline = function_exists('get_setting') ? get_setting('site_tagline', 'Paghilom Café — specialty coffee and comfort bites in Sta. Cruz, Laguna.') : 'Paghilom Café — specialty coffee and comfort bites in Sta. Cruz, Laguna.';
// Prefer the bundled Paghilom Café logo under assets/img/logo.png
$logo_candidate = __DIR__.'/../assets/img/logo.png';
if (file_exists($logo_candidate)) {
  $logo_rel = 'assets/img/logo.png';
} elseif (file_exists(__DIR__.'/../uploads/paghilom_logo.png')) {
  $logo_rel = 'uploads/paghilom_logo.png';
} else {
  $logo_rel = function_exists('get_setting') ? get_setting('logo', 'assets/img/logo.png') : 'assets/img/logo.png';
}
$default_logo = (stripos($logo_rel, 'http') === 0) ? $logo_rel : (APP_URL . ltrim($logo_rel, '/'));
$default_title = $site_name;
$default_description = $site_tagline;

// ✅ Fetch Google Analytics ID from settings table
$ga_id = function_exists('get_setting') ? get_setting('ga_id', '') : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($default_title) ?></title>
<meta name="description" content="<?= e($default_description) ?>">
<meta property="og:title" content="<?= e($default_title) ?>">
<meta property="og:description" content="Sip, savor, and slow down.">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= e(APP_URL) ?>">
<meta property="og:image" content="<?= e($default_logo) ?>">

<!-- ✅ Bootstrap & Libraries -->
<link href="<?= e(APP_URL) ?>assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://unpkg.com/aos@2.3.4/dist/aos.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<!-- ✅ Custom CSS -->
<style>
/* Menu Page Styling */
/* Mobile-specific button styles */
@media (max-width: 767.98px) {
    .product-card .btn {
        font-size: 0.65rem !important;
        padding: 0.2rem 0.3rem !important;
        white-space: nowrap;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.1rem;
    }
    
    .product-card .btn i {
        font-size: 0.7em;
        margin-right: 0.05rem;
    }
    
    .product-card .d-flex.gap-2 {
        gap: 0.3rem !important;
    }
    
    .product-card form, 
    .product-card .btn-outline-success {
        flex: 1;
        min-width: 0;
    }
    
        /* Fix for Buy Now button text wrapping */
    .product-card .btn-outline-success {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* ===== Enhanced Mobile View ===== */
    /* Typography */
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.5;
    }
    
    /* Checkout page */
    .checkout-steps {
        font-size: 0.75rem;
        letter-spacing: 0.3px;
    }
    
    .checkout-steps .step {
        padding: 0.4rem 0.6rem;
        border-radius: 4px;
        background-color: #f8f9fa;
        margin: 0 0.2rem;
    }
    
    /* Card Headers */
    .card {
        border: 1px solid rgba(0,0,0,0.08);
        box-shadow: 0 2px 4px rgba(0,0,0,0.03);
    }
    
    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .card-header h1, 
    .card-header h2,
    .card-header h3,
    .card-header h4,
    .card-header h5 {
        font-size: 1.05rem !important;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    
    /* Form Elements */
    .form-label {
        font-size: 0.82rem !important;
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.35rem !important;
    }
    
    .form-control, 
    .form-select, 
    .form-control::placeholder {
        font-size: 0.85rem !important;
        padding: 0.5rem 0.75rem !important;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }
    
    /* Buttons */
    .btn {
        font-size: 0.85rem !important;
        font-weight: 500;
        padding: 0.5rem 1rem !important;
        border-radius: 0.375rem;
        letter-spacing: 0.3px;
        text-transform: none;
    }
    
    .btn-primary {
        background-color: #2563eb;
        border-color: #2563eb;
    }
    
    .btn-outline-primary {
        color: #2563eb;
        border-color: #2563eb;
    }
    
    /* Order Summary */
    .order-summary-item {
        padding: 0.65rem 0 !important;
        border-bottom: 1px solid #f1f3f5;
    }
    
    .order-summary-label {
        font-size: 0.85rem !important;
        color: #495057;
    }
    
    .order-summary-value {
        font-size: 0.85rem !important;
        font-weight: 500;
        color: #212529;
    }
    
    .order-summary-total {
        background-color: #f8f9fa;
        padding: 1rem !important;
        margin: 1rem -1rem -1rem;
        border-top: 1px solid #e9ecef;
    }
    
    .order-summary-total .order-summary-label,
    .order-summary-total .order-summary-value {
        font-size: 0.95rem !important;
        font-weight: 600;
        color: #1a1a1a;
    }
    
    /* Product Items */
    .checkout-item-details h6 {
        font-size: 0.9rem !important;
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 0.25rem !important;
    }
    
    .checkout-item-price {
        font-size: 0.9rem !important;
        font-weight: 500;
        color: #1a1a1a;
    }
    
    /* Spacing and Layout */
    .container {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    .section-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1a1a1a;
        margin-bottom: 1.25rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    /* ===== User Orders Page - Formal Mobile View ===== */
    .section-head {
        padding: 0.75rem 0 !important;
        border-bottom: 1px solid #e9ecef;
        background: #fff;
    }
    
    .section-head .container {
        flex-direction: column;
        gap: 0.75rem;
        align-items: stretch;
        padding: 0 1rem;
    }
    
    .section-head .search-wrap {
        width: 100%;
        max-width: 100%;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 0.4rem 0.75rem;
    }
    
    .section-head h1 {
        font-size: 1.1rem !important;
        font-weight: 600;
        color: #2d3748;
        margin: 0;
    }
    
    .section-head small {
        font-size: 0.75rem;
        color: #718096;
    }
    
    /* Order Cards */
    .order-card {
        margin-bottom: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 8px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    
    .order-card .card-body {
        padding: 1rem;
    }
    
    .order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }
    
    .order-code {
        font-family: 'SF Mono', 'Roboto Mono', monospace;
        font-size: 0.8rem;
        font-weight: 500;
        color: #2d3748;
        word-break: break-all;
    }
    
    .order-date {
        font-size: 0.75rem;
        color: #718096;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 0.5rem;
    }
    
    .order-meta {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: #4a5568;
        margin: 0.5rem 0;
    }
    
    .order-amount {
        font-weight: 600;
        color: #2d3748;
    }
    
    /* Status Badges */
    .badge {
        font-size: 0.65rem !important;
        padding: 0.25rem 0.5rem !important;
        font-weight: 500;
        letter-spacing: 0.3px;
        border-radius: 4px;
    }
    
    .badge-paid {
        background-color: #e6f7ed !important;
        color: #0b5c2c !important;
        border: 1px solid #c3e6d2;
    }
    
    .badge-pending {
        background-color: #fff4e5 !important;
        color: #8a4d0c !important;
        border: 1px solid #ffdfb3;
    }
    
    .badge-failed {
        background-color: #fde8e8 !important;
        color: #9b1c1c !important;
        border: 1px solid #f8b4b4;
    }
    
    /* Action Buttons */
    .order-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    
    .order-actions .btn {
        flex: 1;
        font-size: 0.75rem !important;
        padding: 0.35rem 0.5rem !important;
        border-radius: 4px;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 500;
    }
    
    /* Pagination */
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
        gap: 0.25rem;
        margin: 1.5rem 0;
    }
    
    .page-item .page-link {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
        color: #4a5568;
    }
    
    .page-item.active .page-link {
        background-color: #2d3748;
        border-color: #2d3748;
    }
    
    /* Empty State */
    .empty-state {
        padding: 2rem 1rem;
        text-align: center;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        margin: 1rem 0;
    }
    
    .empty-state .bi {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: #cbd5e0;
    }
    
    .empty-state h4 {
        font-size: 1.1rem;
        color: #2d3748;
        margin-bottom: 0.5rem;
    }
    
    .empty-state p {
        font-size: 0.9rem;
        color: #718096;
        margin-bottom: 1.5rem;
    }
    
    /* Responsive Tables */
    .table-responsive {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        overflow: hidden;
    }
    
    .table {
        margin-bottom: 0;
        font-size: 0.85rem;
    }
    
    .table th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #718096;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 0.75rem;
    }
    
    .table td {
        vertical-align: middle;
        padding: 0.75rem;
        color: #4a5568;
        border-color: #edf2f7;
    }
    
    /* Modal */
    .modal-dialog {
        margin: 0.5rem;
        width: calc(100% - 1rem);
        max-width: 100%;
    }
    
    .modal-content {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
    }
    
    .modal-header {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background-color: #f8fafc;
    }
    
    .modal-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2d3748;
    }
    
    .modal-body {
        padding: 1.25rem;
    }
    
    /* Stores page mobile styles */
    body.stores-page {
        padding-top: 1rem !important;
    }
    
    .stores-page .container {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    
    .stores-page h1.h3 {
        font-size: 1.25rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .stores-page .btn {
        font-size: 0.8rem !important;
        padding: 0.35rem 0.75rem !important;
    }
    
    .stores-page .card {
        border-radius: 0.5rem !important;
        margin-bottom: 1rem;
    }
    
    .stores-page .card-title {
        font-size: 1.1rem !important;
        margin-bottom: 1rem !important;
    }
    
    .stores-page .text-muted {
        font-size: 0.85rem !important;
        line-height: 1.5;
    }
    
    .stores-page iframe {
        height: 250px !important;
    }
    
    .stores-page .bi {
        min-width: 1.25rem;
        text-align: center;
    }
    
    .stores-page .col-md-6 {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
}

.nav-pills .nav-link {
    font-size: 0.75rem;
    padding: 0.4rem 0.8rem;
    border-radius: 0.5rem !important;
    margin-bottom: 0.25rem;
    white-space: nowrap;
    background-color: #f8f9fa;
    color: #495057;
    border: 1px solid #dee2e6;
}

.nav-pills .nav-link.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0a58ca;
}

.product-card {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    transition: transform 0.2s, box-shadow 0.2s;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.product-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
}

.product-card img {
    height: 140px !important;
    object-fit: cover;
    width: 100%;
}

@media (max-width: 767.98px) {
    .nav-pills {
        flex-wrap: nowrap;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        -webkit-overflow-scrolling: touch;
    }
    
    .nav-pills::-webkit-scrollbar {
        height: 4px;
    }
    
    .nav-pills::-webkit-scrollbar-thumb {
        background-color: #dee2e6;
        border-radius: 4px;
    }
    
    .col-6 {
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }
    
    .product-card {
        margin-bottom: 0.5rem;
    }
    
    .product-card h6 {
        font-size: 0.8rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .product-card .text-muted {
        font-size: 0.7rem !important;
        min-height: auto !important;
        margin-bottom: 0.25rem !important;
    }
    
    .product-price {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .btn i {
        margin-right: 0.1rem;
    }
}

@media (max-width: 360px) {
    .product-card h6 {
        font-size: 0.75rem !important;
    }
    
    .btn {
        font-size: 0.7rem;
        padding: 0.2rem 0.4rem;
    }
}
</style>

<!-- ✅ Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<!-- ✅ Custom CSS -->
<link rel="stylesheet" href="<?= e(APP_URL) ?>assets/css/style.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>assets/css/theme.css">

<!-- ✅ Favicon: use assets/img/logo.png if available -->
<?php $fav = file_exists(__DIR__.'/../assets/img/logo.png') ? (APP_URL.'assets/img/logo.png') : (file_exists(__DIR__.'/../uploads/paghilom_logo.png') ? (APP_URL.'uploads/paghilom_logo.png') : (APP_URL.'assets/img/logo.png')); ?>
<link rel="icon" type="image/png" sizes="32x32" href="<?= e($fav.'?v='.time()) ?>">
<link rel="icon" type="image/png" sizes="16x16" href="<?= e($fav.'?v='.time()) ?>">
<link rel="apple-touch-icon" href="<?= e($fav.'?v='.time()) ?>">
<link rel="shortcut icon" href="<?= e($fav.'?v='.time()) ?>" type="image/png">

<?php if (!empty($ga_id)): ?>
<!-- ✅ Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga_id) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', '<?= e($ga_id) ?>');
</script>
<?php endif; ?>
</head>

<?php $body_class = isset($PAGE_BG) ? $PAGE_BG : ''; ?>
<body class="<?= e($body_class) ?>">
<?php include __DIR__ . '/navbar.php'; ?>
<main>
