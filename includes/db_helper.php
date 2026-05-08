<?php
/**
 * Database Helper Functions
 * Common database operations for Paghilom Cafe
 */

/**
 * Execute a prepared query and return all results
 */
function db_query($mysqli, $query, $types = '', $params = []) {
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        error_log("DB Query Error: " . $mysqli->error);
        return false;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("DB Execute Error: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        return true; // For INSERT/UPDATE/DELETE
    }
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Execute a query and return a single row
 */
function db_query_single($mysqli, $query, $types = '', $params = []) {
    $stmt = $mysqli->prepare($query);
    
    if (!$stmt) {
        error_log("DB Query Error: " . $mysqli->error);
        return null;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        error_log("DB Execute Error: " . $stmt->error);
        return null;
    }
    
    $result = $stmt->get_result();
    return $result ? $result->fetch_assoc() : null;
}

/**
 * Insert a record and return the insert ID
 */
function db_insert($mysqli, $table, $data) {
    $columns = array_keys($data);
    $values = array_values($data);
    
    $placeholders = str_repeat('?,', count($columns) - 1) . '?';
    $columns_str = implode(',', array_map(function($col) {
        return "`$col`";
    }, $columns));
    
    $query = "INSERT INTO `$table` ($columns_str) VALUES ($placeholders)";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("DB Insert Error: " . $mysqli->error);
        return false;
    }
    
    // Determine types
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $stmt->bind_param($types, ...$values);
    
    if ($stmt->execute()) {
        return $mysqli->insert_id;
    }
    
    error_log("DB Execute Error: " . $stmt->error);
    return false;
}

/**
 * Update a record
 */
function db_update($mysqli, $table, $data, $where, $where_types = '', $where_params = []) {
    $set_parts = [];
    $values = [];
    
    foreach ($data as $column => $value) {
        $set_parts[] = "`$column` = ?";
        $values[] = $value;
    }
    
    $set_str = implode(', ', $set_parts);
    $query = "UPDATE `$table` SET $set_str WHERE $where";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("DB Update Error: " . $mysqli->error);
        return false;
    }
    
    // Determine types
    $types = '';
    foreach ($values as $value) {
        if (is_int($value)) {
            $types .= 'i';
        } elseif (is_float($value)) {
            $types .= 'd';
        } else {
            $types .= 's';
        }
    }
    
    $types .= $where_types;
    $all_params = array_merge($values, $where_params);
    
    $stmt->bind_param($types, ...$all_params);
    
    return $stmt->execute();
}

/**
 * Delete a record
 */
function db_delete($mysqli, $table, $where, $types = '', $params = []) {
    $query = "DELETE FROM `$table` WHERE $where";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("DB Delete Error: " . $mysqli->error);
        return false;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt->execute();
}

/**
 * Count records
 */
function db_count($mysqli, $table, $where = '1=1', $types = '', $params = []) {
    $query = "SELECT COUNT(*) as count FROM `$table` WHERE $where";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("DB Count Error: " . $mysqli->error);
        return 0;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return (int)($result['count'] ?? 0);
}

/**
 * Check if a record exists
 */
function db_exists($mysqli, $table, $where, $types = '', $params = []) {
    return db_count($mysqli, $table, $where, $types, $params) > 0;
}

/**
 * Get paginated results
 */
function db_paginate($mysqli, $query, $page = 1, $per_page = 20, $types = '', $params = []) {
    $offset = ($page - 1) * $per_page;
    
    // Get total count
    $count_query = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $query);
    $count_query = preg_replace('/ORDER BY .+/i', '', $count_query);
    $count_query = preg_replace('/LIMIT .+/i', '', $count_query);
    
    $stmt = $mysqli->prepare($count_query);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    
    // Get paginated results
    $query .= " LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($query);
    
    $all_params = $params;
    $all_params[] = $per_page;
    $all_params[] = $offset;
    
    $all_types = $types . 'ii';
    
    if ($all_types) {
        $stmt->bind_param($all_types, ...$all_params);
    }
    
    $stmt->execute();
    $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return [
        'data' => $results,
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => ceil($total / $per_page)
    ];
}

/**
 * Begin database transaction
 */
function db_begin_transaction($mysqli) {
    return $mysqli->begin_transaction();
}

/**
 * Commit database transaction
 */
function db_commit($mysqli) {
    return $mysqli->commit();
}

/**
 * Rollback database transaction
 */
function db_rollback($mysqli) {
    return $mysqli->rollback();
}

/**
 * Execute multiple queries in a transaction
 */
function db_transaction($mysqli, $callback) {
    try {
        $mysqli->begin_transaction();
        $result = $callback($mysqli);
        $mysqli->commit();
        return $result;
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Transaction Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Escape string for SQL (use prepared statements when possible)
 */
function db_escape($mysqli, $string) {
    return $mysqli->real_escape_string($string);
}

/**
 * Get last insert ID
 */
function db_last_insert_id($mysqli) {
    return $mysqli->insert_id;
}

/**
 * Get affected rows
 */
function db_affected_rows($mysqli) {
    return $mysqli->affected_rows;
}

/**
 * Build WHERE clause from array
 */
function db_build_where($conditions, &$types, &$params) {
    $where_parts = [];
    
    foreach ($conditions as $column => $value) {
        if (is_array($value)) {
            // Handle IN clause
            $placeholders = str_repeat('?,', count($value) - 1) . '?';
            $where_parts[] = "`$column` IN ($placeholders)";
            foreach ($value as $v) {
                $params[] = $v;
                $types .= is_int($v) ? 'i' : 's';
            }
        } elseif ($value === null) {
            $where_parts[] = "`$column` IS NULL";
        } else {
            $where_parts[] = "`$column` = ?";
            $params[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }
    }
    
    return implode(' AND ', $where_parts);
}

/**
 * Bulk insert records
 */
function db_bulk_insert($mysqli, $table, $data_array) {
    if (empty($data_array)) {
        return false;
    }
    
    $columns = array_keys($data_array[0]);
    $columns_str = implode(',', array_map(function($col) {
        return "`$col`";
    }, $columns));
    
    $placeholders = '(' . str_repeat('?,', count($columns) - 1) . '?)';
    $all_placeholders = str_repeat($placeholders . ',', count($data_array) - 1) . $placeholders;
    
    $query = "INSERT INTO `$table` ($columns_str) VALUES $all_placeholders";
    
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        error_log("DB Bulk Insert Error: " . $mysqli->error);
        return false;
    }
    
    // Flatten values and determine types
    $values = [];
    $types = '';
    
    foreach ($data_array as $row) {
        foreach ($row as $value) {
            $values[] = $value;
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
    }
    
    $stmt->bind_param($types, ...$values);
    
    return $stmt->execute();
}
?>
