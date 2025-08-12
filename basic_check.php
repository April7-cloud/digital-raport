<?php
// basic_check.php - Simple diagnostic script
// This file should be placed in the root directory (c:\xampp\htdocs\digital-raport\)

// Disable any output buffering to ensure we see all output
@ob_end_clean();
if (ob_get_level()) ob_end_clean();

// Set content type to plain text for better readability
header('Content-Type: text/plain');
header('X-Accel-Buffering: no');

// Basic environment info
echo "===== BASIC SYSTEM CHECK =====\n\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Web Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

// File system check
echo "===== FILE SYSTEM CHECK =====\n\n";
$scriptPath = __FILE__;
$baseDir = dirname($scriptPath);
echo "Script path: $scriptPath\n";
echo "Base directory: $baseDir\n";

// Check read permissions
echo "Can read directory: " . (is_readable($baseDir) ? "YES" : "NO") . "\n";

// Check if important files exist
$configFile = $baseDir . '/config/app_config.php';
$dbConfigFile = $baseDir . '/config/database.php';
$loginFile = $baseDir . '/auth/login.php';

echo "app_config.php exists: " . (file_exists($configFile) ? "YES" : "NO") . "\n";
echo "database.php exists: " . (file_exists($dbConfigFile) ? "YES" : "NO") . "\n";
echo "login.php exists: " . (file_exists($loginFile) ? "YES" : "NO") . "\n\n";

// Check database access without requiring other files
echo "===== DATABASE CHECK =====\n\n";

// Direct database test
try {
    // Try to determine database settings from database.php
    if (file_exists($dbConfigFile)) {
        $dbConfigContent = file_get_contents($dbConfigFile);
        
        // Try to extract database settings using regex
        preg_match('/host\s*=\s*[\'"]([^\'"]+)/', $dbConfigContent, $hostMatches);
        preg_match('/db_name\s*=\s*[\'"]([^\'"]+)/', $dbConfigContent, $dbNameMatches);
        preg_match('/username\s*=\s*[\'"]([^\'"]+)/', $dbConfigContent, $userMatches);
        preg_match('/password\s*=\s*[\'"]([^\'"]+)/', $dbConfigContent, $passMatches);
        
        $host = isset($hostMatches[1]) ? $hostMatches[1] : 'localhost';
        $dbName = isset($dbNameMatches[1]) ? $dbNameMatches[1] : 'digital_raport';
        $username = isset($userMatches[1]) ? $userMatches[1] : 'root';
        $password = isset($passMatches[1]) ? $passMatches[1] : '';
        
        echo "Extracted database settings:\n";
        echo "- Host: $host\n";
        echo "- Database: $dbName\n";
        echo "- Username: $username\n";
        echo "- Password: " . (empty($password) ? "(empty)" : "(found)") . "\n\n";
    } else {
        echo "Could not find database.php, using default values\n";
        $host = 'localhost';
        $dbName = 'digital_raport';
        $username = 'root';
        $password = '';
    }
    
    // Try to connect directly
    echo "Attempting direct PDO connection...\n";
    $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Database connection successful!\n\n";
    
    // Check if tables exist
    echo "Checking tables:\n";
    $tables = ['users', 'login_attempts'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "- $table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
    }
    
    // Check for admin user
    echo "\nChecking admin user:\n";
    try {
        $stmt = $pdo->query("SELECT * FROM users WHERE username = 'admin'");
        if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "Admin user found:\n";
            echo "- ID: " . $user['id'] . "\n";
            echo "- Username: " . $user['username'] . "\n";
            echo "- Role: " . $user['role'] . "\n";
            echo "- Active: " . ($user['is_active'] ? "Yes" : "No") . "\n";
            echo "- Password hash (first 20 chars): " . substr($user['password'], 0, 20) . "...\n";
            
            // Test password verification
            $testPassword = 'admin123';
            $verificationResult = password_verify($testPassword, $user['password']);
            echo "- Password 'admin123' verification: " . ($verificationResult ? "WORKS" : "FAILS") . "\n";
            
            if (!$verificationResult) {
                echo "\nTrying to update admin password directly...\n";
                $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
                $updateStmt->execute([$newHash]);
                echo "Admin password updated. New hash: " . substr($newHash, 0, 20) . "...\n";
                
                // Verify again
                $checkStmt = $pdo->query("SELECT password FROM users WHERE username = 'admin'");
                $updatedUser = $checkStmt->fetch();
                $newVerification = password_verify($testPassword, $updatedUser['password']);
                echo "Verification after update: " . ($newVerification ? "WORKS" : "FAILS") . "\n";
            }
        } else {
            echo "Admin user not found. Creating new admin user...\n";
            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            
            $insertStmt = $pdo->prepare("
                INSERT INTO users (username, password, email, full_name, role, is_active) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $insertStmt->execute([
                'admin',
                $newHash,
                'admin@example.com',
                'Administrator',
                'admin',
                1
            ]);
            
            echo "New admin user created with ID: " . $pdo->lastInsertId() . "\n";
            echo "Password hash: " . substr($newHash, 0, 20) . "...\n";
        }
    } catch (Exception $e) {
        echo "Error checking admin user: " . $e->getMessage() . "\n";
    }
    
} catch (PDOException $e) {
    echo "DATABASE CONNECTION ERROR: " . $e->getMessage() . "\n";
}

// Session check
echo "\n===== SESSION CHECK =====\n\n";
echo "Session status: ";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "DISABLED (Sessions are disabled on server)\n";
        break;
    case PHP_SESSION_NONE:
        echo "NONE (Session enabled but not started)\n";
        break;
    case PHP_SESSION_ACTIVE:
        echo "ACTIVE (Session already started)\n";
        break;
}

// Try to start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    echo "Starting new session...\n";
    if (@session_start()) {
        echo "Session started successfully\n";
    } else {
        echo "Failed to start session\n";
    }
}

echo "Session save path: " . session_save_path() . "\n";
echo "Session write access: " . (is_writable(session_save_path()) ? "YES" : "NO") . "\n\n";

// Check .htaccess rules
echo "===== ACCESS CHECK =====\n\n";
$htaccessFile = $baseDir . '/.htaccess';
echo ".htaccess exists: " . (file_exists($htaccessFile) ? "YES" : "NO") . "\n";

if (file_exists($htaccessFile)) {
    echo "Content of .htaccess:\n";
    echo file_get_contents($htaccessFile) . "\n\n";
}

// Final recommendations
echo "\n===== RECOMMENDATIONS =====\n\n";
echo "1. Access the login page directly: http://localhost/digital-raport/auth/login.php\n";
echo "2. Try username 'admin' and password 'admin123'\n";
echo "3. Check for any error messages in the browser console (F12)\n";
echo "4. If still not working, try clearing your browser cookies and cache\n\n";

echo "If you need more assistance, please share this diagnostic output.\n";
?>
