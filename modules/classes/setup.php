<?php
$pageTitle = 'Setup Manajemen Kelas';
require_once '../../config/config.php';
require_once '../../includes/header.php';
require_once '../../includes/class_helpers.php';

// Check permission - only admin can setup
if (!hasRole(['admin'])) {
    setFlash('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('../dashboard');
}

// Initialize database connection
$database = new Database();
$db = $database->connect();

$isSetup = isClassManagementSetUp($db);
$message = '';
$result = null;

// Process setup form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $message = [
            'type' => 'danger',
            'text' => 'Invalid CSRF token. Please try again.'
        ];
        logError('CSRF validation failed in class setup', 'security');
    } else {
        try {
            // Create the tables and add columns if needed
            $sql = getClassMigrationSQL();
            
            // Execute each statement
            $statements = explode(';', $sql);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $db->exec($statement);
                }
            }
            
            // Check if setup was successful
            $isSetup = isClassManagementSetUp($db);
            
            if ($isSetup) {
                // Migrate existing data if requested
                if (isset($_POST['migrate_existing']) && $_POST['migrate_existing'] == '1') {
                    $result = migrateExistingClassData($db);
                    
                    if ($result['success']) {
                        $message = [
                            'type' => 'success',
                            'text' => 'Sistem manajemen kelas berhasil diatur dan ' . $result['imported'] . ' kelas dari data yang ada telah dimigrasikan.'
                        ];
                    } else {
                        $message = [
                            'type' => 'warning',
                            'text' => 'Sistem manajemen kelas berhasil diatur, tetapi terjadi kesalahan saat migrasi data: ' . $result['message']
                        ];
                    }
                } else {
                    $message = [
                        'type' => 'success',
                        'text' => 'Sistem manajemen kelas berhasil diatur. Anda sekarang dapat menambahkan kelas baru.'
                    ];
                }
                
                // Set flash message for redirect
                setFlash($message['type'], $message['text']);
                redirect('index.php');
            } else {
                $message = [
                    'type' => 'danger',
                    'text' => 'Gagal mengatur sistem manajemen kelas. Silakan periksa log untuk detail lebih lanjut.'
                ];
                logError('Failed to setup class management system');
            }
        } catch (PDOException $e) {
            $message = [
                'type' => 'danger',
                'text' => 'Database error: ' . $e->getMessage()
            ];
            logError('Database error during class setup: ' . $e->getMessage());
        }
    }
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
                <h1 class="h3 mb-0 text-gray-800">Setup Manajemen Kelas</h1>
                <a href="../dashboard" class="btn btn-sm btn-secondary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali ke Dashboard
                </a>
            </div>
            
            <!-- Display Messages -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message['text']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Setup Status Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Status Sistem Manajemen Kelas</h6>
                </div>
                <div class="card-body">
                    <?php if ($isSetup): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Sistem manajemen kelas sudah diatur dan siap digunakan.
                        </div>
                        
                        <p>Anda dapat mengakses sistem manajemen kelas melalui menu navigasi.</p>
                        
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-list"></i> Lihat Daftar Kelas
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Sistem manajemen kelas belum diatur.
                        </div>
                        
                        <p>Pengaturan ini akan melakukan hal berikut:</p>
                        <ul>
                            <li>Membuat tabel <code>classes</code> untuk menyimpan data kelas</li>
                            <li>Menambahkan kolom <code>class_id</code> ke tabel <code>students</code></li>
                            <li>Menambahkan kolom <code>class_id</code> ke tabel <code>subjects</code></li>
                            <li>Secara opsional, memigrasikan data kelas yang ada dari format teks ke format ID</li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Sebaiknya lakukan backup database sebelum melanjutkan.
                        </div>
                        
                        <form method="POST" action="" id="setupForm">
                            <?php echo csrfField(); ?>
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="migrate_existing" name="migrate_existing" value="1" checked>
                                    <label class="custom-control-label" for="migrate_existing">
                                        Migrasikan data kelas yang ada dari format teks
                                    </label>
                                    <small class="form-text text-muted">
                                        Opsi ini akan mencoba mengekstrak informasi tingkat dan nama kelas dari nilai teks yang ada 
                                        di kolom 'class' di tabel students dan subjects.
                                    </small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" onclick="return confirm('Apakah Anda yakin ingin mengatur sistem manajemen kelas?');">
                                    <i class="fas fa-cogs"></i> Atur Sistem Manajemen Kelas
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->
    
    <?php require_once '../../includes/footer.php'; ?>
</div>
<!-- End of Content Wrapper -->
