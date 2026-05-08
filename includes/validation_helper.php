<?php
/**
 * Validation Helper Functions
 * For Paghilom Cafe Management System
 */

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $message = null) {
        if (!isset($this->data[$field]) || trim($this->data[$field]) === '') {
            $this->errors[$field] = $message ?? ucfirst($field) . ' is required';
        }
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?? 'Invalid email format';
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function min($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be at least {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function max($field, $length, $message = null) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must not exceed {$length} characters";
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($field, $message = null) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' must be a number';
        }
        return $this;
    }
    
    /**
     * Validate integer value
     */
    public function integer($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' must be an integer';
        }
        return $this;
    }
    
    /**
     * Validate minimum value
     */
    public function minValue($field, $min, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] < $min) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must be at least {$min}";
        }
        return $this;
    }
    
    /**
     * Validate maximum value
     */
    public function maxValue($field, $max, $message = null) {
        if (isset($this->data[$field]) && $this->data[$field] > $max) {
            $this->errors[$field] = $message ?? ucfirst($field) . " must not exceed {$max}";
        }
        return $this;
    }
    
    /**
     * Validate field matches another field
     */
    public function matches($field, $matchField, $message = null) {
        if (isset($this->data[$field]) && isset($this->data[$matchField]) && 
            $this->data[$field] !== $this->data[$matchField]) {
            $this->errors[$field] = $message ?? ucfirst($field) . ' does not match';
        }
        return $this;
    }
    
    /**
     * Validate URL format
     */
    public function url($field, $message = null) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->errors[$field] = $message ?? 'Invalid URL format';
        }
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date($field, $format = 'Y-m-d', $message = null) {
        if (isset($this->data[$field])) {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = $message ?? 'Invalid date format';
            }
        }
        return $this;
    }
    
    /**
     * Validate phone number (Philippine format)
     */
    public function phone($field, $message = null) {
        if (isset($this->data[$field])) {
            $phone = preg_replace('/[^0-9]/', '', $this->data[$field]);
            if (strlen($phone) !== 11 || substr($phone, 0, 2) !== '09') {
                $this->errors[$field] = $message ?? 'Invalid Philippine phone number';
            }
        }
        return $this;
    }
    
    /**
     * Validate field is in array of values
     */
    public function in($field, $values, $message = null) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = $message ?? 'Invalid value';
        }
        return $this;
    }
    
    /**
     * Validate unique value in database
     */
    public function unique($field, $table, $column, $mysqli, $exceptId = null, $message = null) {
        if (isset($this->data[$field])) {
            $query = "SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = ?";
            $types = 's';
            $params = [$this->data[$field]];
            
            if ($exceptId !== null) {
                $query .= " AND id != ?";
                $types .= 'i';
                $params[] = $exceptId;
            }
            
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                $this->errors[$field] = $message ?? ucfirst($field) . ' already exists';
            }
        }
        return $this;
    }
    
    /**
     * Validate exists in database
     */
    public function exists($field, $table, $column, $mysqli, $message = null) {
        if (isset($this->data[$field])) {
            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM `{$table}` WHERE `{$column}` = ?");
            $stmt->bind_param('s', $this->data[$field]);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['count'] === 0) {
                $this->errors[$field] = $message ?? ucfirst($field) . ' does not exist';
            }
        }
        return $this;
    }
    
    /**
     * Validate regex pattern
     */
    public function regex($field, $pattern, $message = null) {
        if (isset($this->data[$field]) && !preg_match($pattern, $this->data[$field])) {
            $this->errors[$field] = $message ?? 'Invalid format';
        }
        return $this;
    }
    
    /**
     * Validate password strength
     */
    public function strongPassword($field, $message = null) {
        if (isset($this->data[$field])) {
            $password = $this->data[$field];
            $errors = [];
            
            if (strlen($password) < 8) {
                $errors[] = 'at least 8 characters';
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = 'one uppercase letter';
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = 'one lowercase letter';
            }
            if (!preg_match('/[0-9]/', $password)) {
                $errors[] = 'one number';
            }
            if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                $errors[] = 'one special character';
            }
            
            if (!empty($errors)) {
                $this->errors[$field] = $message ?? 'Password must contain ' . implode(', ', $errors);
            }
        }
        return $this;
    }
    
    /**
     * Custom validation with callback
     */
    public function custom($field, $callback, $message = null) {
        if (isset($this->data[$field])) {
            $result = call_user_func($callback, $this->data[$field]);
            if (!$result) {
                $this->errors[$field] = $message ?? 'Validation failed';
            }
        }
        return $this;
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Get all errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get first error
     */
    public function firstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Get error for specific field
     */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }
}

/**
 * Create validator instance
 */
function validate($data) {
    return new Validator($data);
}

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize array of inputs
 */
function sanitize_array($array) {
    $sanitized = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitize_array($value);
        } else {
            $sanitized[$key] = sanitize($value);
        }
    }
    return $sanitized;
}

/**
 * Clean filename
 */
function clean_filename($filename) {
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}

/**
 * Validate file upload
 */
function validate_upload($file, $allowed_types = [], $max_size = 10485760) {
    $errors = [];
    
    if (!isset($file['error']) || is_array($file['error'])) {
        $errors[] = 'Invalid file';
        return $errors;
    }
    
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $errors[] = 'No file uploaded';
            return $errors;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errors[] = 'File size exceeds limit';
            return $errors;
        default:
            $errors[] = 'Unknown upload error';
            return $errors;
    }
    
    if ($file['size'] > $max_size) {
        $errors[] = 'File size exceeds ' . ($max_size / 1048576) . 'MB';
    }
    
    if (!empty($allowed_types)) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, $allowed_types)) {
            $errors[] = 'Invalid file type';
        }
    }
    
    return $errors;
}

/**
 * Validate image upload
 */
function validate_image($file, $max_size = 5242880) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    return validate_upload($file, $allowed, $max_size);
}

/**
 * Validate CSRF token
 */
function validate_csrf($token) {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Generate CSRF token
 */
function generate_csrf() {
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * CSRF token input field
 */
function csrf_input() {
    $token = generate_csrf();
    return "<input type='hidden' name='csrf_token' value='{$token}'>";
}

/**
 * CSRF meta tag
 */
function csrf_meta() {
    $token = generate_csrf();
    return "<meta name='csrf-token' content='{$token}'>";
}
?>
