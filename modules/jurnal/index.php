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

// Get all classes
$classes = [];
try {
    $stmt = $db->query("SELECT DISTINCT class FROM students ORDER BY class");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $classes[] = $row['class'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

// Get all subjects
$subjects = [];
try {
    $stmt = $db->query("SELECT * FROM subjects ORDER BY name");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

// Get students if class is selected
$students = [];
if (!empty($kelas) && $subjectId > 0) {
    try {
        // Use case-insensitive comparison for better matching
        $stmt = $db->prepare("
            SELECT * FROM students 
            WHERE LOWER(REPLACE(class, ' ', '')) = LOWER(REPLACE(?, ' ', ''))
            ORDER BY name
        ");
        $stmt->execute([$kelas]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no students found, try less strict matching
        if (count($students) === 0) {
            $stmt = $db->query("
                SELECT *, class as original_class FROM students 
                ORDER BY name
            ");
            $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $normalizedKelas = strtolower(str_replace(' ', '', $kelas));
            
            foreach ($allStudents as $student) {
                $normalizedClass = strtolower(str_replace(' ', '', $student['original_class']));
                if ($normalizedClass === $normalizedKelas) {
                    // Convert to the format expected by the rest of the code
                    $student['class'] = $student['original_class'];
                    unset($student['original_class']);
                    $students[] = $student;
                }
            }
            
            // Show message about class name substitution
            if (count($students) > 0) {
                $actualClass = $students[0]['class'];
                $_SESSION['warning'] = "Kelas \"$kelas\" tidak ditemukan. Menampilkan data untuk kelas \"$actualClass\" sebagai alternatif.";
                $kelas = $actualClass;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Set page title variable (keeping this for future use if needed)
$pageTitle = 'Jurnal Kelas';

?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Jurnal Kelas</h1>
        <div>
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
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
                
                <div class="form-group mr-3 mb-3">
                    <label for="subject_id" class="mr-2">Mata Pelajaran:</label>
                    <select name="subject_id" id="subject_id" class="form-control">
                        <option value="">Pilih Mata Pelajaran</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?= $subject['id'] ?>" <?= $subjectId === (int)$subject['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($subject['name']) ?>
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
                
                <button type="submit" class="btn btn-primary mb-3">
                    <i class="fas fa-search"></i> Filter
                </button>
            </form>
        </div>
    </div>
    
    <?php if (!empty($kelas) && $subjectId > 0): ?>
        <?php if (count($students) > 0): ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Daftar Siswa - <?= htmlspecialchars($kelas) ?>
                    </h6>
                    <div>
                        <a href="view.php?kelas=<?= urlencode($kelas) ?>&subject_id=<?= $subjectId ?>&semester=<?= $semester ?>&academic_year=<?= urlencode($academicYear) ?>" 
                           class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> Lihat Jurnal Kelas
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>NISN</th>
                                    <th>Nama</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($student['nisn']) ?></td>
                                    <td><?= htmlspecialchars($student['name']) ?></td>
                                    <td>
                                        <a href="input_single.php?student_id=<?= $student['id'] ?>&subject_id=<?= $subjectId ?>&kelas=<?= urlencode($kelas) ?>&semester=<?= $semester ?>&academic_year=<?= urlencode($academicYear) ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus-circle"></i> Tambah Jurnal
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-circle"></i> Tidak ada siswa yang ditemukan untuk kelas <?= htmlspecialchars($kelas) ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Silakan pilih kelas dan mata pelajaran untuk melihat daftar siswa
        </div>
    <?php endif; ?>
</div>

<!-- DataTables JavaScript -->
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Indonesian.json"
            }
        });
    });
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
