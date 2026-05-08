<?php
// Points system helper functions for per-user points
require_once __DIR__ . '/../config.php';

if (!function_exists('get_user_points_balance')) {
    function get_user_points_balance(int $user_id): int {
        global $mysqli;
        $stmt = $mysqli->prepare("SELECT COALESCE(SUM(points), 0) AS balance FROM point_transactions WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['balance'] ?? 0);
    }
}

if (!function_exists('get_user_point_history')) {
    function get_user_point_history(int $user_id, int $limit = 20, int $offset = 0): array {
        global $mysqli;
        $stmt = $mysqli->prepare("SELECT id, points, type, ref_type, ref_id, note, created_at FROM point_transactions WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('iii', $user_id, $limit, $offset);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows ?: [];
    }
}

if (!function_exists('add_points')) {
    function add_points(int $user_id, int $points, string $type = 'earn', ?string $ref_type = null, ?int $ref_id = null, ?string $note = null): bool {
        global $mysqli;
        if ($points <= 0) return false;
        $stmt = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('iissis', $user_id, $points, $type, $ref_type, $ref_id, $note);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('redeem_points')) {
    function redeem_points(int $user_id, int $points, ?string $ref_type = null, ?int $ref_id = null, ?string $note = null, bool $allow_negative = false): array {
        global $mysqli;
        if ($points <= 0) return ['success' => false, 'error' => 'Points must be positive'];
        $balance = get_user_points_balance($user_id);
        if (!$allow_negative && $points > $balance) {
            return ['success' => false, 'error' => 'Insufficient points'];
        }
        $neg = -abs($points);
        $stmt = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, ref_type, ref_id, note, created_at) VALUES (?, ?, 'redeem', ?, ?, ?, NOW())");
        $stmt->bind_param('iisis', $user_id, $neg, $ref_type, $ref_id, $note);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? ['success' => true] : ['success' => false, 'error' => 'Database error'];
    }
}

if (!function_exists('expire_points')) {
    function expire_points(int $user_id, int $points, ?string $note = null): bool {
        global $mysqli;
        if ($points <= 0) return false;
        $neg = -abs($points);
        $stmt = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, note, created_at) VALUES (?, ?, 'expire', ?, NOW())");
        $stmt->bind_param('iis', $user_id, $neg, $note);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('adjust_points')) {
    function adjust_points(int $user_id, int $points, ?string $note = null): bool {
        global $mysqli;
        if ($points === 0) return true; // no-op
        $stmt = $mysqli->prepare("INSERT INTO point_transactions (user_id, points, type, note, created_at) VALUES (?, ?, 'adjust', ?, NOW())");
        $stmt->bind_param('iis', $user_id, $points, $note);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
