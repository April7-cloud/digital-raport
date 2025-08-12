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

// Function to validate academic year
function validateAcademicYear($year) {
    return preg_match('/^\d{4}\/\d{4}$/', $year) === 1;
}

// Function to validate semester
function validateSemester($semester) {
    return in_array($semester, ['1', '2'], true);
}

// Initialize variables
$errors = [];
$successMessage = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

// Check if class is specified
if (!isset($_GET['kelas']) || empty(trim($_GET['kelas']))) {
    $errors[] = 'Parameter kelas tidak valid';
}

if (!isset($_GET['subject_id']) || !is_numeric($_GET['subject_id'])) {
    $errors[] = 'Mata pelajaran tidak valid';
}

if (!isset($_GET['semester']) || !validateSemester($_GET['semester'])) {
    $errors[] = 'Semester harus 1 atau 2';
}

if (!isset($_GET['academic_year']) || !validateAcademicYear($_GET['academic_year'])) {
    $errors[] = 'Tahun ajaran tidak valid. Format yang benar: YYYY/YYYY';
}

// Only proceed with database operations if no validation errors
if (empty($errors)) {
    $kelas = trim($_GET['kelas']);
    $subjectId = (int)$_GET['subject_id'];
    $semester = $_GET['semester'];
    $academicYear = $_GET['academic_year'];
    
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
        
        if (empty($students)) {
            $errors[] = 'Tidak ada siswa di kelas ini';
        } else {
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
        }
        
    } catch (PDOException $e) {
        error_log('Database error in penilaian/view.php: ' . $e->getMessage());
        $errors[] = 'Terjadi kesalahan saat mengambil data. Silakan coba lagi nanti.';
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Display success message if exists
if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="icon fas fa-check"></i> <?= htmlspecialchars($successMessage) ?>
    </div>
<?php endif; ?>

<?php 
// Display class name matching note if exists
if (isset($_SESSION['class_match_note'])) {
    echo $_SESSION['class_match_note'];
    unset($_SESSION['class_match_note']); // Clear after display
}
?>

<!-- Display errors if any -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h5><i class="icon fas fa-ban"></i> Terjadi kesalahan!</h5>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php if (count($errors) > 3): ?>
        <div class="alert alert-warning">
            <i class="icon fas fa-exclamation-triangle"></i>
            Terdapat beberapa masalah dengan data yang diminta. 
            <a href="javascript:history.back()" class="alert-link">Kembali ke halaman sebelumnya</a>
        </div>
    <?php endif; ?>
    <?php exit; // Stop further execution if there are errors ?>
<?php endif; ?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Rekap Nilai - Kelas <?= htmlspecialchars($kelas) ?></h1>
        <div>
            <a href="input.php?<?= http_build_query([
                'kelas' => $kelas, 
                'subject_id' => $subjectId, 
                'semester' => $semester, 
                'academic_year' => $academicYear
            ]) ?>" class="btn btn-success mr-2">
                <i class="fas fa-edit"></i> Input Nilai
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['print' => 1])) ?>" 
               class="btn btn-primary mr-2" target="_blank">
                <i class="fas fa-print"></i> Cetak
            </a>
            <a href="export_excel.php?<?= http_build_query([
                'kelas' => $kelas, 
                'subject_id' => $subjectId, 
                'semester' => $semester, 
                'academic_year' => $academicYear
            ]) ?>" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <input type="hidden" name="kelas" value="<?= htmlspecialchars($kelas) ?>">
                
                <div class="form-group mb-2 mr-2">
                    <label for="subject_id" class="mr-2">Mata Pelajaran</label>
                    <select name="subject_id" id="subject_id" class="form-control" required>
                        <option value="">Pilih Mata Pelajaran</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" 
                                <?= $subjectId == $subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-2 mr-2">
                    <label for="semester" class="mr-2">Semester</label>
                    <select name="semester" id="semester" class="form-control">
                        <?php foreach ($semesters as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $semester == $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mb-2 mr-2">
                    <label for="academic_year" class="mr-2">Tahun Ajaran</label>
                    <select name="academic_year" id="academic_year" class="form-control">
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?= $year ?>" <?= $academicYear == $year ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary mb-2">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
            </form>
        </div>
    </div>

    <?php if ($subjectId > 0 && $selectedSubject): ?>
        <!-- Subject Info -->
        <div class="card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">
                    <?= htmlspecialchars($selectedSubject['name']) ?> - 
                    <?= htmlspecialchars($semesters[$semester]) ?> 
                    (<?= htmlspecialchars($academicYear) ?>)
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0" id="gradesTable">
                        <thead class="thead-light">
                            <tr>
                                <th>No</th>
                                <th>NISN</th>
                                <th>Nama Siswa</th>
                                <th class="text-center">Nilai Akhir</th>
                                <th class="text-center">Predikat</th>
                                <th>Deskripsi</th>
                                <th>Keterampilan</th>
                                <th>Capaian Kompetensi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($students) > 0): ?>
                                <?php foreach ($students as $index => $student): 
                                    $gradeData = $grades[$student['id']] ?? null;
                                    $gradeValue = $gradeData['grade'] ?? null;
                                    $predikat = $gradeData['predikat'] ?? null;
                                    
                                    // Set row color based on grade value compared to KKM
                                    $rowClass = '';
                                    if ($gradeValue !== null && isset($selectedSubject['kkm'])) {
                                        $rowClass = ($gradeValue >= $selectedSubject['kkm']) ? 'table-success' : 'table-danger';
                                    }
                                ?>
                                    <tr class="<?= $rowClass ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($student['nisn'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td class="text-center font-weight-bold">
                                            <?php if ($gradeValue !== null): ?>
                                                <?= number_format($gradeValue, 2) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($predikat): ?>
                                                <span class="badge badge-<?= getPridekatBadgeClass($predikat) ?>"><?= htmlspecialchars($predikat) ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $gradeData['deskripsi'] ? nl2br(htmlspecialchars($gradeData['deskripsi'])) : '-' ?></td>
                                        <td><?= $gradeData['keterampilan'] ? nl2br(htmlspecialchars($gradeData['keterampilan'])) : '-' ?></td>
                                        <td><?= $gradeData['competency_achievement'] ? nl2br(htmlspecialchars($gradeData['competency_achievement'])) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="py-4">
                                            <img src="<?= BASE_URL ?>/assets/img/empty_data.svg" alt="No Data" style="max-height: 150px;">
                                            <h4 class="text-gray-500 mt-3">Tidak Ada Data Nilai</h4>
                                            <p class="text-gray-500 mb-3">Belum ada data nilai untuk kelas ini.</p>
                                            <a href="input.php?<?= http_build_query(['kelas' => $kelas, 'subject_id' => $subjectId, 'semester' => $semester, 'academic_year' => $academicYear]) ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Input Nilai
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($students) > 0): ?>
                    <div class="mt-3">
                        <a href="input.php?<?= http_build_query(['kelas' => $kelas, 'subject_id' => $subjectId, 'semester' => $semester, 'academic_year' => $academicYear]) ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Nilai
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Show loading state on export/print
    $('.btn-export, .btn-print').on('click', function() {
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        
        // Re-enable button after 3 seconds in case of failure
        setTimeout(function() {
            $btn.prop('disabled', false).html(originalText);
        }, 3000);
    });
    
    // Confirm before deleting
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus data nilai ini?')) {
            e.preventDefault();
            return false;
        }
        return true;
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
