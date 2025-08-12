<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

$pageTitle = 'Tambah Kelas Baru';
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Check permission
if (!hasRole(['admin', 'teacher'])) {
    setFlash('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('../dashboard');
}

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Form processing
$errors = [];
$success = false;
$formData = [
    'name' => '',
    'level' => '',
    'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = 'Invalid CSRF token. Please try again.';
        logError('CSRF validation failed in class creation', 'security');
    } else {
        // Sanitize and validate input
        $formData['name'] = sanitize($_POST['name'] ?? '');
        $formData['level'] = sanitize($_POST['level'] ?? '');
        $formData['description'] = sanitize($_POST['description'] ?? '');

        // Validation
        if (empty($formData['name'])) {
            $errors[] = 'Nama kelas harus diisi.';
        } elseif (strlen($formData['name']) > 50) {
            $errors[] = 'Nama kelas maksimal 50 karakter.';
        } 

        if (empty($formData['level'])) {
            $errors[] = 'Tingkat kelas harus diisi.';
        } elseif (strlen($formData['level']) > 10) {
            $errors[] = 'Tingkat kelas maksimal 10 karakter.';
        }

        if (strlen($formData['description']) > 255) {
            $errors[] = 'Deskripsi kelas maksimal 255 karakter.';
        }

        // Check if class already exists with the same level and name
        if (empty($errors)) {
            try {
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM classes WHERE level = ? AND name = ?");
                $checkStmt->execute([$formData['level'], $formData['name']]);
                if ($checkStmt->fetchColumn() > 0) {
                    $errors[] = 'Kelas dengan tingkat dan nama yang sama sudah ada.';
                }
            } catch (PDOException $e) {
                logError('Error checking class existence: ' . $e->getMessage());
                $errors[] = 'Terjadi kesalahan saat memeriksa keberadaan kelas.';
            }
        }

        // If validation passes, insert into database
        if (empty($errors)) {
            try {
                $stmt = $db->prepare("INSERT INTO classes (name, level, description) VALUES (?, ?, ?)");
                $result = $stmt->execute([
                    $formData['name'],
                    $formData['level'],
                    $formData['description']
                ]);

                if ($result) {
                    $success = true;
                    setFlash('success', 'Kelas berhasil ditambahkan.');
                    
                    // Log the action
                    logError("New class added: {$formData['level']} {$formData['name']}", 'info');
                    
                    // Redirect to prevent form resubmission
                    redirect('index.php');
                } else {
                    $errors[] = 'Gagal menambahkan kelas. Error: ' . implode(', ', $db->errorInfo());
                    logError('Failed to insert class: ' . print_r($formData, true) . ' - DB Error: ' . implode(', ', $db->errorInfo()));
                }
            } catch(PDOException $e) {
                $errors[] = 'Terjadi kesalahan database: ' . $e->getMessage();
                logError('Database error adding class: ' . $e->getMessage());
            }
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
                <h1 class="h3 mb-0 text-gray-800">Tambah Kelas Baru</h1>
                <a href="index.php" class="btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                </a>
            </div>
            
            <!-- Display Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Display Success Message -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Kelas berhasil ditambahkan.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Form Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Tambah Kelas</h6>
                </div>
                <div class="card-body">
                    <form action="" method="post" id="classForm" novalidate>
                        <?php echo csrfField(); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="level">Tingkat Kelas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="level" name="level" value="<?php echo htmlspecialchars($formData['level']); ?>" required maxlength="10">
                                    <small class="form-text text-muted">Contoh: X, XI, XII atau 1, 2, 3, dst.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nama Kelas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required maxlength="50">
                                    <small class="form-text text-muted">Contoh: IPA 1, IPS 2, Reguler A, dst.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                            <small class="form-text text-muted">Deskripsi singkat kelas (opsional)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan</button>
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
            
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->
    <?php require_once '../../includes/footer.php'; ?>
</div>
<!-- End of Content Wrapper -->

<script>
$(document).ready(function() {
    // Client-side validation
    $('#classForm').on('submit', function(e) {
        let valid = true;
        const level = $('#level').val().trim();
        const name = $('#name').val().trim();
        const description = $('#description').val().trim();
        
        // Reset previous error messages
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        if (!level) {
            $('#level').addClass('is-invalid').after('<div class="invalid-feedback">Tingkat kelas harus diisi</div>');
            valid = false;
        } else if (level.length > 10) {
            $('#level').addClass('is-invalid').after('<div class="invalid-feedback">Tingkat kelas maksimal 10 karakter</div>');
            valid = false;
        }
        
        if (!name) {
            $('#name').addClass('is-invalid').after('<div class="invalid-feedback">Nama kelas harus diisi</div>');
            valid = false;
        } else if (name.length > 50) {
            $('#name').addClass('is-invalid').after('<div class="invalid-feedback">Nama kelas maksimal 50 karakter</div>');
            valid = false;
        }
        
        if (description.length > 255) {
            $('#description').addClass('is-invalid').after('<div class="invalid-feedback">Deskripsi maksimal 255 karakter</div>');
            valid = false;
        }
        
        if (!valid) {
            e.preventDefault();
        }
    });
});
</script>

<?php
// End the output buffer and send output to browser
ob_end_flush();
?>
