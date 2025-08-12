<?php
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if called directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests (like delete), check CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        logError('CSRF validation failed in teachers API', 'security');
        exit;
    }
}

$database = new Database();
$db = $database->connect();
$response = ['success' => false, 'message' => 'Invalid request'];

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all teachers or a specific teacher
    try {
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $teacher = $stmt->fetch();
            
            if ($teacher) {
                $response = ['success' => true, 'data' => $teacher];
            } else {
                $response = ['success' => false, 'message' => 'Teacher not found'];
            }
        } else {
            $stmt = $db->query("SELECT * FROM teachers ORDER BY name");
            $teachers = $stmt->fetchAll();
            $response = ['success' => true, 'data' => $teachers];
        }
    } catch (PDOException $e) {
        logError('Database error in teachers API: ' . $e->getMessage());
        $response = ['success' => false, 'message' => 'Database error occurred'];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete teacher
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $response = ['success' => false, 'message' => 'ID guru diperlukan'];
        } else {
            try {
                // Get teacher details before deletion for logging
                $getStmt = $db->prepare("SELECT nip, name FROM teachers WHERE id = ?");
                $getStmt->execute([(int)$_POST['id']]);
                $teacherInfo = $getStmt->fetch();
                
                // Delete the teacher
                $stmt = $db->prepare("DELETE FROM teachers WHERE id = ?");
                $result = $stmt->execute([(int)$_POST['id']]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Guru berhasil dihapus'];
                    if ($teacherInfo) {
                        logError("Teacher deleted: {$teacherInfo['name']} ({$teacherInfo['nip']})", 'info');
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Gagal menghapus guru'];
                    logError("Failed to delete teacher ID: {$_POST['id']}", 'error');
                }
            } catch (PDOException $e) {
                logError('Database error deleting teacher: ' . $e->getMessage());
                $response = ['success' => false, 'message' => 'Database error occurred'];
            }
        }
    }
}

echo json_encode($response);
