<?php
require_once __DIR__.'/../../config.php';
require_pos();

// Safe escape function
if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Get staff info
$user_id = $_SESSION['user']['id'] ?? 0;
$stmt = $mysqli->prepare("SELECT name, role FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get pending orders count
$pending_orders = 0;
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'preparing') AND DATE(created_at) = CURDATE()");
if ($result) {
    $pending_orders = $result->fetch_assoc()['count'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>POS - <?= e($user['name'] ?? 'Staff') ?> | <?= e(APP_NAME) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= APP_URL ?>assets/img/logo.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary: #2A5618;
            --primary-light: #3d7a26;
            --secondary: #6c757d;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #198754;
            --warning: #ffc107;
            --danger: #dc3545;
            --border-radius: 0.5rem;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Staff Header */
        .staff-header {
            background: linear-gradient(135deg, var(--primary) 0%, #1e3d10 100%);
            color: white;
            padding: 0.75rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .staff-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .staff-brand img {
            height: 32px;
            width: auto;
        }
        
        .staff-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .staff-nav-link {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .staff-nav-link:hover, .staff-nav-link.active {
            color: white;
            border-bottom-color: white;
        }
        
        .staff-nav-link .badge {
            font-size: 0.6rem;
            padding: 0.25rem 0.4rem;
            border-radius: 10px;
            position: relative;
            top: -1px;
        }
        
        .staff-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            font-weight: 500;
        }
        
        .staff-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        
        /* Main Content */
        .pos-container {
            flex: 1;
            padding: 1.5rem 0;
        }
        
        .pos-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .pos-card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            background-color: #f8f9fa;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pos-card-title {
            font-weight: 600;
            color: var(--dark);
            margin: 0;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .pos-card-body {
            padding: 1.25rem;
            flex: 1;
        }
        
        /* Buttons */
        .btn-pos {
            border-radius: var(--border-radius);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-pos-primary {
            background: var(--primary);
            border: 1px solid var(--primary);
            color: white;
        }
        
        .btn-pos-primary:hover {
            background: var(--primary-light);
            border-color: var(--primary-light);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .btn-pos-outline {
            background: transparent;
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        
        .btn-pos-outline:hover {
            background: rgba(108, 117, 125, 0.1);
            color: var(--dark);
            border-color: var(--dark);
        }
        
        /* Order List */
        .order-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .order-item {
            padding: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: background 0.2s ease;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item:hover {
            background-color: #f8f9fa;
        }
        
        .order-id {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .order-meta {
            font-size: 0.85rem;
            color: var(--secondary);
            display: flex;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .order-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-preparing { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        /* QR Scanner */
        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        #qr-reader__dashboard_section_csr > div:first-child {
            display: none;
        }
        
        /* Mobile Styles */
        @media (max-width: 991.98px) {
            .staff-nav {
                gap: 1rem;
            }
            
            .staff-nav-link {
                font-size: 0.85rem;
            }
            
            .pos-container {
                padding: 1rem 0;
            }
        }
        
        @media (max-width: 767.98px) {
            :root {
                --border-radius: 0.4rem;
            }
            
            .staff-brand {
                font-size: 1rem;
            }
            
            .staff-brand img {
                height: 28px;
            }
            
            .staff-nav {
                gap: 0.75rem;
                overflow-x: auto;
                padding-bottom: 0.5rem;
                margin: 0 -1rem;
                padding: 0 1rem 0.5rem;
            }
            
            .staff-nav-link {
                font-size: 0.8rem;
                white-space: nowrap;
                padding: 0.4rem 0.5rem;
            }
            
            .staff-user {
                display: none;
            }
            
            .pos-card {
                margin-bottom: 1rem;
            }
            
            .pos-card-header {
                padding: 0.75rem 1rem;
            }
            
            .pos-card-title {
                font-size: 0.9rem;
            }
            
            .pos-card-body {
                padding: 1rem;
            }
            
            .btn-pos {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
            }
            
            .order-item {
                padding: 0.75rem;
            }
            
            .order-meta {
                font-size: 0.8rem;
                gap: 0.75rem;
            }
            
            .order-status {
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .staff-header .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .pos-container .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .staff-nav {
                gap: 0.5rem;
            }
            
            .staff-nav-link {
                font-size: 0.75rem;
                padding: 0.35rem 0.4rem;
            }
            
            .pos-card {
                border-radius: 0.3rem;
            }
        }
    </style>
</head>
<body>
    <!-- Staff Header -->
    <header class="staff-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="index.php" class="staff-brand">
                    <img src="<?= APP_URL ?>assets/img/logo.png" alt="Logo">
                    <span>POS System</span>
                </a>
                
                <nav class="staff-nav">
                    <a href="index.php" class="staff-nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="board.php" class="staff-nav-link <?= basename($_SERVER['PHP_SELF']) == 'board.php' ? 'active' : '' ?>">
                        <i class="bi bi-grid"></i>
                        <span>Order Board</span>
                        <?php if ($pending_orders > 0): ?>
                            <span class="badge bg-danger"><?= $pending_orders ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="kitchen.php" class="staff-nav-link <?= basename($_SERVER['PHP_SELF']) == 'kitchen.php' ? 'active' : '' ?>">
                        <i class="bi bi-cup-hot"></i>
                        <span>Kitchen</span>
                    </a>
                    <a href="quick_sale.php" class="staff-nav-link <?= basename($_SERVER['PHP_SELF']) == 'quick_sale.php' ? 'active' : '' ?>">
                        <i class="bi bi-cash-coin"></i>
                        <span>Quick Sale</span>
                    </a>
                </nav>
                
                <div class="staff-user">
                    <div class="staff-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="d-none d-md-block">
                        <div class="small"><?= e($user['name'] ?? 'Staff') ?></div>
                        <div class="xsmall opacity-75"><?= ucfirst(e($user['role'] ?? 'staff')) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="pos-container">
        <div class="container">
