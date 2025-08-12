<?php
// create_admin.php - Create a new admin user with a known password
require_once 'config/app_config.php';
require_once 'config/database.php';

// Output plain text with headers to prevent any buffer issues
header('Content-Type: text/plain');

try {
    $database = new Database();
    $db = $database->connect();
    
    echo "Database connection established...\n";
    
    // 1. Check if users table exists
    $usersTableExists = $db->query("SHOW TABLES LIKE 'users'")->rowCount() > 0;
    echo "Users table exists: " . ($usersTableExists ? "YES" : "NO") . "\n";
    
    if (!$usersTableExists) {
        echo "Creating users table...\n";
        $db->exec("
            CREATE TABLE `users` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL,
              `password` varchar(255) NOT NULL,
              `email` varchar(100) NOT NULL,
              `full_name` varchar(100) NOT NULL,
              `role` varchar(20) NOT NULL,
              `is_active` tinyint(1) NOT NULL DEFAULT 1,
              `failed_login_attempts` int(11) NOT NULL DEFAULT 0,
              `locked_until` datetime DEFAULT NULL,
              `last_login` datetime DEFAULT NULL,
              `last_login_ip` varchar(45) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `username` (`username`),
              UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "Users table created\n";
    }
    
    // 2. Check if login_attempts table exists
    $attemptsTableExists = $db->query("SHOW TABLES LIKE 'login_attempts'")->rowCount() > 0;
    echo "Login attempts table exists: " . ($attemptsTableExists ? "YES" : "NO") . "\n";
    
    if (!$attemptsTableExists) {
        echo "Creating login_attempts table...\n";
        $db->exec("
            CREATE TABLE `login_attempts` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `username` varchar(50) NOT NULL,
              `ip_address` varchar(45) NOT NULL,
              `attempt_time` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `username` (`username`),
              KEY `ip_address` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        echo "Login attempts table created\n";
    }
    
    // 3. Check if admin user exists
    $adminExists = false;
    $checkAdminStmt = $db->prepare("SELECT id FROM users WHERE username = 'admin'");
    $checkAdminStmt->execute();
    
    if ($checkAdminStmt->fetch()) {
        $adminExists = true;
        echo "Admin user already exists, will be updated\n";
    } else {
        echo "Admin user does not exist, will be created\n";
    }
    
    // 4. Create the admin user password
    $password = 'admin123';
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    echo "Password hash for 'admin123': $passwordHash\n";
    echo "Password hash length: " . strlen($passwordHash) . "\n";
    
    // 5. Verify the hash
    $verifyResult = password_verify($password, $passwordHash);
    echo "Password verification test: " . ($verifyResult ? "SUCCESS" : "FAILURE") . "\n";
    
    // 6. Create or update admin user
    if ($adminExists) {
        $updateAdminStmt = $db->prepare("
            UPDATE users 
            SET password = ?, 
                role = 'admin', 
                is_active = 1, 
                failed_login_attempts = 0, 
                locked_until = NULL,
                email = 'admin@example.com',
                full_name = 'Administrator'
            WHERE username = 'admin'
        ");
        
        $updateAdminStmt->execute([$passwordHash]);
        echo "Admin user updated\n";
    } else {
        $createAdminStmt = $db->prepare("
            INSERT INTO users 
            (username, password, email, full_name, role, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $createAdminStmt->execute([
            'admin',
            $passwordHash,
            'admin@example.com',
            'Administrator',
            'admin',
            1
        ]);
        echo "New admin user created with ID: " . $db->lastInsertId() . "\n";
    }
    
    // 7. Clear all login attempts
    $clearAttemptsStmt = $db->prepare("DELETE FROM login_attempts WHERE username = ?");
    $clearAttemptsStmt->execute(['admin']);
    echo "Login attempts cleared\n";
    
    // 8. Check final admin user data
    $finalCheckStmt = $db->prepare("
        SELECT id, username, email, role, is_active, failed_login_attempts, 
               locked_until, password 
        FROM users 
        WHERE username = 'admin'
    ");
    $finalCheckStmt->execute();
    $adminUser = $finalCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminUser) {
        echo "\n--- Admin User Details ---\n";
        echo "ID: " . $adminUser['id'] . "\n";
        echo "Username: " . $adminUser['username'] . "\n";
        echo "Email: " . $adminUser['email'] . "\n";
        echo "Role: " . $adminUser['role'] . "\n";
        echo "Is Active: " . ($adminUser['is_active'] ? "Yes" : "No") . "\n";
        echo "Failed Attempts: " . $adminUser['failed_login_attempts'] . "\n";
        echo "Locked Until: " . ($adminUser['locked_until'] ?: "Not locked") . "\n";
        echo "Password (first 30 chars): " . substr($adminUser['password'], 0, 30) . "...\n";
        
        // 9. Final verification test
        $finalVerify = password_verify($password, $adminUser['password']);
        echo "Final verification test: " . ($finalVerify ? "SUCCESS" : "FAILURE") . "\n";
    }
    
    echo "\n=== ADMIN ACCOUNT READY ===\n";
    echo "You can now login with:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    
    // Show debug info
    echo "\nDebug Information:\n";
    echo "PHP Version: " . phpversion() . "\n";
    
    if (isset($db)) {
        echo "Database Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "\n";
        echo "Database Server Version: " . $db->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    }
}
?>
