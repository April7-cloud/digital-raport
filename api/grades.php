<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

// Create database connection
$database = new Database();
$db = $database->connect();

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

// Get action from query string or request body
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Handle different actions
switch ($action) {
    case 'save_grades':
        if ($method === 'POST') {
            handleSaveGrades($db, $response);
        } else {
            $response['message'] = 'Method not allowed';
            http_response_code(405);
        }
        break;
        
    case 'get_grades':
        if ($method === 'GET') {
            handleGetGrades($db, $response);
        } else {
            $response['message'] = 'Method not allowed';
            http_response_code(405);
        }
        break;
        
    default:
        $response['message'] = 'Invalid action';
        http_response_code(400);
        break;
}

// Return JSON response
echo json_encode($response);

/**
 * Handle saving grades
 */
function handleSaveGrades($db, &$response) {
    // Check CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $response['message'] = 'Invalid CSRF token';
        http_response_code(403);
        return;
    }
    
    // Check if user has permission
    if (!hasRole(['admin', 'guru'])) {
        $response['message'] = 'Unauthorized';
        http_response_code(403);
        return;
    }
    
    // Get grades data from request
    $gradesData = json_decode(file_get_contents('php://input'), true);
    
    if (empty($gradesData['grades']) || !is_array($gradesData['grades'])) {
        $response['message'] = 'Invalid grades data';
        http_response_code(400);
        return;
    }
    
    try {
        $db->beginTransaction();
        
        // Prepare statement for upsert
        $stmt = $db->prepare("
            INSERT INTO grades (student_id, subject_id, grade, note)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                grade = VALUES(grade),
                note = VALUES(note),
                updated_at = CURRENT_TIMESTAMP");
        
        $successCount = 0;
        $subjectId = null;
        
        foreach ($gradesData['grades'] as $grade) {
            if (empty($grade['student_id']) || empty($grade['subject_id'])) {
                continue;
            }
            
            $subjectId = $grade['subject_id'];
            $stmt->execute([
                $grade['student_id'],
                $grade['subject_id'],
                !empty($grade['grade']) ? $grade['grade'] : null,
                !empty($grade['note']) ? $grade['note'] : null
            ]);
            
            if ($stmt->rowCount() > 0) {
                $successCount++;
            }
        }
        
        $db->commit();
        
        $response['success'] = true;
        $response['message'] = "Berhasil menyimpan $successCount nilai";
        
        // Log activity
        if ($subjectId) {
            logActivity("Menyimpan nilai untuk mata pelajaran ID: $subjectId");
        }
        
    } catch (PDOException $e) {
        $db->rollBack();
        logError('Error saving grades: ' . $e->getMessage());
        $response['message'] = 'Gagal menyimpan data nilai';
        http_response_code(500);
    }
}

/**
 * Handle getting grades for a subject
 */
function handleGetGrades($db, &$response) {
    // Check if user has permission
    if (!hasRole(['admin', 'guru', 'siswa', 'orangtua'])) {
        $response['message'] = 'Unauthorized';
        http_response_code(403);
        return;
    }
    
    $subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    
    if ($subjectId <= 0) {
        $response['message'] = 'ID mata pelajaran tidak valid';
        http_response_code(400);
        return;
    }
    
    try {
        // Get grades data
        $stmt = $db->prepare("
            SELECT g.student_id, g.grade, g.note, g.updated_at,
                   s.nis, s.full_name, s.class
            FROM grades g
            JOIN students s ON g.student_id = s.id
            WHERE g.subject_id = ?
            ORDER BY s.full_name ASC");
        
        $stmt->execute([$subjectId]);
        $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['data'] = $grades;
        
    } catch (PDOException $e) {
        logError('Error fetching grades: ' . $e->getMessage());
        $response['message'] = 'Gagal mengambil data nilai';
        http_response_code(500);
    }
}
?>
