<?php
// Prevent direct access
if (!defined('BASE_PATH')) {
    http_response_code(403);
    die('Forbidden');
}

// Site configuration
define('SITE_NAME', 'Digital Raport');
define('BASE_URL', 'http://localhost/digital-raport');

// Academic settings
define('CURRENT_ACADEMIC_YEAR', '2025/2026');
define('CURRENT_SEMESTER', '1'); // 1 or 2

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'digital_raport_session');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_EXPIRY', 7200); // 2 hours in seconds

// Database configuration
require_once 'database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

/**
 * Set flash message to display once
 * 
 * @param string $type Type of message (success, error, warning, info)
 * @param string $message The message to display
 * @return void
 */
if (!function_exists('setFlash')) {
    function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

/**
 * Check if flash message exists
 * 
 * @return boolean True if flash message exists
 */
if (!function_exists('hasFlash')) {
    function hasFlash() {
        return isset($_SESSION['flash']);
    }
}

/**
 * Get flash message and clear it
 * 
 * @return array|null Array with type and message or null if no message
 */
if (!function_exists('getFlash')) {
    function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

/**
 * Redirect to specified URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
if (!function_exists('redirect')) {
    function redirect($url) {
        // Clean all output buffers before redirecting
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        header("Location: $url");
        exit;
    }
}

/**
 * Sanitize user inpu
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            $output = [];
            foreach ($input as $key => $value) {
                $output[$key] = sanitize($value);
            }
            return $output;
        }
        
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
        
        return $input;
    }
}

/**
 * Check if user has specified role
 * 
 * @param string|array $roles Role(s) to check
 * @return bool True if user has role, false otherwise
 */
//function hasRole($roles) {
  //  if (!isset($_SESSION['user_id'])) {
  //      return false;
    //}
    //
    //if (is_string($roles)) {
    //    $roles = [$roles];
    //}
    
    //return in_array($_SESSION['user_role'], $roles);
//}

/**
 * Check if current user can access a resource
 * 
 * @param string $permission Permission required to access resource
 * @return bool True if user has permission, false otherwise
 */
if (!function_exists('can')) {
    function can($permission) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Admin has all permissions
        if ($_SESSION['user_role'] === 'admin') {
            return true;
        }
        
        // Check if user has specific permission
        if (isset($_SESSION['user_permissions']) && is_array($_SESSION['user_permissions'])) {
            return in_array($permission, $_SESSION['user_permissions']);
        }
        
        return false;
    }
}

/**
 * Format date to a readable format
 * 
 * @param string $date Date to format (Y-m-d)
 * @param string $format Format to use (default: d F Y)
 * @return string Formatted date
 */
if (!function_exists('formatDate')) {
    function formatDate($date, $format = 'd F Y') {
        if (empty($date)) {
            return '-';
        }
        return date($format, strtotime($date));
    }
}

/**
 * Generate CSRF token and store it in session
 * 
 * @return string Generated token
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        // Generate a new token if one doesn't exist or has expired
        if (
            !isset($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_token_time']) || 
            (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY
        ) {
            // Generate a random token
            $token = bin2hex(random_bytes(32));
            
            // Store token and time in session
            $_SESSION['csrf_token'] = $token;
            $_SESSION['csrf_token_time'] = time();
        }
        
        return $_SESSION['csrf_token'];
    }
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if token is valid, false otherwise
 */
if (!function_exists('validateCsrfToken')) {
    function validateCsrfToken($token) {
        // Check if token exists and hasn't expired
        if (
            isset($_SESSION['csrf_token']) && 
            isset($_SESSION['csrf_token_time']) && 
            (time() - $_SESSION['csrf_token_time']) <= CSRF_TOKEN_EXPIRY &&
            hash_equals($_SESSION['csrf_token'], $token)
        ) {
            return true;
        }
        
        return false;
    }
}

/**
 * Create CSRF token input field
 * 
 * @return string HTML input field with CSRF token
 */
if (!function_exists('csrfField')) {
    function csrfField() {
        $token = generateCsrfToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
    }
}

/**
 * Log error messages to a file
 * 
 * @param string $message Error message to log
 * @param string $type Type of error (error, warning, info, debug)
 * @return void
 */
if (!function_exists('logError')) {
    function logError($message, $type = 'error') {
        $logFile = BASE_PATH . '/logs/' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        // Create logs directory if it doesn't exist
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Format the log message
        $formattedMessage = '[' . date('Y-m-d H:i:s') . '] [' . strtoupper($type) . '] ' . $message . PHP_EOL;
        
        // Write to log file
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
    }
}