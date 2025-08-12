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
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
$journalId = isset($_GET['journal_id']) ? (int)$_GET['journal_id'] : 0;

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$errors = [];
$success = false;
$student = [];
$subject = [];
$journalData = [];
$assignments = [];

// Default current date and time
$currentDate = date('Y-m-d');
$currentTime = date('H:i');

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
    $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject) {
        $errors[] = 'Mata pelajaran tidak ditemukan';
    }
    
    // If editing existing journal entry, fetch the data
    if ($journalId > 0) {
        $stmt = $db->prepare("SELECT * FROM journal_entries WHERE id = ? AND student_id = ? AND subject_id = ?");
        $stmt->execute([$journalId, $studentId, $subjectId]);
        $journalData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($journalData) {
            $currentDate = $journalData['entry_date'];
            $currentTime = $journalData['entry_time'];
            
            // Get assignments
            $stmt = $db->prepare("SELECT * FROM journal_assignments WHERE journal_id = ? ORDER BY id ASC");
            $stmt->execute([$journalId]);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Process form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_journal'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $errors[] = 'Invalid CSRF token. Please refresh the page and try again.';
        } else {
            // Validate inputs
            $entryDate = $_POST['entry_date'] ?? '';
            $entryTime = $_POST['entry_time'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            if (empty($entryDate)) {
                $errors[] = 'Tanggal kegiatan harus diisi';
            }
            
            if (empty($entryTime)) {
                $errors[] = 'Waktu kegiatan harus diisi';
            }
            
            // Process assignments
            $assignmentNames = $_POST['assignment_name'] ?? [];
            $assignmentScores = $_POST['assignment_score'] ?? [];
            $assignmentMaxScores = $_POST['assignment_max_score'] ?? [];
            $assignmentDates = $_POST['assignment_date'] ?? [];
            
            $assignmentsToSave = [];
            
            for ($i = 0; $i < count($assignmentNames); $i++) {
                if (empty($assignmentNames[$i])) continue;
                
                $score = !empty($assignmentScores[$i]) ? (float)$assignmentScores[$i] : null;
                $maxScore = !empty($assignmentMaxScores[$i]) ? (float)$assignmentMaxScores[$i] : 100;
                $assignmentDate = !empty($assignmentDates[$i]) ? $assignmentDates[$i] : $entryDate;
                
                $assignmentsToSave[] = [
                    'name' => $assignmentNames[$i],
                    'score' => $score,
                    'max_score' => $maxScore,
                    'date' => $assignmentDate
                ];
            }
            
            if (empty($assignmentsToSave)) {
                $errors[] = 'Setidaknya satu tugas harus diisi';
            }
            
            // If no errors, save the data
            if (empty($errors)) {
                $db->beginTransaction();
                
                try {
                    if ($journalId > 0) {
                        // Update existing journal entry
                        $stmt = $db->prepare("
                            UPDATE journal_entries SET 
                            entry_date = ?, 
                            entry_time = ?, 
                            notes = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([$entryDate, $entryTime, $notes, $journalId]);
                        
                        // Delete existing assignments
                        $stmt = $db->prepare("DELETE FROM journal_assignments WHERE journal_id = ?");
                        $stmt->execute([$journalId]);
                        
                        $savedJournalId = $journalId;
                    } else {
                        // Create new journal entry
                        $stmt = $db->prepare("
                            INSERT INTO journal_entries 
                            (student_id, subject_id, class, semester, academic_year, entry_date, entry_time, notes)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $studentId,
                            $subjectId,
                            $kelas,
                            $semester,
                            $academicYear,
                            $entryDate,
                            $entryTime,
                            $notes
                        ]);
                        
                        $savedJournalId = $db->lastInsertId();
                    }
                    
                    // Insert assignments
                    foreach ($assignmentsToSave as $assignment) {
                        $stmt = $db->prepare("
                            INSERT INTO journal_assignments
                            (journal_id, assignment_name, score, max_score, assignment_date)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $savedJournalId,
                            $assignment['name'],
                            $assignment['score'],
                            $assignment['max_score'],
                            $assignment['date']
                        ]);
                    }
                    
                    $db->commit();
                    $success = true;
                    
                    // Set success message and redirect
                    $_SESSION['success_message'] = 'Data jurnal berhasil disimpan';
                    header('Location: view.php?kelas=' . urlencode($kelas) . '&subject_id=' . $subjectId . '&semester=' . $semester . '&academic_year=' . urlencode($academicYear));
                    exit;
                    
                } catch (PDOException $e) {
                    $db->rollBack();
                    $errors[] = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
                    error_log('Database error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
                }
            }
        }
    }
    
} catch (PDOException $e) {
    $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
    error_log('Database error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<?php
// Include page header
$pageTitle = 'Jurnal Kelas';
include_once BASE_PATH . '/includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Jurnal Kelas</h1>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="fas fa-exclamation-triangle"></i> Terdapat kesalahan:</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="fas fa-check-circle"></i> Sukses!</h5>
            Data jurnal berhasil disimpan.
            <div class="mt-2">
                <a href="view.php?kelas=<?= urlencode($kelas) ?>&subject_id=<?= $subjectId ?>&semester=<?= $semester ?>&academic_year=<?= urlencode($academicYear) ?>" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-list"></i> Lihat Daftar Jurnal
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Jurnal Kelas</h6>
                </div>
                <div class="card-body">
                    <form method="post" id="journalForm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <!-- Student Info -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Nama Siswa</label>
                            <div class="col-sm-9">
                                <p class="form-control-static"><?= htmlspecialchars($student['name'] ?? '-') ?></p>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">NISN</label>
                            <div class="col-sm-9">
                                <p class="form-control-static"><?= htmlspecialchars($student['nisn'] ?? '-') ?></p>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Kelas</label>
                            <div class="col-sm-9">
                                <p class="form-control-static"><?= htmlspecialchars($kelas) ?></p>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Mata Pelajaran</label>
                            <div class="col-sm-9">
                                <p class="form-control-static"><?= htmlspecialchars($subject['name'] ?? '-') ?></p>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Semester</label>
                            <div class="col-sm-9">
                                <p class="form-control-static"><?= $semester == '1' ? 'Semester 1' : 'Semester 2' ?></p>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Tahun Ajaran</label>
                            <div class="col-sm-9">
                                <p class="form-control-static"><?= htmlspecialchars($academicYear) ?></p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Journal Details -->
                        <div class="form-group row">
                            <label for="entry_date" class="col-sm-3 col-form-label">Tanggal <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    </div>
                                    <input type="date" class="form-control" id="entry_date" name="entry_date" 
                                           value="<?= htmlspecialchars($currentDate) ?>" required>
                                    <div class="invalid-feedback">Harap masukkan tanggal kegiatan.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="entry_time" class="col-sm-3 col-form-label">Waktu <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                    </div>
                                    <input type="time" class="form-control" id="entry_time" name="entry_time" 
                                           value="<?= htmlspecialchars($currentTime) ?>" required>
                                    <div class="invalid-feedback">Harap masukkan waktu kegiatan.</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <!-- Dynamic Assignments Section -->
                        <div class="card mb-3 bg-light">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-tasks"></i> Daftar Tugas</h5>
                            </div>
                            <div class="card-body">
                                <div id="assignments-container">
                                    <?php 
                                    if (!empty($assignments)) {
                                        foreach ($assignments as $index => $assignment) {
                                            ?>
                                            <div class="assignment-entry card mb-3">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-11">
                                                            <div class="form-group row">
                                                                <label class="col-sm-3 col-form-label">Nama Tugas <span class="text-danger">*</span></label>
                                                                <div class="col-sm-9">
                                                                    <input type="text" class="form-control" name="assignment_name[]" 
                                                                           value="<?= htmlspecialchars($assignment['assignment_name']) ?>" required>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="form-row">
                                                                <div class="form-group col-md-4">
                                                                    <label>Nilai</label>
                                                                    <input type="number" class="form-control" name="assignment_score[]" 
                                                                           value="<?= $assignment['score'] !== null ? htmlspecialchars($assignment['score']) : '' ?>" 
                                                                           min="0" max="100" step="0.01">
                                                                    <small class="form-text text-muted">Opsional, dapat diisi kemudian</small>
                                                                </div>
                                                                <div class="form-group col-md-4">
                                                                    <label>Nilai Maksimal <span class="text-danger">*</span></label>
                                                                    <input type="number" class="form-control" name="assignment_max_score[]" 
                                                                           value="<?= htmlspecialchars($assignment['max_score']) ?>" 
                                                                           min="0" step="0.01" required>
                                                                </div>
                                                                <div class="form-group col-md-4">
                                                                    <label>Tanggal Tugas</label>
                                                                    <input type="date" class="form-control" name="assignment_date[]" 
                                                                           value="<?= htmlspecialchars($assignment['assignment_date']) ?>">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <button type="button" class="btn btn-danger btn-sm remove-assignment">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        // Add one empty assignment entry by default
                                        ?>
                                        <div class="assignment-entry card mb-3">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-11">
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Nama Tugas <span class="text-danger">*</span></label>
                                                            <div class="col-sm-9">
                                                                <input type="text" class="form-control" name="assignment_name[]" required>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="form-row">
                                                            <div class="form-group col-md-4">
                                                                <label>Nilai</label>
                                                                <input type="number" class="form-control" name="assignment_score[]" 
                                                                       min="0" max="100" step="0.01">
                                                                <small class="form-text text-muted">Opsional, dapat diisi kemudian</small>
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Nilai Maksimal <span class="text-danger">*</span></label>
                                                                <input type="number" class="form-control" name="assignment_max_score[]" 
                                                                       value="100" min="0" step="0.01" required>
                                                            </div>
                                                            <div class="form-group col-md-4">
                                                                <label>Tanggal Tugas</label>
                                                                <input type="date" class="form-control" name="assignment_date[]" 
                                                                       value="<?= htmlspecialchars($currentDate) ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1">
                                                        <button type="button" class="btn btn-danger btn-sm remove-assignment">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                
                                <div class="form-group mt-3">
                                    <button type="button" id="add-assignment" class="btn btn-info btn-sm">
                                        <i class="fas fa-plus"></i> Tambah Tugas
                                    </button>
                                    <small class="form-text text-muted">Klik tombol untuk menambahkan tugas baru.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="notes" class="col-sm-3 col-form-label">Catatan untuk siswa</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="notes" name="notes" rows="4"><?= htmlspecialchars($journalData['notes'] ?? '') ?></textarea>
                                <small class="form-text text-muted">Catatan tambahan untuk siswa (opsional).</small>
                            </div>
                        </div>
                        
                        <div class="form-group row mt-4">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" name="save_journal" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <a href="index.php?kelas=<?= urlencode($kelas) ?>&subject_id=<?= $subjectId ?>&semester=<?= $semester ?>&academic_year=<?= urlencode($academicYear) ?>" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times"></i> Batal
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
// Add assignment entry
document.getElementById('add-assignment').addEventListener('click', function() {
    const container = document.getElementById('assignments-container');
    const entryIndex = document.querySelectorAll('.assignment-entry').length;
    const today = new Date().toISOString().split('T')[0];
    
    const entryDiv = document.createElement('div');
    entryDiv.className = 'assignment-entry card mb-3';
    entryDiv.innerHTML = `
        <div class="card-body">
            <div class="row">
                <div class="col-md-11">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label">Nama Tugas <span class="text-danger">*</span></label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" name="assignment_name[]" required placeholder="Masukkan nama tugas">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Nilai</label>
                            <input type="number" class="form-control" name="assignment_score[]" 
                                   min="0" max="100" step="0.01" placeholder="0-100">
                            <small class="form-text text-muted">Opsional, dapat diisi kemudian</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Nilai Maksimal <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="assignment_max_score[]" 
                                   value="100" min="0" step="0.01" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Tanggal Tugas</label>
                            <input type="date" class="form-control" name="assignment_date[]" 
                                   value="${today}">
                        </div>
                    </div>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm remove-assignment">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(entryDiv);
    
    // Add event listener to the new remove button
    const newRemoveButton = entryDiv.querySelector('.remove-assignment');
    newRemoveButton.addEventListener('click', function() {
        container.removeChild(entryDiv);
        
        // Check if there's at least one assignment entry
        if (document.querySelectorAll('.assignment-entry').length === 0) {
            document.getElementById('add-assignment').click();
        }
    });
});

// Remove assignment entry
document.querySelectorAll('.remove-assignment').forEach(function(button) {
    button.addEventListener('click', function() {
        const container = document.getElementById('assignments-container');
        const entry = this.closest('.assignment-entry');
        
        container.removeChild(entry);
        
        // Check if there's at least one assignment entry
        if (document.querySelectorAll('.assignment-entry').length === 0) {
            document.getElementById('add-assignment').click();
        }
    });
});

// Form validation
const journalForm = document.getElementById('journalForm');
journalForm.addEventListener('submit', function(event) {
    let hasError = false;
    
    // Check if at least one assignment is added
    if (document.querySelectorAll('.assignment-entry').length === 0) {
        alert('Harap tambahkan minimal satu tugas.');
        hasError = true;
    }
    
    // Check required fields
    const requiredInputs = journalForm.querySelectorAll('input[required]');
    requiredInputs.forEach(function(input) {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            hasError = true;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    if (hasError) {
        event.preventDefault();
    }
});

// Add validation styling
document.querySelectorAll('input, textarea').forEach(function(element) {
    element.addEventListener('change', function() {
        if (this.hasAttribute('required')) {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        }
    });
});

// Add tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
