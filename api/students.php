<?php
header('Content-Type: application/json');
require_once '../config/config.php';

// Check if called directly
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For POST requests (like delete), check CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        logError('CSRF validation failed in students API', 'security');
        exit;
    }
}

$database = new Database();
$db = $database->connect();
$response = ['success' => false, 'message' => 'Invalid request'];

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all students or a specific student
    try {
        if (isset($_GET['id'])) {
            $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([(int)$_GET['id']]);
            $student = $stmt->fetch();
            
            if ($student) {
                $response = ['success' => true, 'data' => $student];
            } else {
                $response = ['success' => false, 'message' => 'Student not found'];
            }
        } else {
            $stmt = $db->query("SELECT * FROM students ORDER BY name");
            $students = $stmt->fetchAll();
            $response = ['success' => true, 'data' => $students];
        }
    } catch (PDOException $e) {
        logError('Database error in students API: ' . $e->getMessage());
        $response = ['success' => false, 'message' => 'Database error occurred'];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete student
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            $response = ['success' => false, 'message' => 'ID siswa diperlukan'];
        } else {
            try {
                // Get student details before deletion for logging
                $getStmt = $db->prepare("SELECT nis, name FROM students WHERE id = ?");
                $getStmt->execute([(int)$_POST['id']]);
                $studentInfo = $getStmt->fetch();
                
                // Delete the student
                $stmt = $db->prepare("DELETE FROM students WHERE id = ?");
                $result = $stmt->execute([(int)$_POST['id']]);
                
                if ($result) {
                    $response = ['success' => true, 'message' => 'Siswa berhasil dihapus'];
                    if ($studentInfo) {
                        logError("Student deleted: {$studentInfo['name']} ({$studentInfo['nis']})", 'info');
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Gagal menghapus siswa'];
                    logError("Failed to delete student ID: {$_POST['id']}", 'error');
                }
            } catch (PDOException $e) {
                logError('Database error deleting student: ' . $e->getMessage());
                $response = ['success' => false, 'message' => 'Database error occurred'];
            }
        }
    }
}

echo json_encode($response);