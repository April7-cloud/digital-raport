<?php
// Define BASE_PATH to prevent 403 error in session.php
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/auth/session.php';
require_once BASE_PATH . '/config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Check if user has permission
if (!hasRole(['admin', 'guru'])) {
    $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengakses halaman ini';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Get parameters
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
$academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$kelas = isset($_GET['kelas']) ? $_GET['kelas'] : '';

// Initialize variables
$errors = [];
$success = false;
$student = [];
$subject = [];
$gradeData = [];

// Validate inputs
if ($studentId <= 0 || $subjectId <= 0 || !in_array($semester, ['1', '2']) || empty($academicYear) || empty($kelas)) {
    $errors[] = 'Parameter tidak valid';
}

try {
    // Get student data
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ? AND class = ?");
    $stmt->execute([$studentId, $kelas]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        $errors[] = 'Siswa tidak ditemukan';
    }
    
    // Get subject data
    $stmt = $db->prepare("SELECT s.*, t.name as teacher_name FROM subjects s LEFT JOIN teachers t ON s.teacher_id = t.id WHERE s.id = ?");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject) {
        $errors[] = 'Mata pelajaran tidak ditemukan';
    }
    
    // Get existing grade data if any
    $stmt = $db->prepare("SELECT * FROM grades WHERE student_id = ? AND subject_id = ? AND semester = ? AND academic_year = ?");
    $stmt->execute([$studentId, $subjectId, $semester, $academicYear]);
    $gradeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grade'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $errors[] = 'Invalid CSRF token. Please refresh the page and try again.';
        } else {
            // Validate inputs
            $assignment1 = !empty($_POST['assignment1']) ? (float)$_POST['assignment1'] : null;
            $midExam = !empty($_POST['mid_exam']) ? (float)$_POST['mid_exam'] : null;
            $finalExam = !empty($_POST['final_exam']) ? (float)$_POST['final_exam'] : null;
            $predikat = !empty($_POST['predikat']) ? $_POST['predikat'] : null;
            $deskripsi = !empty($_POST['deskripsi']) ? trim($_POST['deskripsi']) : null;
            $keterampilan = !empty($_POST['keterampilan']) ? trim($_POST['keterampilan']) : null;
            $competencyAchievement = !empty($_POST['competency_achievement']) ? trim($_POST['competency_achievement']) : null;
            
            // Calculate final grade (30% assignment, 30% mid exam, 40% final exam)
            $nilaiAkhir = null;
            if ($assignment1 !== null && $midExam !== null && $finalExam !== null) {
                $nilaiAkhir = ($assignment1 * 0.3) + ($midExam * 0.3) + ($finalExam * 0.4);
            }
            
            // Auto-generate deskripsi if empty
            if (empty($deskripsi) && $predikat) {
                switch($predikat) {
                    case 'A': $deskripsi = 'Sangat baik dan sempurna'; break;
                    case 'B': $deskripsi = 'Baik dan perlu sedikit peningkatan'; break;
                    case 'C': $deskripsi = 'Cukup dan perlu peningkatan'; break;
                    case 'D': $deskripsi = 'Perlu bimbingan khusus'; break;
                }
            }
            
            // Prepare data for database
            $data = [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'semester' => $semester,
                'academic_year' => $academicYear,
                'assignment1' => $assignment1,
                'mid_exam' => $midExam,
                'final_exam' => $finalExam,
                'nilai_akhir' => $nilaiAkhir,
                'predikat' => $predikat,
                'deskripsi' => $deskripsi,
                'keterampilan' => $keterampilan,
                'competency_achievement' => $competencyAchievement
            ];
            
            // Save to database
            $db->beginTransaction();
            
            try {
                if ($gradeData) {
                    // Update existing grade
                    $sql = "UPDATE grades SET 
                            assignment1 = :assignment1,
                            mid_exam = :mid_exam,
                            final_exam = :final_exam,
                            nilai_akhir = :nilai_akhir,
                            predikat = :predikat,
                            deskripsi = :deskripsi,
                            keterampilan = :keterampilan,
                            competency_achievement = :competency_achievement,
                            updated_at = NOW()
                            WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $data['id'] = $gradeData['id'];
                } else {
                    // Insert new grade
                    $sql = "INSERT INTO grades (
                            student_id, subject_id, semester, academic_year,
                            assignment1, mid_exam, final_exam, nilai_akhir,
                            predikat, deskripsi, keterampilan, competency_achievement,
                            created_at, updated_at
                        ) VALUES (
                            :student_id, :subject_id, :semester, :academic_year,
                            :assignment1, :mid_exam, :final_exam, :nilai_akhir,
                            :predikat, :deskripsi, :keterampilan, :competency_achievement,
                            NOW(), NOW()
                        )";
                    $stmt = $db->prepare($sql);
                }
                
                $stmt->execute($data);
                $db->commit();
                
                $_SESSION['success_message'] = 'Data nilai berhasil disimpan';
                header("Location: input.php?" . http_build_query([
                    'kelas' => $kelas,
                    'subject_id' => $subjectId,
                    'semester' => $semester,
                    'academic_year' => $academicYear
                ]));
                exit;
                
            } catch (PDOException $e) {
                $db->rollBack();
                $errors[] = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
                error_log('Database error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
        }
    }
    
} catch (PDOException $e) {
    $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
    error_log('Database error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Input Nilai Siswa</h1>
        <div>
            <a href="input.php?<?= http_build_query([
                'kelas' => $kelas,
                'subject_id' => $subjectId,
                'semester' => $semester,
                'academic_year' => $academicYear
            ]) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
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

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Input Nilai</h6>
                </div>
                <div class="card-body">
                    <form method="post" id="gradeForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <!-- Student Info -->
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Nama Siswa</label>
                            <div class="col-sm-8">
                                <input type="text" readonly class="form-control-plaintext" value="<?= htmlspecialchars($student['name']) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">NISN</label>
                            <div class="col-sm-8">
                                <input type="text" readonly class="form-control-plaintext" value="<?= htmlspecialchars($student['nisn'] ?? '-') ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Kelas</label>
                            <div class="col-sm-8">
                                <input type="text" readonly class="form-control-plaintext" value="<?= htmlspecialchars($kelas) ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Mata Pelajaran</label>
                            <div class="col-sm-8">
                                <input type="text" readonly class="form-control-plaintext" value="<?= htmlspecialchars($subject['name'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Semester</label>
                            <div class="col-sm-8">
                                <input type="text" readonly class="form-control-plaintext" value="<?= $semester ?>">
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Tahun Ajaran</label>
                            <div class="col-sm-8">
                                <input type="text" readonly class="form-control-plaintext" value="<?= htmlspecialchars($academicYear) ?>">
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Grade Inputs -->
                        <div class="form-group row">
                            <label for="assignment1" class="col-sm-4 col-form-label">Nilai Tugas</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="assignment1" name="assignment1" 
                                           min="0" max="100" step="0.01" 
                                           value="<?= htmlspecialchars($gradeData['assignment1'] ?? '') ?>" 
                                           onchange="calculateGrade()">
                                    <div class="input-group-append">
                                        <span class="input-group-text">/ 100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="mid_exam" class="col-sm-4 col-form-label">Nilai UTS</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="mid_exam" name="mid_exam" 
                                           min="0" max="100" step="0.01" 
                                           value="<?= htmlspecialchars($gradeData['mid_exam'] ?? '') ?>" 
                                           onchange="calculateGrade()">
                                    <div class="input-group-append">
                                        <span class="input-group-text">/ 100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="final_exam" class="col-sm-4 col-form-label">Nilai UAS</label>
                            <div class="col-sm-8">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="final_exam" name="final_exam" 
                                           min="0" max="100" step="0.01" 
                                           value="<?= htmlspecialchars($gradeData['final_exam'] ?? '') ?>" 
                                           onchange="calculateGrade()">
                                    <div class="input-group-append">
                                        <span class="input-group-text">/ 100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Nilai Akhir</label>
                            <div class="col-sm-8">
                                <input type="text" id="nilai_akhir" class="form-control-plaintext font-weight-bold" readonly>
                                <small class="form-text text-muted">(30% Tugas + 30% UTS + 40% UAS)</small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="predikat" class="col-sm-4 col-form-label">Predikat</label>
                            <div class="col-sm-8">
                                <select class="form-control" id="predikat" name="predikat" onchange="updateDeskripsi()">
                                    <option value="">Pilih Predikat</option>
                                    <option value="A" <?= (isset($gradeData['predikat']) && $gradeData['predikat'] === 'A') ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= (isset($gradeData['predikat']) && $gradeData['predikat'] === 'B') ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= (isset($gradeData['predikat']) && $gradeData['predikat'] === 'C') ? 'selected' : '' ?>>C</option>
                                    <option value="D" <?= (isset($gradeData['predikat']) && $gradeData['predikat'] === 'D') ? 'selected' : '' ?>>D</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="deskripsi" class="col-sm-4 col-form-label">Deskripsi</label>
                            <div class="col-sm-8">
                                <textarea class="form-control" id="deskripsi" name="deskripsi" rows="2"><?= htmlspecialchars($gradeData['deskripsi'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="keterampilan" class="col-sm-4 col-form-label">Keterampilan</label>
                            <div class="col-sm-8">
                                <textarea class="form-control" id="keterampilan" name="keterampilan" rows="2"><?= htmlspecialchars($gradeData['keterampilan'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="competency_achievement" class="col-sm-4 col-form-label">Capaian Kompetensi</label>
                            <div class="col-sm-8">
                                <textarea class="form-control" id="competency_achievement" name="competency_achievement" rows="3"><?= htmlspecialchars($gradeData['competency_achievement'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-group row mt-4">
                            <div class="col-sm-8 offset-sm-4">
                                <button type="submit" name="save_grade" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Simpan Nilai
                                </button>
                                <a href="input.php?<?= http_build_query([
                                    'kelas' => $kelas,
                                    'subject_id' => $subjectId,
                                    'semester' => $semester,
                                    'academic_year' => $academicYear
                                ]) ?>" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times mr-2"></i>Batal
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Calculate final grade
function calculateGrade() {
    const assignment1 = parseFloat(document.getElementById('assignment1').value) || 0;
    const midExam = parseFloat(document.getElementById('mid_exam').value) || 0;
    const finalExam = parseFloat(document.getElementById('final_exam').value) || 0;
    
    // Calculate final grade (30% assignment, 30% mid exam, 40% final exam)
    const finalGrade = (assignment1 * 0.3) + (midExam * 0.3) + (finalExam * 0.4);
    
    // Update the final grade field
    document.getElementById('nilai_akhir').value = finalGrade.toFixed(2);
    
    // Update predikat based on final grade
    const predikatSelect = document.getElementById('predikat');
    if (predikatSelect.value === '') {  // Only auto-update if predikat is not manually set
        if (finalGrade >= 85) {
            predikatSelect.value = 'A';
        } else if (finalGrade >= 70) {
            predikatSelect.value = 'B';
        } else if (finalGrade >= 55) {
            predikatSelect.value = 'C';
        } else if (finalGrade > 0) {
            predikatSelect.value = 'D';
        }
        
        // Update deskripsi based on predikat
        updateDeskripsi();
    }
}

// Update deskripsi based on selected predikat
function updateDeskripsi() {
    const predikat = document.getElementById('predikat').value;
    const deskripsiField = document.getElementById('deskripsi');
    
    // Only update if deskripsi is empty or matches one of the auto-generated values
    const currentDeskripsi = deskripsiField.value.trim();
    const autoDeskripsi = [
        'Sangat baik dan sempurna',
        'Baik dan perlu sedikit peningkatan',
        'Cukup dan perlu peningkatan',
        'Perlu bimbingan khusus'
    ];
    
    if (currentDeskripsi === '' || autoDeskripsi.includes(currentDeskripsi)) {
        let deskripsi = '';
        
        switch(predikat) {
            case 'A': deskripsi = 'Sangat baik dan sempurna'; break;
            case 'B': deskripsi = 'Baik dan perlu sedikit peningkatan'; break;
            case 'C': deskripsi = 'Cukup dan perlu peningkatan'; break;
            case 'D': deskripsi = 'Perlu bimbingan khusus'; break;
        }
        
        deskripsiField.value = deskripsi;
    }
}

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial nilai akhir if grade data exists
    const gradeData = <?= json_encode($gradeData ?? []) ?>;
    if (gradeData && gradeData.nilai_akhir) {
        document.getElementById('nilai_akhir').value = parseFloat(gradeData.nilai_akhir).toFixed(2);
    } else {
        // Calculate initial grade if inputs have values
        calculateGrade();
    }
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
