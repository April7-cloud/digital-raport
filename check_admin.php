<?php
// Database check script - check_admin.php
require_once 'config/app_config.php';
require_once 'config/database.php';

echo "<h2>Admin User Check</h2>";

try {
    $database = new Database();
    $db = $database->connect();
    
    // Check if we can connect to database
    if ($db) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Check if users table exists
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ Users table exists</p>";
            
            // Get admin user data
            $stmt = $db->prepare("SELECT id, username, email, full_name, role, is_active, password FROM users WHERE username = 'admin'");
            $stmt->execute();
            
            if ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<p style='color: green;'>✓ Admin user found</p>";
                echo "<h3>Admin User Data:</h3>";
                echo "<ul>";
                echo "<li><strong>ID:</strong> " . $user['id'] . "</li>";
                echo "<li><strong>Username:</strong> " . $user['username'] . "</li>";
                echo "<li><strong>Email:</strong> " . $user['email'] . "</li>";
                echo "<li><strong>Full Name:</strong> " . $user['full_name'] . "</li>";
                echo "<li><strong>Role:</strong> " . $user['role'] . "</li>";
                echo "<li><strong>Is Active:</strong> " . ($user['is_active'] ? 'Yes' : 'No') . "</li>";
                echo "<li><strong>Password Hash:</strong> " . substr($user['password'], 0, 20) . "...</li>";
                echo "</ul>";
                
                // Test password verification
                $testPassword = 'admin123';
                if (password_verify($testPassword, $user['password'])) {
                    echo "<p style='color: green;'>✓ Password 'admin123' matches the hash</p>";
                } else {
                    echo "<p style='color: red;'>✗ Password 'admin123' does NOT match the hash</p>";
                    
                    // Try to create a new hash for admin123
                    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
                    echo "<p><strong>New hash for 'admin123':</strong> $newHash</p>";
                    
                    // Update the password
                    $updateStmt = $db->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
                    if ($updateStmt->execute([$newHash])) {
                        echo "<p style='color: green;'>✓ Password updated successfully. Try logging in with 'admin123'</p>";
                    } else {
                        echo "<p style='color: red;'>✗ Failed to update password</p>";
                    }
                }
            } else {
                echo "<p style='color: red;'>✗ Admin user not found</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Users table does not exist</p>";
        }
        
        // Check login_attempts table
        $stmt = $db->query("SHOW TABLES LIKE 'login_attempts'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✓ login_attempts table exists</p>";
        } else {
            echo "<p style='color: red;'>✗ login_attempts table does not exist</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
