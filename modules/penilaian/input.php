<?php
// Define BASE_PATH to prevent 403 error in session.php
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/auth/session.php';
require_once BASE_PATH . '/config/database.php';

// Check if user has permission
if (!hasRole(['admin', 'guru'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Check if class is specified
if (!isset($_GET['kelas']) || empty($_GET['kelas'])) {
    header('Location: index.php');
    exit;
}

// Clean and normalize the class parameter
$kelasRaw = $_GET['kelas'];
$kelas = trim(htmlspecialchars($kelasRaw)); // Trim whitespace and sanitize

// Get academic years for dropdown
$academicYears = [];
$currentYear = date('Y');
for ($i = 0; $i < 5; $i++) {
    $year = ($currentYear - 2 + $i) . '/' . ($currentYear - 1 + $i);
    $academicYears[] = $year;
}

$semesters = [
    '1' => 'Semester 1',
    '2' => 'Semester 2'
];

// Get filters from request
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
$academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : $academicYears[2]; // Default to current year

// Initialize variables
$subjects = [];
$selectedSubject = null;
$students = [];
$existingGrades = [];
$saveSuccess = false;
$saveError = '';

function validateGrade($value) {
    if ($value === '') return true; // Allow empty
    if (!is_numeric($value)) return false;
    $grade = (float)$value;
    return $grade >= 0 && $grade <= 100;
}

function validatePredikat($value) {
    return in_array($value, ['', 'A', 'B', 'C', 'D'], true);
}

function validateText($value, $maxLength = 1000) {
    if (empty($value)) return true;
    return mb_strlen($value) <= $maxLength;
}

$errors = [];
$success = false;

try {
    // Get all subjects for this class - using case-insensitive comparison
    $stmt = $db->prepare("SELECT s.id, s.code, s.name, s.kkm, t.name as teacher_name 
                          FROM subjects s
                          LEFT JOIN teachers t ON s.teacher_id = t.id
                          WHERE LOWER(s.class) = LOWER(?) 
                          ORDER BY s.name ASC");
    $stmt->execute([$kelas]);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no subjects found with exact match, try a more flexible search
    if (empty($subjects)) {
        $stmt = $db->prepare("SELECT s.id, s.code, s.name, s.kkm, t.name as teacher_name,
                               s.class as original_class
                               FROM subjects s
                               LEFT JOIN teachers t ON s.teacher_id = t.id
                               WHERE REPLACE(LOWER(s.class), ' ', '') = REPLACE(LOWER(?), ' ', '')
                               ORDER BY s.name ASC");
        $stmt->execute([$kelas]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($subjects)) {
            // We found subjects with similar class names
            $originalClass = $subjects[0]['original_class'];
            // Add a note that we're using a similar class name
            $note = "<div class='alert alert-warning'>
                       <i class='fas fa-info-circle mr-1'></i> 
                       Showing subjects for class '<strong>$originalClass</strong>' instead of '$kelas'.
                     </div>";
            $_SESSION['class_match_note'] = $note;
            // Update kelas to use the actual class name in the database
            $kelas = $originalClass;
        }
    }
    
    // If subject is selected, get its details and students
    if ($subjectId > 0) {
        // Get subject details
        $stmt = $db->prepare("SELECT s.*, t.name as teacher_name 
                             FROM subjects s
                             LEFT JOIN teachers t ON s.teacher_id = t.id
                             WHERE s.id = ?");
        $stmt->execute([$subjectId]);
        $selectedSubject = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get students in this class - using both case-insensitive and space-insensitive matching
        $stmt = $db->prepare("SELECT * FROM students 
                              WHERE LOWER(TRIM(class)) = LOWER(TRIM(?)) 
                              ORDER BY name ASC");
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
                // Add note about the class name difference
                $note = "<div class='alert alert-warning'>
                           <i class='fas fa-info-circle mr-1'></i> 
                           Showing students for class '<strong>$actualClass</strong>' instead of '$kelas'.
                         </div>";
                $_SESSION['class_match_note'] = $note;
                
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
        
        // Get existing grades for the selected subject, semester and year
        $existingGrades = [];
        if (count($students) > 0) {
            $studentIds = array_column($students, 'id');
            $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
            
            $stmt = $db->prepare("SELECT * FROM grades 
                                 WHERE subject_id = ? 
                                 AND student_id IN ($placeholders)
                                 AND semester = ? 
                                 AND academic_year = ?");
            
            $params = array_merge([$subjectId], $studentIds, [$semester, $academicYear]);
            $stmt->execute($params);
            
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Index grades by student_id
            foreach ($grades as $grade) {
                $existingGrades[$grade['student_id']] = $grade;
            }
        }
    }
    
} catch (PDOException $e) {
    $saveError = 'Error: ' . $e->getMessage();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$baseUrl = strtok($currentUrl, '?');
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Input Nilai</h1>
        <div class="d-flex">
            <a href="view.php?<?= http_build_query([
                'kelas' => $kelas,
                'subject_id' => $subjectId,
                'semester' => $semester,
                'academic_year' => $academicYear
            ]) ?>" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
            <a href="<?= $baseUrl ?>" class="btn btn-primary">
                <i class="fas fa-sync-alt mr-2"></i>Refresh
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h5><i class="icon fas fa-ban"></i> Error!</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php 
    // Display class name matching note if exists
    if (isset($_SESSION['class_match_note'])) {
        echo $_SESSION['class_match_note'];
        unset($_SESSION['class_match_note']); // Clear after display
    }
    ?>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form method="get" action="" class="form-inline" id="filterForm">
                <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
                
                <div class="form-group mr-3 mb-3">
                    <label for="subject_id" class="mr-2">Mata Pelajaran:</label>
                    <select name="subject_id" id="subject_id" class="form-control" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subjectId == $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?> 
                                <?php if (isset($subject['teacher_name']) && !empty($subject['teacher_name'])): ?>
                                    (<?= htmlspecialchars($subject['teacher_name']) ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mr-3 mb-3">
                    <label for="semester" class="mr-2">Semester:</label>
                    <select name="semester" id="semester" class="form-control">
                        <?php foreach ($semesters as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $semester == $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mr-3 mb-3">
                    <label for="academic_year" class="mr-2">Tahun Ajaran:</label>
                    <select name="academic_year" id="academic_year" class="form-control">
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>" <?= $academicYear == $year ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary mb-3">
                    <i class="fas fa-filter mr-2"></i>Terapkan Filter
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar Siswa <?= isset($selectedSubject['name']) ? '- ' . htmlspecialchars($selectedSubject['name']) : '' ?>
            </h6>
            <div class="text-muted small">
                Kelas: <?= htmlspecialchars($kelas) ?> | 
                Semester: <?= $semester ?> | 
                Tahun Ajaran: <?= htmlspecialchars($academicYear) ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($subjects)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-book fa-4x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">Tidak ada mata pelajaran untuk kelas ini</h5>
                    <p class="text-muted">Silakan tambahkan mata pelajaran terlebih dahulu</p>
                    <a href="<?= BASE_PATH ?>/modules/subjects/create.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus mr-2"></i>Tambah Mata Pelajaran
                    </a>
                </div>
            <?php elseif ($subjectId == 0): ?>
                <div class="text-center py-5">
                    <i class="fas fa-filter fa-4x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">Silakan pilih mata pelajaran</h5>
                    <p class="text-muted">Gunakan filter di atas untuk memilih mata pelajaran</p>
                </div>
            <?php elseif (empty($students)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-user-graduate fa-4x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">Tidak ada siswa di kelas ini</h5>
                    <p class="text-muted">Silakan tambahkan siswa terlebih dahulu atau pastikan nama kelas sesuai</p>
                    <div class="mt-3">
                        <a href="<?= BASE_PATH ?>/modules/students/create.php" class="btn btn-primary mr-2">
                            <i class="fas fa-plus mr-2"></i>Tambah Siswa
                        </a>
                        <a href="<?= BASE_PATH ?>/modules/students/list.php" class="btn btn-info">
                            <i class="fas fa-list mr-2"></i>Daftar Siswa
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="studentsTable" width="100%" cellspacing="0">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th width="15%" class="text-center">Nilai Akhir</th>
                                <th width="15%" class="text-center">Predikat</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $index => $student): 
                                $gradeData = $existingGrades[$student['id']] ?? null;
                                $hasGrade = $gradeData !== null;
                                $rowClass = $hasGrade ? 'table-success' : '';
                            ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="text-center"><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($student['nisn'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td class="text-center">
                                        <?php if ($hasGrade): ?>
                                            <span class="badge badge-<?= ($gradeData['grade'] >= ($selectedSubject['kkm'] ?? 0)) ? 'success' : 'danger' ?>">
                                                <?= number_format($gradeData['grade'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($hasGrade): ?>
                                            <span class="badge badge-<?= 
                                                $gradeData['predikat'] === 'A' ? 'success' : 
                                                ($gradeData['predikat'] === 'B' ? 'info' : 
                                                ($gradeData['predikat'] === 'C' ? 'warning' : 'danger')) 
                                            ?>">
                                                <?= $gradeData['predikat'] ?: '-' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="input_single.php?<?= http_build_query([
                                            'student_id' => $student['id'],
                                            'subject_id' => $subjectId,
                                            'semester' => $semester,
                                            'academic_year' => $academicYear,
                                            'kelas' => $kelas
                                        ]) ?>" 
                                        class="btn btn-sm btn-<?= $hasGrade ? 'primary' : 'outline-primary' ?>"
                                        title="<?= $hasGrade ? 'Edit Nilai' : 'Input Nilai' ?>">
                                            <i class="fas fa-<?= $hasGrade ? 'edit' : 'plus' ?> mr-1"></i>
                                            <?= $hasGrade ? 'Edit' : 'Input' ?>
                                        </a>
                                        <?php if ($hasGrade): ?>
                                            <button type="button" class="btn btn-sm btn-outline-info ml-1" 
                                                onclick="viewDetails(<?= htmlspecialchars(json_encode([
                                                    'siswa' => $student['name'],
                                                    'grade' => $gradeData['grade'],
                                                    'predikat' => $gradeData['predikat'],
                                                    'deskripsi' => $gradeData['deskripsi'],
                                                    'keterampilan' => $gradeData['keterampilan'],
                                                    'competency_achievement' => $gradeData['competency_achievement']
                                                ])) ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel">Detail Nilai</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Nama Siswa:</div>
                    <div class="col-md-8" id="detailNama"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Nilai Akhir:</div>
                    <div class="col-md-8" id="detailNilai"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Predikat:</div>
                    <div class="col-md-8" id="detailPredikat"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Deskripsi:</div>
                    <div class="col-md-8" id="detailDeskripsi"></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Keterampilan:</div>
                    <div class="col-md-8" id="detailKeterampilan"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 font-weight-bold">Capaian Kompetensi:</div>
                    <div class="col-md-8" id="detailKompetensi"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize DataTable
$(document).ready(function() {
    $('#studentsTable').DataTable({
        "pageLength": 25,
        "order": [[2, 'asc']], // Sort by name
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json"
        },
        "columnDefs": [
            { "orderable": false, "targets": [0, 5] } // Disable sorting on No and Action columns
        ]
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});

// View details in modal
function viewDetails(data) {
    $('#detailNama').text(data.siswa);
    $('#detailNilai').html(`<span class="badge badge-${parseFloat(data.grade) >= <?= $selectedSubject['kkm'] ?? 75 ?> ? 'success' : 'danger'}">${data.grade}</span>`);
    $('#detailPredikat').html(`<span class="badge badge-${
        data.predikat === 'A' ? 'success' : 
        (data.predikat === 'B' ? 'info' : 
        (data.predikat === 'C' ? 'warning' : 'danger'))
    }">${data.predikat || '-'}</span>`);
    $('#detailDeskripsi').text(data.deskripsi || '-');
    $('#detailKeterampilan').text(data.keterampilan || '-');
    $('#detailKompetensi').text(data.competency_achievement || '-');
    
    $('#detailModal').modal('show');
}
</script>

<style>
.table th {
    white-space: nowrap;
    vertical-align: middle;
}
.table td {
    vertical-align: middle;
}
.badge {
    font-size: 0.85em;
    padding: 0.35em 0.65em;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
</style>

<?php require_once '../../includes/footer.php'; ?>
