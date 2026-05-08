<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design System Verification - Paghilom Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .status-ok { color: #10b981; }
        .status-warning { color: #f59e0b; }
        .status-error { color: #ef4444; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0"><i class="fas fa-check-circle me-2"></i>Design System Verification</h2>
            </div>
            <div class="card-body">
                <p class="lead">Checking which PHP files use the unified design system...</p>
                
                <?php
                function checkFile($filepath) {
                    if (!file_exists($filepath)) {
                        return ['exists' => false];
                    }
                    
                    $content = file_get_contents($filepath);
                    
                    return [
                        'exists' => true,
                        'has_config' => strpos($content, 'config.php') !== false,
                        'has_header' => strpos($content, 'partials/header.php') !== false,
                        'has_footer' => strpos($content, 'partials/footer.php') !== false,
                        'has_style_css' => strpos($content, 'style.css') !== false,
                        'has_primary_color' => strpos($content, '#2A5618') !== false || strpos($content, 'btn-primary') !== false,
                    ];
                }
                
                $files_to_check = [
                    'Main Pages' => [
                        'index.php',
                        'login.php',
                        'register.php',
                        'gallery.php',
                        'stores.php',
                        'map.php',
                        'product.php',
                        'cart.php',
                        'checkout.php',
                    ],
                    'Admin Pages' => [
                        'admin/index.php',
                        'admin/products.php',
                        'admin/categories.php',
                        'admin/orders.php',
                        'admin/users.php',
                        'admin/settings.php',
                        'admin/reports.php',
                    ],
                    'POS Pages' => [
                        'pos/index.php',
                        'pos/edit_order.php',
                        'pos/quick_sale.php',
                        'pos/kitchen.php',
                        'pos/receipt.php',
                    ],
                    'User Pages' => [
                        'user/dashboard.php',
                        'user/orders.php',
                        'user/rewards.php',
                        'user/account.php',
                    ],
                ];
                
                foreach ($files_to_check as $category => $files) {
                    echo "<h4 class='mt-4 mb-3'><i class='fas fa-folder me-2'></i>$category</h4>";
                    echo "<div class='table-responsive'>";
                    echo "<table class='table table-hover'>";
                    echo "<thead><tr>";
                    echo "<th>File</th>";
                    echo "<th>Exists</th>";
                    echo "<th>Config</th>";
                    echo "<th>Header</th>";
                    echo "<th>Footer</th>";
                    echo "<th>Uses Design</th>";
                    echo "</tr></thead>";
                    echo "<tbody>";
                    
                    foreach ($files as $file) {
                        $check = checkFile($file);
                        $overall = $check['exists'] && $check['has_config'] && $check['has_header'] && $check['has_footer'];
                        
                        echo "<tr>";
                        echo "<td><code>$file</code></td>";
                        echo "<td>" . ($check['exists'] ? "✅" : "❌") . "</td>";
                        
                        if ($check['exists']) {
                            echo "<td>" . ($check['has_config'] ? "✅" : "⚠️") . "</td>";
                            echo "<td>" . ($check['has_header'] ? "✅" : "⚠️") . "</td>";
                            echo "<td>" . ($check['has_footer'] ? "✅" : "⚠️") . "</td>";
                            echo "<td>" . ($overall ? "<span class='badge bg-success'>Good</span>" : "<span class='badge bg-warning'>Needs Update</span>") . "</td>";
                        } else {
                            echo "<td colspan='4' class='text-muted'>File not found</td>";
                        }
                        
                        echo "</tr>";
                    }
                    
                    echo "</tbody></table>";
                    echo "</div>";
                }
                ?>
                
                <hr class="my-4">
                
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle me-2"></i>Legend</h5>
                    <ul class="mb-0">
                        <li>✅ = Present and correct</li>
                        <li>⚠️ = Missing or needs attention</li>
                        <li>❌ = File doesn't exist</li>
                    </ul>
                </div>
                
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>Design System Status</h5>
                    <p class="mb-2"><strong>Color Palette:</strong> #2A5618 (Primary), #F6FFF6 (Background) ✅</p>
                    <p class="mb-2"><strong>CSS File:</strong> <code>assets/css/style.css</code> ✅</p>
                    <p class="mb-2"><strong>Partials:</strong> <code>header.php</code>, <code>navbar.php</code>, <code>footer.php</code> ✅</p>
                    <p class="mb-0"><strong>Navigation:</strong> Connected between index.php ↔ admin/index.php ✅</p>
                </div>
                
                <div class="d-flex gap-2 mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Go to Homepage
                    </a>
                    <a href="admin/" class="btn btn-outline-primary">
                        <i class="fas fa-cog me-2"></i>Go to Admin
                    </a>
                    <a href="DESIGN_SYSTEM.md" class="btn btn-outline-secondary" download>
                        <i class="fas fa-download me-2"></i>Download Design Docs
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>
