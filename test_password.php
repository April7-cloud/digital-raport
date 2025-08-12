<?php
// Simple password test - test_password.php
require_once 'config/app_config.php';
require_once 'config/database.php';

echo "<h2>Password Test & Debug</h2>";

try {
    $database = new Database();
    $db = $database->connect();
    
    // Get current admin user
    $stmt = $db->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<h3>Current Admin User Data:</h3>";
        echo "<p><strong>Username:</strong> " . $user['username'] . "</p>";
        echo "<p><strong>Current Password Hash:</strong> " . $user['password'] . "</p>";
        echo "<p><strong>Hash Length:</strong> " . strlen($user['password']) . "</p>";
        echo "<p><strong>Role:</strong> " . $user['role'] . "</p>";
        echo "<p><strong>Is Active:</strong> " . ($user['is_active'] ? 'Yes' : 'No') . "</p>";
        echo "<p><strong>Failed Attempts:</strong> " . $user['failed_login_attempts'] . "</p>";
        echo "<p><strong>Locked Until:</strong> " . ($user['locked_until'] ?: 'Not locked') . "</p>";
        
        echo "<hr>";
        
        // Test different passwords
        $testPasswords = ['admin', 'admin123', 'password', '123456'];
        
        echo "<h3>Password Verification Tests:</h3>";
        foreach ($testPasswords as $testPass) {
            $result = password_verify($testPass, $user['password']);
            $color = $result ? 'green' : 'red';
            $status = $result ? '✓ MATCH' : '✗ NO MATCH';
            echo "<p style='color: $color;'><strong>'$testPass':</strong> $status</p>";
        }
        
        echo "<hr>";
        
        // Create new hash and update
        echo "<h3>Creating New Password Hash:</h3>";
        $newPassword = 'admin123';
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        echo "<p><strong>New hash for 'admin123':</strong> $newHash</p>";
        echo "<p><strong>New hash length:</strong> " . strlen($newHash) . "</p>";
        
        // Test the new hash before updating
        if (password_verify($newPassword, $newHash)) {
            echo "<p style='color: green;'>✓ New hash verification works</p>";
            
            // Update the database
            $updateStmt = $db->prepare("UPDATE users SET password = ?, failed_login_attempts = 0, locked_until = NULL WHERE username = 'admin'");
            if ($updateStmt->execute([$newHash])) {
                echo "<p style='color: green;'>✓ Database updated successfully</p>";
                
                // Clear login attempts
                $db->prepare("DELETE FROM login_attempts WHERE username = 'admin'")->execute();
                echo "<p style='color: green;'>✓ Login attempts cleared</p>";
                
                echo "<div style='background: #e8f5e8; padding: 15px; border: 2px solid green; margin: 20px 0;'>";
                echo "<h3 style='color: green; margin-top: 0;'>SUCCESS!</h3>";
                echo "<p><strong>You can now login with:</strong></p>";
                echo "<p><strong>Username:</strong> admin</p>";
                echo "<p><strong>Password:</strong> admin123</p>";
                echo "<p><a href='auth/login.php' style='color: blue; font-weight: bold;'>→ Go to Login Page</a></p>";
                echo "</div>";
                
            } else {
                echo "<p style='color: red;'>✗ Failed to update database</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ New hash verification failed - this shouldn't happen!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Admin user not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
