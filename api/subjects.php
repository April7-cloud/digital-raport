<?php
// API endpoint for subjects
require_once '../config/config.php';

// Headers for API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// Connect to database
$database = new Database();
$db = $database->connect();

// Handle different request methods
$method = $_SERVER['REQUEST_METHOD'];
$response = ['success' => false, 'message' => 'Invalid request', 'data' => null];

try {
    switch ($method) {
        case 'GET':
            // Handle GET requests - fetch subjects
            handleGetRequest($db, $response);
            break;
            
        case 'POST':
            // Handle POST requests - typically used for delete operations
            handlePostRequest($db, $response);
            break;
            
        default:
            $response['message'] = 'Unsupported request method';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    logError('API error: ' . $e->getMessage());
}

// Return response as JSON
echo json_encode($response);
exit;

/**
 * Handle GET requests to fetch subjects
 */
function handleGetRequest($db, &$response) {
    // Get query parameters
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $class = isset($_GET['class']) ? sanitize($_GET['class']) : null;
    
    try {
        if ($id) {
            // Fetch specific subject
            $stmt = $db->prepare("
                SELECT s.*, t.name AS teacher_name
                FROM subjects s
                LEFT JOIN teachers t ON s.teacher_id = t.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            $subject = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($subject) {
                $response['success'] = true;
                $response['message'] = 'Subject retrieved successfully';
                $response['data'] = $subject;
            } else {
                $response['message'] = 'Subject not found';
            }
        } else {
            // Fetch all subjects or filter by class
            $query = "
                SELECT s.*, t.name AS teacher_name
                FROM subjects s
                LEFT JOIN teachers t ON s.teacher_id = t.id
                WHERE 1
            ";
            $params = [];
            
            if ($class) {
                $query .= " AND s.class = ?";
                $params[] = $class;
            }
            
            $query .= " ORDER BY s.name";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response['success'] = true;
            $response['message'] = 'Subjects retrieved successfully';
            $response['data'] = $subjects;
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error';
        logError('API subjects GET error: ' . $e->getMessage());
    }
}

/**
 * Handle POST requests (mainly for delete operations)
 */
function handlePostRequest($db, &$response) {
    // Get the requested action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    // Handle CSRF validation for all non-GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $action !== 'get_classes') {
        // CSRF token validation
        if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
            http_response_code(403); // Forbidden
            $response['message'] = 'CSRF token validation failed.';
            echo json_encode($response);
            exit;
        }
    }
    
    // Handle the action
    switch ($action) {
        case 'create':
            handleCreateAction($db, $response);
            break;
            
        case 'update':
            handleUpdateAction($db, $response);
            break;
            
        case 'delete':
            handleDeleteAction($db, $response);
            break;
        
        case 'add_class':
            handleAddClassAction($db, $response);
            break;
            
        case 'get_classes':
            handleGetClassesAction($db, $response);
            break;
            
        default:
            $response['message'] = 'Invalid action specified';
            break;
    }
}

/**
 * Handle create action for subjects
 */
function handleCreateAction($db, &$response) {
    // Get all form data and sanitize
    $code = isset($_POST['code']) ? sanitize($_POST['code']) : '';
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $teacher_id = isset($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 0;
    $class = isset($_POST['class']) ? sanitize($_POST['class']) : '';
    $kkm = isset($_POST['kkm']) ? (float)$_POST['kkm'] : 70.00;
    $academic_year = isset($_POST['academic_year']) ? sanitize($_POST['academic_year']) : '';
    
    // Basic validation
    $errors = [];
    
    if (empty($code)) {
        $errors[] = 'Kode mata pelajaran harus diisi';
    } elseif (strlen($code) > 10) {
        $errors[] = 'Kode mata pelajaran maksimal 10 karakter';
    }
    
    if (empty($name)) {
        $errors[] = 'Nama mata pelajaran harus diisi';
    } elseif (strlen($name) > 100) {
        $errors[] = 'Nama mata pelajaran maksimal 100 karakter';
    }
    
    if (empty($class)) {
        $errors[] = 'Silahkan menambahkan Kelas baru, pada kolom kelas baru';
    } elseif (strlen($class) > 20) {
        $errors[] = 'Kelas maksimal 20 karakter';
    }
    
    // Check if subject code already exists for this class
    if (!empty($code) && !empty($class)) {
        try {
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE code = ? AND class = ?");
            $checkStmt->execute([$code, $class]);
            
            if ($checkStmt->fetchColumn() > 0) {
                $errors[] = 'Kode mata pelajaran sudah digunakan untuk kelas ini';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan saat memeriksa kode mata pelajaran';
            logError('API check subject code error: ' . $e->getMessage());
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO subjects (code, name, description, teacher_id, class, kkm, academic_year) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            $result = $stmt->execute([
                $code,
                $name,
                $description,
                $teacher_id ?: null,
                $class,
                $kkm,
                $academic_year
            ]);
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Mata pelajaran berhasil ditambahkan';
                $response['id'] = $db->lastInsertId();
            } else {
                $response['success'] = false;
                $response['message'] = 'Gagal menambahkan mata pelajaran';
            }
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = 'Terjadi kesalahan database: ' . $e->getMessage();
            logError('API create subject error: ' . $e->getMessage());
        }
    } else {
        $response['success'] = false;
        $response['message'] = implode(', ', $errors);
    }
}

/**
 * Handle delete action for subjects
 */
function handleDeleteAction($db, &$response) {
    // Get the subject ID
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    if (!$id) {
        $response['message'] = 'Invalid subject ID';
        return;
    }
    
    try {
        // First, check if the subject exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() == 0) {
            $response['message'] = 'Subject not found';
            return;
        }
        
        // TODO: Check for related records that might prevent deletion
        // For example, grades, assessments, etc.
        
        // Perform the deletion
        $stmt = $db->prepare("DELETE FROM subjects WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Subject deleted successfully';
            
            // Handle redirect for non-AJAX requests
            $return_url = isset($_POST['return_url']) ? $_POST['return_url'] : '../modules/subjects/index.php';
            
            // If this is a direct form submission (not AJAX), redirect immediately
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') {
                // Add success message to session flash
                $_SESSION['flash_message'] = 'Subject deleted successfully';
                $_SESSION['flash_type'] = 'success';
                
                // Redirect and exit
                header('Location: ' . $return_url);
                exit;
            }
            
            // Otherwise include redirect URL in JSON response for AJAX handling
            $response['redirect'] = $return_url;
        } else {
            $response['message'] = 'Failed to delete subject';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error';
        logError('API subjects DELETE error: ' . $e->getMessage());
    }
}

/**
 * Handle add class action for subjects
 */
function handleAddClassAction($db, &$response) {
    // Get the subject ID and class
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $class = isset($_POST['class']) ? sanitize($_POST['class']) : '';
    
    if (!$id || !$class) {
        $response['message'] = 'Invalid subject ID or class';
        return;
    }
    
    try {
        // First, check if the subject exists
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE id = ?");
        $checkStmt->execute([$id]);
        
        if ($checkStmt->fetchColumn() == 0) {
            $response['message'] = 'Subject not found';
            return;
        }
        
        // Update the subject with the new class
        $stmt = $db->prepare("UPDATE subjects SET class = ? WHERE id = ?");
        $result = $stmt->execute([$class, $id]);
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = 'Class added successfully';
        } else {
            $response['message'] = 'Failed to add class';
        }
    } catch (PDOException $e) {
        $response['message'] = 'Database error';
        logError('API subjects ADD CLASS error: ' . $e->getMessage());
    }
}

/**
 * Handle get classes action for subjects
 */
function handleGetClassesAction($db, &$response) {
    try {
        // Fetch all classes
        $stmt = $db->prepare("SELECT DISTINCT class FROM subjects");
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $response['success'] = true;
        $response['message'] = 'Classes retrieved successfully';
        $response['data'] = $classes;
    } catch (PDOException $e) {
        $response['message'] = 'Database error';
        logError('API subjects GET CLASSES error: ' . $e->getMessage());
    }
}
?>
