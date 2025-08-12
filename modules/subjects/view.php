<?php
// Start output buffering
ob_start();

$pageTitle = 'Detail Mata Pelajaran';
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Check permission
if (!hasRole(['admin', 'teacher', 'student'])) {
    setFlash('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('../dashboard');
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlash('error', 'ID mata pelajaran tidak valid.');
    redirect('index.php');
}

$subjectId = (int)$_GET['id'];

// Connect to database
$database = new Database();
$db = $database->connect();

// Get subject data with teacher information
try {
    $stmt = $db->prepare("
        SELECT s.*, t.name AS teacher_name, t.nip AS teacher_nip, t.email AS teacher_email 
        FROM subjects s
        LEFT JOIN teachers t ON s.teacher_id = t.id
        WHERE s.id = ?
    ");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject) {
        setFlash('error', 'Mata pelajaran tidak ditemukan.');
        redirect('index.php');
    }
} catch(PDOException $e) {
    logError('Error retrieving subject: ' . $e->getMessage());
    setFlash('error', 'Terjadi kesalahan saat mengambil data mata pelajaran.');
    redirect('index.php');
}
?>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        
        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Detail Mata Pelajaran</h1>
                <div>
                    <?php if (hasRole(['admin', 'teacher'])): ?>
                    <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-sm btn-primary shadow-sm mr-2">
                        <i class="fas fa-edit fa-sm text-white-50"></i> Edit
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn btn-sm btn-secondary shadow-sm">
                        <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                    </a>
                </div>
            </div>
            
            <!-- Flash messages -->
            <?php if ($flash = getFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $flash; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($flash = getFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $flash; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Subject Details Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Mata Pelajaran</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Kode Mata Pelajaran</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($subject['code']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-book fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Nama Mata Pelajaran</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($subject['name']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-bookmark fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Kelas</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($subject['class']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                KKM</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($subject['kkm']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Tahun Ajaran</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo htmlspecialchars($subject['academic_year']); ?></div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-left-dark shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                                Tanggal Dibuat</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo date('d-m-Y H:i', strtotime($subject['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Deskripsi Mata Pelajaran</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($subject['description'])): ?>
                                <p><?php echo nl2br(htmlspecialchars($subject['description'])); ?></p>
                            <?php else: ?>
                                <p class="text-muted"><em>Tidak ada deskripsi.</em></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Informasi Guru Pengajar</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($subject['teacher_id'] && $subject['teacher_name']): ?>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($subject['teacher_name']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>NIP:</strong> <?php echo htmlspecialchars($subject['teacher_nip']); ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($subject['teacher_email']); ?></p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="../teachers/view.php?id=<?php echo $subject['teacher_id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-user fa-sm text-white-50"></i> Lihat Profil Guru
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted"><em>Tidak ada informasi guru.</em></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->
    <?php require_once '../../includes/footer.php'; ?>
</div>
<!-- End of Content Wrapper -->

<?php
// End the output buffer and send output to browser
ob_end_flush();
?>
