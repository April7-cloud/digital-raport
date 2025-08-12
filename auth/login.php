<?php
// Define BASE_PATH for direct access check
define('BASE_PATH', dirname(dirname(__FILE__)));

// Set page title
$pageTitle = 'Login';

// Include app configuration before HTML output
require_once BASE_PATH . '/config/app_config.php';
require_once BASE_PATH . '/auth/session.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

// Process login form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug information - write to log file
    file_put_contents(BASE_PATH . '/login_debug.log', 
        date('Y-m-d H:i:s') . " - Login attempt for username: " . 
        (isset($_POST['username']) ? $_POST['username'] : 'not set') . "\n", 
        FILE_APPEND);
    
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $error = 'Invalid form submission, please try again';
        logError('CSRF token validation failed during login attempt', 'security');
        file_put_contents(BASE_PATH . '/login_debug.log', 
            date('Y-m-d H:i:s') . " - CSRF validation failed\n", FILE_APPEND);
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password']; // Don't sanitize password before verification
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required';
            file_put_contents(BASE_PATH . '/login_debug.log', 
                date('Y-m-d H:i:s') . " - Empty username or password\n", FILE_APPEND);
        } else {
            $database = new Database();
            $db = $database->connect();
            
            try {
                // First check if login_attempts table exists
                $tableCheckStmt = $db->query("SHOW TABLES LIKE 'login_attempts'");
                $loginAttemptsTableExists = ($tableCheckStmt->rowCount() > 0);
                
                file_put_contents(BASE_PATH . '/login_debug.log', 
                    date('Y-m-d H:i:s') . " - login_attempts table exists: " . 
                    ($loginAttemptsTableExists ? 'yes' : 'no') . "\n", FILE_APPEND);
                
                // Check for too many failed attempts from this IP
                $ipAddress = $_SERVER['REMOTE_ADDR'];
                
                // Only check login_attempts if the table exists
                if ($loginAttemptsTableExists) {
                    $ipLockStmt = $db->prepare("
                        SELECT COUNT(*) AS attempt_count 
                        FROM login_attempts 
                        WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                    ");
                    $ipLockStmt->execute([$ipAddress]);
                    $ipAttempts = $ipLockStmt->fetch();
                    
                    if ($ipAttempts && $ipAttempts['attempt_count'] >= 10) {
                        $error = 'Too many failed login attempts. Please try again later.';
                        logError('IP-based rate limit reached: ' . $ipAddress, 'security');
                        file_put_contents(BASE_PATH . '/login_debug.log', 
                            date('Y-m-d H:i:s') . " - Too many failed attempts\n", FILE_APPEND);
                    }
                }
                
                // Continue with login if no rate limiting issue
                if (empty($error)) {
                    file_put_contents(BASE_PATH . '/login_debug.log', 
                        date('Y-m-d H:i:s') . " - Looking up user: {$username}\n", FILE_APPEND);
                    
                    $stmt = $db->prepare("
                        SELECT * FROM users 
                        WHERE username = ? 
                        AND is_active = 1 
                        AND (locked_until IS NULL OR locked_until < NOW())
                    ");
                    $stmt->execute([$username]);
                    
                    if ($user = $stmt->fetch()) {
                        file_put_contents(BASE_PATH . '/login_debug.log', 
                            date('Y-m-d H:i:s') . " - User found, verifying password\n", FILE_APPEND);
                        
                        if (password_verify($password, $user['password'])) {
                            file_put_contents(BASE_PATH . '/login_debug.log', 
                                date('Y-m-d H:i:s') . " - Password verified, setting up session\n", FILE_APPEND);
                            
                            // Update last login
                            $updateStmt = $db->prepare("
                                UPDATE users 
                                SET last_login = NOW(), 
                                    last_login_ip = ?, 
                                    failed_login_attempts = 0 
                                WHERE id = ?
                            ");
                            $updateStmt->execute([$ipAddress, $user['id']]);
                            
                            // Delete failed login attempts
                            $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ipAddress]);
                            
                            // Set session
                            login($user);
                            
                            // Redirect to dashboard
                            redirect(BASE_URL . '/index.php');
                        } else {
                            // Increment failed login attempts
                            $failedStmt = $db->prepare("
                                UPDATE users 
                                SET failed_login_attempts = failed_login_attempts + 1 
                                WHERE id = ?
                            ");
                            $failedStmt->execute([$user['id']]);
                            
                            // Record failed attempt
                            $db->prepare("
                                INSERT INTO login_attempts (username, ip_address, attempt_time) 
                                VALUES (?, ?, NOW())
                            ")->execute([$username, $ipAddress]);
                            
                            // Check if account should be locked
                            if ($user['failed_login_attempts'] >= 4) {
                                $lockStmt = $db->prepare("
                                    UPDATE users 
                                    SET locked_until = DATE_ADD(NOW(), INTERVAL 30 MINUTE) 
                                    WHERE id = ?
                                ");
                                $lockStmt->execute([$user['id']]);
                                $error = 'Account locked due to too many failed attempts. Try again in 30 minutes.';
                                logError('Account locked: ' . $username, 'security');
                                file_put_contents(BASE_PATH . '/login_debug.log', 
                                    date('Y-m-d H:i:s') . " - Account locked\n", FILE_APPEND);
                            } else {
                                $error = 'Invalid username or password';
                                file_put_contents(BASE_PATH . '/login_debug.log', 
                                    date('Y-m-d H:i:s') . " - Invalid username or password\n", FILE_APPEND);
                            }
                        }
                    } else {
                        // Record failed attempt even for non-existent users
                        $db->prepare("
                            INSERT INTO login_attempts (username, ip_address, attempt_time) 
                            VALUES (?, ?, NOW())
                        ")->execute([$username, $ipAddress]);
                        
                        $error = 'Invalid username or password';
                        file_put_contents(BASE_PATH . '/login_debug.log', 
                            date('Y-m-d H:i:s') . " - Invalid username or password\n", FILE_APPEND);
                    }
                }
            } catch (PDOException $e) {
                $error = 'Database error occurred';
                logError('Database error during login: ' . $e->getMessage(), 'error');
                file_put_contents(BASE_PATH . '/login_debug.log', 
                    date('Y-m-d H:i:s') . " - Database error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo $pageTitle; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #4e73df;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 1.5rem;
        }
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <?php if ($flash = getFlash()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><?php echo SITE_NAME; ?></h4>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Log In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>