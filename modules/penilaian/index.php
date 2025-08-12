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

$database = new Database();
$db = $database->connect();

// Get distinct classes from subjects
$classes = [];
try {
    $stmt = $db->query("SELECT DISTINCT class FROM subjects WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
    $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    logError('Error fetching classes: ' . $e->getMessage());
}

// Count students and subjects for each class
$classStats = [];
foreach ($classes as $class) {
    try {
        // Count students in class
        $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE class = ?");
        $stmt->execute([$class]);
        $studentCount = $stmt->fetchColumn();
        
        // Count subjects for class
        $stmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE class = ?");
        $stmt->execute([$class]);
        $subjectCount = $stmt->fetchColumn();
        
        $classStats[$class] = [
            'students' => $studentCount,
            'subjects' => $subjectCount
        ];
    } catch (PDOException $e) {
        logError('Error counting stats for class ' . $class . ': ' . $e->getMessage());
        $classStats[$class] = [
            'students' => 0,
            'subjects' => 0
        ];
    }
}
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Penilaian Siswa</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light py-2 px-3 mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Penilaian</li>
            </ol>
        </nav>
    </div>

    <!-- Alert if needed -->
    <?php if (function_exists('hasFlash') && hasFlash()): ?>
        <?php $flash = getFlash(); ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
            <?= $flash['message'] ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Classes Cards -->
    <div class="row">
        <?php if (empty($classes)): ?>
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-body text-center py-5">
                        <img src="<?= BASE_PATH ?>/assets/img/empty_data.svg" alt="No Data" style="max-height: 200px;" class="mb-3">
                        <h4 class="text-gray-500">Tidak Ada Data Kelas</h4>
                        <p class="text-gray-500 mb-0">Silakan tambahkan data kelas terlebih dahulu.</p>
                        <div class="mt-3">
                            <a href="<?= BASE_PATH ?>/modules/subjects/" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Tambah Mata Pelajaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-12 mb-4">
                <div class="card border-left-primary shadow">
                    <div class="card-body">
                        <h5 class="font-weight-bold text-primary">
                            <i class="fas fa-info-circle"></i> Informasi Penilaian
                        </h5>
                        <p class="mb-0">Silahkan pilih kelas untuk melihat atau menginput nilai siswa. Klik tombol "Lihat Penilaian" untuk melihat nilai yang sudah ada atau "Input Nilai" untuk menambahkan nilai baru.</p>
                    </div>
                </div>
            </div>
            <?php foreach ($classes as $class): ?>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card class-card border-left-primary shadow h-100">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Kelas</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($class) ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-school fa-2x text-gray-300"></i>
                                </div>
                            </div>
                            <?php if (isset($classStats[$class])): ?>
                            <div class="row mt-3">
                                <div class="col-6 border-right">
                                    <div class="text-xs text-muted mb-1">Jumlah Siswa</div>
                                    <div class="h6 mb-0 font-weight-bold">
                                        <i class="fas fa-users text-info"></i> <?= $classStats[$class]['students'] ?>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-xs text-muted mb-1">Mata Pelajaran</div>
                                    <div class="h6 mb-0 font-weight-bold">
                                        <i class="fas fa-book text-success"></i> <?= $classStats[$class]['subjects'] ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="mt-3 d-flex justify-content-between">
                                <a href="view.php?kelas=<?= urlencode($class) ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Lihat Penilaian
                                </a>
                                <a href="input.php?kelas=<?= urlencode($class) ?>" class="btn btn-success">
                                    <i class="fas fa-edit"></i> Input Nilai
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .class-card {
        transition: all 0.3s ease-in-out;
        border-radius: 10px;
        overflow: hidden;
        border-left-width: 4px !important;
    }
    
    .class-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        border-left-width: 6px !important;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    .btn {
        border-radius: 5px;
        padding: 0.375rem 0.75rem;
    }
    
    .breadcrumb {
        background-color: transparent;
        padding: 0;
    }
</style>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
