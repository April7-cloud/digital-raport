<?php
// Prevent direct access
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(dirname(__FILE__)));
    http_response_code(403);
    die('Forbidden');
}

require_once BASE_PATH . '/config/app_config.php';

/**
 * Initialize secure session
 */
if (!function_exists('initSession')) {
    function initSession() {
        // First check if session is already active
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Session already started elsewhere - just handle regeneration
            if (!isset($_SESSION['last_regeneration'])) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
            return; // Exit early, don't try to modify session settings
        }
        
        // Session is not active, we can safely set parameters
        // Set session cookie parameters
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => isset($_SERVER['HTTPS']), // Only send over HTTPS
            'httponly' => true, // Prevent JavaScript access to session cookie
            'samesite' => 'Lax' // CSRF protection
        ]);

        // Set session name
        session_name(SESSION_NAME);
        
        // Start the session
        session_start();
        
        // Regenerate session ID if needed
        if (!isset($_SESSION['last_regeneration'])) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

/**
 * Log in a user and set session variables
 * 
 * @param array $user User data from database
 * @return void
 */
if (!function_exists('login')) {
    function login($user) {
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['last_activity'] = time();
        
        // Store user agent and IP for additional security
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        // Set CSRF token
        generateCsrfToken();
        
        // Load user permissions
        loadUserPermissions($user['id']);
    }
}

/**
 * Load user permissions
 * 
 * @param int $userId User ID
 * @return void
 */
if (!function_exists('loadUserPermissions')) {
    function loadUserPermissions($userId) {
        global $db;
        
        if (!isset($db)) {
            $database = new Database();
            $db = $database->connect();
        }
        
        $permissions = [];
        
        // Get role-based permissions
        if (isset($_SESSION['user_role'])) {
            try {
                $stmt = $db->prepare("SELECT permission FROM role_permissions WHERE role = ?");
                $stmt->execute([$_SESSION['user_role']]);
                
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $permissions[] = $row['permission'];
                }
            } catch (PDOException $e) {
                logError('Error loading role permissions: ' . $e->getMessage());
            }
        }
        
        // Get user-specific permissions
        try {
            $stmt = $db->prepare("SELECT permission FROM user_permissions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $permissions[] = $row['permission'];
            }
        } catch (PDOException $e) {
            logError('Error loading user permissions: ' . $e->getMessage());
        }
        
        // Store permissions in session
        $_SESSION['permissions'] = array_unique($permissions);
    }
}

/**
 * Log out the current user
 * 
 * @return void
 */
if (!function_exists('logout')) {
    function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        redirect(BASE_URL . '/auth/login.php');
    }
}

/**
 * Check if user is logged in and session is valid
 * 
 * @return bool True if user is logged in and session is valid
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        // Check if session variables are set
        if (!isset(
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['user_role'],
            $_SESSION['last_activity'],
            $_SESSION['user_agent'],
            $_SESSION['ip_address']
        )) {
            return false;
        }
        
        // Check session timeout
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            logout();
            return false;
        }
        
        // Check for session hijacking
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT'] || 
            $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            // Log suspicious activity
            logError("Possible session hijacking attempt for user: {$_SESSION['username']}", 'security');
            logout();
            return false;
        }
        
        // Update last activity time
        $_SESSION['last_activity'] = time();
        
        return true;
    }
}

/**
 * Check if user has a specific role
 * 
 * @param string|array $roles Role or array of roles to check
 * @return bool True if user has the required role
 */
if (!function_exists('hasRole')) {
    function hasRole($roles) {
        if (!isLoggedIn()) {
            return false;
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['user_role'], $roles);
    }
}

/**
 * Check if user session is active, redirect to login if not
 * 
 * @param bool $redirect Whether to redirect to login page
 * @return bool True if logged in, false otherwise
 */
if (!function_exists('checkSession')) {
    function checkSession($redirect = true) {
        // Check if user is logged in
        if (!isLoggedIn()) {
            if ($redirect) {
                setFlash('warning', 'Please login to continue');
                redirect(BASE_URL . '/auth/login.php');
            }
            return false;
        }
        
        return true;
    }
}

// Initialize session
initSession();