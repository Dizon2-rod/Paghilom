<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?? 'My Account' ?> - Paghilom Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>assets/css/style.css">
    <style>
    :root {
        --brand: #2A5618;
        --brand-light: #3a7020;
        --gold: #d4af37;
    }
    .sidebar {
        background: white;
        min-height: 100vh;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        position: fixed;
        left: 0;
        top: 0;
        width: 260px;
        padding-top: 20px;
    }
    .sidebar-header {
        padding: 20px;
        background: var(--brand);
        color: white;
        text-align: center;
        margin-bottom: 20px;
    }
    .sidebar-menu {
        list-style: none;
        padding: 0;
    }
    .sidebar-menu li {
        border-bottom: 1px solid #f0f0f0;
    }
    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        color: #333;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .sidebar-menu a:hover,
    .sidebar-menu a.active {
        background: var(--brand);
        color: white;
        padding-left: 30px;
    }
    .sidebar-menu i {
        margin-right: 15px;
        font-size: 1.2rem;
    }
    .main-content {
        margin-left: 260px;
        padding: 30px;
    }
    .dashboard-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 15px;
    }
    .stat-icon.green {
        background: linear-gradient(135deg, var(--brand), var(--brand-light));
        color: white;
    }
    .stat-icon.gold {
        background: linear-gradient(135deg, var(--gold), #b8941f);
        color: white;
    }
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            position: relative;
            min-height: auto;
        }
        .main-content {
            margin-left: 0;
        }
    }
    </style>
</head>
<body class="bg-light">

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <img src="<?= APP_URL ?>assets/images/Paghilom_logo.jpeg" alt="Logo" style="height: 50px; margin-bottom: 10px;">
        <h5 class="mb-0">User Dashboard</h5>
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="order.php" class="<?= basename($_SERVER['PHP_SELF']) == 'order.php' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i> Order Now
            </a>
        </li>
        <li>
            <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>">
                <i class="fas fa-history"></i> My Orders
            </a>
        </li>
        <li>
            <a href="rewards.php" class="<?= basename($_SERVER['PHP_SELF']) == 'rewards.php' ? 'active' : '' ?>">
                <i class="fas fa-gift"></i> My Rewards
            </a>
        </li>
        <li>
            <a href="account.php" class="<?= basename($_SERVER['PHP_SELF']) == 'account.php' ? 'active' : '' ?>">
                <i class="fas fa-user-edit"></i> My Account
            </a>
        </li>
        <li>
            <a href="<?= APP_URL ?>">
                <i class="fas fa-home"></i> Back to Website
            </a>
        </li>
        <li>
            <a href="../paghilom/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
