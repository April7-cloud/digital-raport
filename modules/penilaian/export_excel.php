<?php
// Define BASE_PATH to prevent 403 error in session.php
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/auth/session.php';
require_once BASE_PATH . '/config/database.php';

// Check permissions
if (!hasRole(['admin', 'guru'])) {
    $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengakses halaman ini';
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

// Validate input parameters
$errors = [];

if (!isset($_GET['kelas']) || empty($_GET['kelas'])) {
    $errors[] = 'Parameter kelas tidak valid';
}

if (!isset($_GET['subject_id']) || !is_numeric($_GET['subject_id'])) {
    $errors[] = 'Parameter subject_id tidak valid';
}

// Function to validate academic year
function validateAcademicYear($year) {
    return preg_match('/^\d{4}\/\d{4}$/', $year);
}

// Function to validate semester
function validateSemester($semester) {
    return in_array($semester, ['1', '2'], true);
}

if (!isset($_GET['semester']) || !validateSemester($_GET['semester'])) {
    $errors[] = 'Semester harus 1 atau 2';
}

if (!isset($_GET['academic_year']) || !validateAcademicYear($_GET['academic_year'])) {
    $errors[] = 'Tahun ajaran tidak valid. Format yang benar: YYYY/YYYY';
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: view.php');
    exit();
}

// Get parameters
$kelas = trim($_GET['kelas']);
$subjectId = (int)$_GET['subject_id'];
$semester = $_GET['semester'];
$academicYear = $_GET['academic_year'];

// Initialize database connection
$database = new Database();
$db = $database->connect();

try {
    // Get subject info
    $stmt = $db->prepare("SELECT s.*, t.name as teacher_name 
                         FROM subjects s
                         LEFT JOIN teachers t ON s.teacher_id = t.id
                         WHERE s.id = ?");
    $stmt->execute([$subjectId]);
    $selectedSubject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$selectedSubject) {
        throw new Exception('Mata pelajaran tidak ditemukan');
    }
    
    // Get students in the class - using case-insensitive and space-insensitive matching
    $stmt = $db->prepare("SELECT s.* FROM students s 
                         WHERE LOWER(TRIM(s.class)) = LOWER(TRIM(?)) 
                         ORDER BY s.name ASC");
    $stmt->execute([$kelas]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no students found with case-insensitive match, try a more flexible search
    if (empty($students)) {
        // Try to find any classes that might match with different spacing/casing
        $stmt = $db->prepare("SELECT DISTINCT class FROM students 
                              WHERE REPLACE(LOWER(class), ' ', '') = REPLACE(LOWER(?), ' ', '')
                              LIMIT 1");
        $stmt->execute([$kelas]);
        $similarClass = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($similarClass) {
            $actualClass = $similarClass['class'];
            
            // Now get students with the actual class name
            $stmt = $db->prepare("SELECT * FROM students 
                                  WHERE class = ? 
                                  ORDER BY name ASC");
            $stmt->execute([$actualClass]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update kelas to use the actual class name in the database
            $kelas = $actualClass;
        }
    }
    
    if (empty($students)) {
        throw new Exception('Tidak ada siswa di kelas ini');
    }
    
    // Get existing grades
    $studentIds = array_column($students, 'id');
    $placeholders = rtrim(str_repeat('?,', count($studentIds)), ',');
    
    $stmt = $db->prepare("SELECT * FROM grades 
                         WHERE student_id IN ($placeholders) 
                         AND subject_id = ? 
                         AND semester = ? 
                         AND academic_year = ?");
    $params = array_merge($studentIds, [$subjectId, $semester, $academicYear]);
    $stmt->execute($params);
    
    $grades = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $grades[$row['student_id']] = $row;
    }
    
    // Get semester name for display
    $semesters = [
        '1' => 'Semester 1',
        '2' => 'Semester 2'
    ];
    $semesterName = $semesters[$semester] ?? 'Unknown';
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Rekap_Nilai_' . str_replace(' ', '_', $kelas) . '_' . str_replace(' ', '_', $selectedSubject['name']) . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Start output buffering
    ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
        }
        th {
            background-color: #f2f2f2;
        }
        .text-center {
            text-align: center;
        }
        .header {
            font-weight: bold;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Rekap Nilai - <?= htmlspecialchars($kelas) ?></h2>
        <p>Mata Pelajaran: <?= htmlspecialchars($selectedSubject['name']) ?></p>
        <p>Guru: <?= htmlspecialchars($selectedSubject['teacher_name'] ?? '-') ?></p>
        <p><?= htmlspecialchars($semesterName) ?> - <?= htmlspecialchars($academicYear) ?></p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Nilai Akhir</th>
                <th>Predikat</th>
                <th>Deskripsi</th>
                <th>Keterampilan</th>
                <th>Capaian Kompetensi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($students as $index => $student): 
                $gradeData = $grades[$student['id']] ?? null;
            ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <td><?= $student['nisn'] ?? '-' ?></td>
                    <td><?= $student['name'] ?></td>
                    <td class="text-center">
                        <?= $gradeData['grade'] ? number_format($gradeData['grade'], 2) : '-' ?>
                    </td>
                    <td class="text-center">
                        <?= $gradeData['predikat'] ?? '-' ?>
                    </td>
                    <td><?= $gradeData['deskripsi'] ?? '-' ?></td>
                    <td><?= $gradeData['keterampilan'] ?? '-' ?></td>
                    <td><?= $gradeData['competency_achievement'] ?? '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>

<?php
    // Send the output buffer and end it
    echo ob_get_clean();
    
} catch (PDOException $e) {
    // Log error and redirect with error message
    error_log('Database error in penilaian/export_excel.php: ' . $e->getMessage());
    $_SESSION['error'] = 'Terjadi kesalahan saat mengambil data. Silakan coba lagi nanti.';
    header('Location: view.php');
    exit();
} catch (Exception $e) {
    // Redirect with error message
    $_SESSION['error'] = $e->getMessage();
    header('Location: view.php');
    exit();
}
?>
