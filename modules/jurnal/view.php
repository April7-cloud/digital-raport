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

// Get parameters with defaults
$semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
$academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y') . '/' . (date('Y') + 1);
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Set default date range to current month if not specified
if (empty($startDate)) {
    $startDate = date('Y-m-01'); // First day of current month
}

if (empty($endDate)) {
    $endDate = date('Y-m-t'); // Last day of current month
}

// Get subject data
$subject = [];
if ($subjectId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
        $stmt->execute([$subjectId]);
        $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error fetching subject: ' . $e->getMessage();
    }
}

// Get all classes (for filter)
$classes = [];
try {
    $stmt = $db->query("SELECT DISTINCT class FROM students ORDER BY class");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $classes[] = $row['class'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error fetching classes: ' . $e->getMessage();
}

// Get all subjects (for filter)
$subjects = [];
try {
    $stmt = $db->query("SELECT * FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error fetching subjects: ' . $e->getMessage();
}

// Get student data if student_id is provided
$student = [];
if ($studentId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error fetching student: ' . $e->getMessage();
    }
}

// Get journal entries
$entries = [];
$whereConditions = [];
$params = [];

if (!empty($kelas)) {
    // Use case-insensitive comparison for better matching
    $whereConditions[] = "LOWER(REPLACE(j.class, ' ', '')) = LOWER(REPLACE(?, ' ', ''))";
    $params[] = $kelas;
}

if ($subjectId > 0) {
    $whereConditions[] = "j.subject_id = ?";
    $params[] = $subjectId;
}

if ($studentId > 0) {
    $whereConditions[] = "j.student_id = ?";
    $params[] = $studentId;
}

if (!empty($semester)) {
    $whereConditions[] = "j.semester = ?";
    $params[] = $semester;
}

if (!empty($academicYear)) {
    $whereConditions[] = "j.academic_year = ?";
    $params[] = $academicYear;
}

if (!empty($startDate) && !empty($endDate)) {
    $whereConditions[] = "j.entry_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

try {
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    $query = "
        SELECT j.*, s.name as student_name, s.nisn, sub.name as subject_name
        FROM journal_entries j
        LEFT JOIN students s ON j.student_id = s.id
        LEFT JOIN subjects sub ON j.subject_id = sub.id
        $whereClause
        ORDER BY j.entry_date DESC, j.entry_time DESC, s.name ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch assignments for each entry
    foreach ($entries as &$entry) {
        $stmt = $db->prepare("
            SELECT * FROM journal_assignments 
            WHERE journal_id = ? 
            ORDER BY id ASC
        ");
        $stmt->execute([$entry['id']]);
        $entry['assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error fetching journal entries: ' . $e->getMessage();
    error_log('Database error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
}

// Include page header
$pageTitle = 'Jurnal Kelas';
include_once BASE_PATH . '/includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Jurnal Kelas</h1>
        <div>
            <?php if (!empty($kelas) && $subjectId > 0): ?>
                <a href="index.php?kelas=<?= urlencode($kelas) ?>&subject_id=<?= $subjectId ?>&semester=<?= $semester ?>&academic_year=<?= urlencode($academicYear) ?>" 
                   class="btn btn-primary mr-2">
                    <i class="fas fa-user-plus"></i> Tambah Jurnal Siswa
                </a>
            <?php endif; ?>
            
            <a href="export_excel.php?kelas=<?= urlencode($kelas) ?>&subject_id=<?= $subjectId ?>&semester=<?= $semester ?>&academic_year=<?= urlencode($academicYear) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?><?= $studentId ? "&student_id=$studentId" : "" ?>" 
               class="btn btn-success mr-2" target="_blank">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            
            <a href="#" onclick="window.print(); return false;" class="btn btn-info mr-2">
                <i class="fas fa-print"></i> Print
            </a>
            
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-times-circle"></i> <?= htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['warning']) ?>
        </div>
        <?php unset($_SESSION['warning']); ?>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <?php if (empty($studentId)): ?>
                <div class="form-group mr-3 mb-3">
                    <label for="kelas" class="mr-2">Kelas:</label>
                    <select name="kelas" id="kelas" class="form-control">
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= htmlspecialchars($class) ?>" <?= $kelas === $class ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group mr-3 mb-3">
                    <label for="subject_id" class="mr-2">Mata Pelajaran:</label>
                    <select name="subject_id" id="subject_id" class="form-control">
                        <option value="">Pilih Mata Pelajaran</option>
                        <?php foreach ($subjects as $subj): ?>
                            <option value="<?= $subj['id'] ?>" <?= $subjectId === (int)$subj['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subj['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group mr-3 mb-3">
                    <label for="semester" class="mr-2">Semester:</label>
                    <select name="semester" id="semester" class="form-control">
                        <option value="1" <?= $semester === '1' ? 'selected' : '' ?>>Semester 1</option>
                        <option value="2" <?= $semester === '2' ? 'selected' : '' ?>>Semester 2</option>
                    </select>
                </div>
                
                <div class="form-group mr-3 mb-3">
                    <label for="academic_year" class="mr-2">Tahun Ajaran:</label>
                    <input type="text" name="academic_year" id="academic_year" class="form-control" 
                           value="<?= htmlspecialchars($academicYear) ?>" placeholder="2023/2024">
                </div>
                
                <div class="form-group mr-3 mb-3">
                    <label for="start_date" class="mr-2">Dari Tanggal:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" 
                           value="<?= htmlspecialchars($startDate) ?>">
                </div>
                
                <div class="form-group mr-3 mb-3">
                    <label for="end_date" class="mr-2">Sampai Tanggal:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" 
                           value="<?= htmlspecialchars($endDate) ?>">
                </div>
                
                <?php if ($studentId): ?>
                    <input type="hidden" name="student_id" value="<?= $studentId ?>">
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary mb-3">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($kelas) || $studentId > 0): ?>
        <?php if (!empty($entries)): ?>
            <!-- Current filter info -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php if ($studentId > 0 && !empty($student)): ?>
                            <div class="col-md-4">
                                <p><strong>Siswa:</strong> <?= htmlspecialchars($student['name']) ?></p>
                                <p><strong>NISN:</strong> <?= htmlspecialchars($student['nisn']) ?></p>
                                <p><strong>Kelas:</strong> <?= htmlspecialchars($student['class']) ?></p>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <p><strong>Kelas:</strong> <?= htmlspecialchars($kelas) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-4">
                            <p><strong>Mata Pelajaran:</strong> <?= htmlspecialchars($subject['name'] ?? '-') ?></p>
                            <p><strong>Semester:</strong> <?= $semester === '1' ? 'Semester 1' : 'Semester 2' ?></p>
                            <p><strong>Tahun Ajaran:</strong> <?= htmlspecialchars($academicYear) ?></p>
                        </div>
                        
                        <div class="col-md-4">
                            <p><strong>Periode:</strong> <?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?></p>
                            <p><strong>Jumlah Entri:</strong> <?= count($entries) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Journal entries list -->
            <?php foreach ($entries as $index => $entry): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <?= date('d F Y', strtotime($entry['entry_date'])) ?> - <?= date('H:i', strtotime($entry['entry_time'])) ?>
                            <?php if (empty($studentId)): ?>
                                <span class="ml-3">| <?= htmlspecialchars($entry['student_name']) ?></span>
                            <?php endif; ?>
                        </h6>
                        <div>
                            <a href="input_single.php?student_id=<?= $entry['student_id'] ?>&subject_id=<?= $entry['subject_id'] ?>&kelas=<?= urlencode($entry['class']) ?>&semester=<?= $entry['semester'] ?>&academic_year=<?= urlencode($entry['academic_year']) ?>&journal_id=<?= $entry['id'] ?>" 
                               class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete.php?id=<?= $entry['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" 
                               class="btn btn-danger btn-sm" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus entri jurnal ini?');">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="30%">Nama Siswa</th>
                                        <td><?= htmlspecialchars($entry['student_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>NISN</th>
                                        <td><?= htmlspecialchars($entry['nisn']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Kelas</th>
                                        <td><?= htmlspecialchars($entry['class']) ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th width="30%">Mata Pelajaran</th>
                                        <td><?= htmlspecialchars($entry['subject_name']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Semester</th>
                                        <td><?= $entry['semester'] === '1' ? 'Semester 1' : 'Semester 2' ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tahun Ajaran</th>
                                        <td><?= htmlspecialchars($entry['academic_year']) ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <h6 class="font-weight-bold">Daftar Tugas:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="5%">No</th>
                                        <th width="40%">Nama Tugas</th>
                                        <th>Tanggal</th>
                                        <th>Nilai</th>
                                        <th>Nilai Maks</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($entry['assignments'])): ?>
                                        <?php foreach ($entry['assignments'] as $aIndex => $assignment): ?>
                                            <tr>
                                                <td><?= $aIndex + 1 ?></td>
                                                <td><?= htmlspecialchars($assignment['assignment_name']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($assignment['assignment_date'])) ?></td>
                                                <td><?= $assignment['score'] !== null ? htmlspecialchars($assignment['score']) : '-' ?></td>
                                                <td><?= htmlspecialchars($assignment['max_score']) ?></td>
                                                <td>
                                                    <?php if ($assignment['score'] !== null): ?>
                                                        <?php 
                                                        $percentage = ($assignment['score'] / $assignment['max_score']) * 100;
                                                        $badgeClass = $percentage >= 75 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                                        ?>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-<?= $badgeClass ?>" role="progressbar" 
                                                                 style="width: <?= $percentage ?>%" 
                                                                 aria-valuenow="<?= $percentage ?>" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                                <?= number_format($percentage, 1) ?>%
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum dinilai</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada data tugas</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (!empty($entry['notes'])): ?>
                            <div class="card mt-3">
                                <div class="card-header py-2">
                                    <h6 class="m-0 font-weight-bold text-primary">Catatan untuk Siswa</h6>
                                </div>
                                <div class="card-body py-2">
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($entry['notes'])) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Tidak ada entri jurnal yang ditemukan untuk filter yang dipilih
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Silakan pilih kelas dan mata pelajaran untuk melihat entri jurnal
        </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        .no-print {
            display: none !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            margin-bottom: 20px !important;
            break-inside: avoid !important;
        }
        
        .card-header {
            background-color: #f8f9fc !important;
            border-bottom: 1px solid #ddd !important;
            padding: 10px !important;
        }
    }
</style>

<script>
    // Hide elements when printing
    window.addEventListener('beforeprint', function() {
        document.querySelectorAll('.btn, .sidebar, .topbar, .card.shadow:first-child, .card.shadow:nth-child(2), .footer').forEach(function(el) {
            el.classList.add('no-print');
        });
    });
    
    window.addEventListener('afterprint', function() {
        document.querySelectorAll('.no-print').forEach(function(el) {
            el.classList.remove('no-print');
        });
    });
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
