<?php
// Verify database schema and tables (admin-only tool)
// Web access: require logged-in admin; CLI access: allowed without session.
if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../config/auth.php';
    require_admin();
}

$conn = new mysqli('localhost', 'root', '', 'paghilom_cafe');

if ($conn->connect_error) {
    die("❌ Database connection failed: " . $conn->connect_error . "\n");
}

echo "✅ Database connected successfully\n\n";

// List of expected tables
$expectedTables = [
    'users', 'categories', 'products', 'product_images', 'gallery', 
    'settings', 'pages', 'addons', 'milks', 'product_addons', 
    'product_milks', 'orders', 'order_items', 'order_item_options', 
    'clients', 'loyalty_ledger', 'rewards', 'vouchers', 'stock_movements', 
    'login_attempts', 'stores', 'promos', 'ingredients', 'product_recipes', 
    'ingredient_movements', 'point_transactions', 'reward_catalog', 
    'redemptions', 'point_promos'
];

echo "Checking tables:\n";
echo str_repeat("-", 50) . "\n";

$result = $conn->query("SHOW TABLES");
$actualTables = [];
while ($row = $result->fetch_array()) {
    $actualTables[] = $row[0];
}

$missingTables = array_diff($expectedTables, $actualTables);
$extraTables = array_diff($actualTables, $expectedTables);

foreach ($expectedTables as $table) {
    if (in_array($table, $actualTables)) {
        $countResult = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
        $count = $countResult->fetch_assoc()['cnt'];
        echo "✅ $table ($count rows)\n";
    } else {
        echo "❌ $table (MISSING)\n";
    }
}

if (!empty($extraTables)) {
    echo "\nExtra tables found:\n";
    foreach ($extraTables as $table) {
        echo "  • $table\n";
    }
}

echo "\n" . str_repeat("-", 50) . "\n";

if (empty($missingTables)) {
    echo "✅ All required tables exist!\n";
} else {
    echo "❌ Missing tables: " . implode(', ', $missingTables) . "\n";
    echo "\nTo fix: Run the schema_unified.sql file\n";
}

// Check key data
echo "\n" . str_repeat("=", 50) . "\n";
echo "Key Data Verification:\n";
echo str_repeat("-", 50) . "\n";

$checks = [
"SELECT COUNT(*) as cnt FROM users WHERE role='admin'" => "Admin users",
    "SELECT COUNT(*) as cnt FROM categories" => "Categories",
    "SELECT COUNT(*) as cnt FROM products" => "Products",
    "SELECT COUNT(*) as cnt FROM settings" => "Settings",
    "SELECT COUNT(*) as cnt FROM stores" => "Stores",
];

foreach ($checks as $query => $label) {
    $result = $conn->query($query);
    if ($result) {
        $count = $result->fetch_assoc()['cnt'];
        $status = $count > 0 ? "✅" : "⚠️ ";
        echo "$status $label: $count\n";
    }
}

$conn->close();
echo "\n✅ Database verification complete!\n";
?>
