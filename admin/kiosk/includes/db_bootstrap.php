<?php
// db_bootstrap: tries to include project DB config or falls back to local settings

function kiosk_db_connect(): mysqli {
    // Try common include locations first
    $paths = [
        __DIR__ . '/../../config.php',               // project root config (defines $mysqli)
        __DIR__ . '/../../includes/db.php',
        __DIR__ . '/../../includes/config.php',
        __DIR__ . '/../../../includes/db.php',
        __DIR__ . '/../includes/db.php',
    ];

    foreach ($paths as $p) {
        if (file_exists($p)) {
            require_once $p;
            if (function_exists('db_connect')) {
                $conn = db_connect();
                if ($conn instanceof mysqli) return $conn;
            }
            // If main config.php provided $mysqli, use it
            if (isset($mysqli) && $mysqli instanceof mysqli) return $mysqli;
            if (isset($conn) && $conn instanceof mysqli) return $conn; // supports include styles that set $conn
        }
    }

    // Fallback: env or default local credentials
    $host = getenv('DB_HOST') ?: (defined('DB_HOST') ? DB_HOST : '127.0.0.1');
    $user = getenv('DB_USER') ?: (defined('DB_USER') ? DB_USER : 'root');
    $pass = getenv('DB_PASS') ?: (defined('DB_PASS') ? DB_PASS : '');
    $name = getenv('DB_NAME') ?: (defined('DB_NAME') ? DB_NAME : 'paghilom_cafe');
    $port = getenv('DB_PORT') ?: (defined('DB_PORT') ? DB_PORT : 3306);

    $conn = @new mysqli($host, $user, $pass, $name, (int)$port);
    if ($conn->connect_error) {
        http_response_code(500);
        die('Database connection failed: ' . htmlspecialchars($conn->connect_error));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function kiosk_json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function kiosk_safe_param(string $key, string $method = 'GET'): ?string {
    $src = strtoupper($method) === 'POST' ? $_POST : $_GET;
    if (!isset($src[$key])) return null;
    $val = trim((string)$src[$key]);
    return $val !== '' ? $val : null;
}

function kiosk_log_scan(mysqli $conn, string $code, string $result, ?string $meta = null): void {
    // Attempt DB insert if table exists; fallback to file
    try {
        if (table_exists($conn, 'qr_scans')) {
            $stmt = $conn->prepare('INSERT INTO qr_scans (code, result, meta) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $code, $result, $meta);
            $stmt->execute();
            $stmt->close();
            return;
        }
    } catch (Throwable $e) {
        // ignore and fallback
    }
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    $line = date('c') . "\t" . $code . "\t" . $result . ($meta ? "\t" . $meta : '') . "\n";
    @file_put_contents($dir . '/qr_scans.log', $line, FILE_APPEND);
}

function table_exists(mysqli $conn, string $table): bool {
    $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    if ($res) {
        $exists = $res->num_rows > 0;
        $res->free();
        return $exists;
    }
    return false;
}

function column_exists(mysqli $conn, string $table, string $column): bool {
    $res = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "` LIKE '" . $conn->real_escape_string($column) . "'");
    if ($res) {
        $exists = $res->num_rows > 0;
        $res->free();
        return $exists;
    }
    return false;
}

function first_existing_column(mysqli $conn, string $table, array $candidates): ?string {
    foreach ($candidates as $c) {
        if (column_exists($conn, $table, $c)) return $c;
    }
    return null;
}

?>
