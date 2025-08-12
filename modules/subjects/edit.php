<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

$pageTitle = 'Edit Mata Pelajaran';
require_once '../../config/config.php';
require_once '../../includes/header.php';
require_once '../../includes/class_helpers.php'; // Include class helpers

// Check permission
if (!hasRole(['admin', 'teacher'])) {
    setFlash('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('../dashboard');
}

// Connect to database
$database = new Database();
$db = $database->connect();

// Get subject ID from URL
$subjectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$subjectId) {
    setFlash('error', 'ID mata pelajaran tidak valid.');
    redirect('index.php');
}

// Get existing subject data
try {
    $stmt = $db->prepare("
        SELECT * FROM subjects WHERE id = ?
    ");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject) {
        setFlash('error', 'Mata pelajaran tidak ditemukan.');
        redirect('index.php');
    }
} catch (PDOException $e) {
    logError('Error fetching subject: ' . $e->getMessage());
    setFlash('error', 'Terjadi kesalahan saat mengambil data mata pelajaran.');
    redirect('index.php');
}

// Get all teachers for the dropdown
try {
    $stmt = $db->query("
        SELECT t.id, t.name, t.nip
        FROM teachers t
        WHERE t.is_active = 1
        ORDER BY t.name ASC
    ");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError('Error fetching teachers: ' . $e->getMessage());
    $teachers = [];
}

// Form processing
$errors = [];
$success = false;
$formData = [
    'code' => $subject['code'] ?? '',
    'name' => $subject['name'] ?? '',
    'description' => $subject['description'] ?? '',
    'teacher_id' => $subject['teacher_id'] ?? '',
    'class' => $subject['class'] ?? '',
    'kkm' => $subject['kkm'] ?? '70.00',
    'academic_year' => $subject['academic_year'] ?? (date('Y') . '/' . (date('Y') + 1)),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = 'Invalid CSRF token. Please try again.';
        logError('CSRF validation failed in subject editing', 'security');
    } else {
        // Sanitize and validate input
        $formData['code'] = sanitize($_POST['code'] ?? '');
        $formData['name'] = sanitize($_POST['name'] ?? '');
        $formData['description'] = sanitize($_POST['description'] ?? '');
        $formData['teacher_id'] = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
        $formData['kkm'] = (float)($_POST['kkm'] ?? 70.00);
        $formData['academic_year'] = sanitize($_POST['academic_year'] ?? '');
        $formData['class'] = sanitize($_POST['class'] ?? '');

        // Validation
        if (empty($formData['code'])) {
            $errors[] = 'Kode mata pelajaran harus diisi.';
        } elseif (strlen($formData['code']) > 10) {
            $errors[] = 'Kode mata pelajaran maksimal 10 karakter.';
        } else {
            // Check if code already exists (excluding this subject)
            try {
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE code = ? AND id != ?");
                $checkStmt->execute([$formData['code'], $subjectId]);
                if ($checkStmt->fetchColumn() > 0) {
                    $errors[] = 'Kode mata pelajaran sudah digunakan.';
                }
            } catch(PDOException $e) {
                logError('Error checking subject code: ' . $e->getMessage());
                $errors[] = 'Terjadi kesalahan saat memeriksa kode mata pelajaran.';
            }
        }

        if (empty($formData['name'])) {
            $errors[] = 'Nama mata pelajaran harus diisi.';
        } elseif (strlen($formData['name']) > 100) {
            $errors[] = 'Nama mata pelajaran maksimal 100 karakter.';
        }

        if (empty($formData['teacher_id'])) {
            $errors[] = 'Guru pengajar harus dipilih.';
        } else {
            // Verify teacher_id exists
            try {
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM teachers WHERE id = ?");
                $checkStmt->execute([$formData['teacher_id']]);
                if ($checkStmt->fetchColumn() == 0) {
                    $errors[] = 'Guru yang dipilih tidak valid.';
                }
            } catch(PDOException $e) {
                logError('Error validating teacher_id: ' . $e->getMessage());
                $errors[] = 'Terjadi kesalahan saat memvalidasi guru.';
            }
        }

        if (empty($formData['class'])) {
            $errors[] = 'Kelas harus diisi.';
        } elseif (strlen($formData['class']) > 20) {
            $errors[] = 'Kelas maksimal 20 karakter.';
        }

        if (empty($formData['academic_year'])) {
            $errors[] = 'Tahun ajaran harus diisi.';
        }

        // If no errors, update subject
        if (empty($errors)) {
            try {
                $updateStmt = $db->prepare("
                    UPDATE subjects 
                    SET code = ?, name = ?, description = ?, teacher_id = ?, 
                        class = ?, kkm = ?, academic_year = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                
                $result = $updateStmt->execute([
                    $formData['code'],
                    $formData['name'],
                    $formData['description'],
                    $formData['teacher_id'],
                    $formData['class'],
                    $formData['kkm'],
                    $formData['academic_year'],
                    $subjectId
                ]);
                
                if ($result) {
                    setFlash('success', 'Mata pelajaran berhasil diperbarui.');
                    redirect('index.php');
                } else {
                    $errors[] = 'Gagal memperbarui mata pelajaran.';
                }
            } catch(PDOException $e) {
                logError('Error updating subject: ' . $e->getMessage());
                $errors[] = 'Terjadi kesalahan saat memperbarui mata pelajaran.';
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
                <h1 class="h3 mb-0 text-gray-800">Edit Mata Pelajaran</h1>
                <a href="index.php" class="btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
                </a>
            </div>
            
            <!-- Success message -->
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Berhasil!</strong> Mata pelajaran berhasil diperbarui.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Error messages -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <!-- Subject Edit Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Edit Mata Pelajaran</h6>
                </div>
                <div class="card-body">
                    <form action="" method="post" id="subjectForm" novalidate>
                        <?php echo csrfField(); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Kode Mata Pelajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($formData['code']); ?>" required maxlength="10">
                                    <small class="text-muted">Contoh: MTK, B.IND, B.ING, dll.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required maxlength="100">
                                    <small class="text-muted">Contoh: Matematika, Bahasa Indonesia, dll.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="teacher_id">Guru Pengajar <span class="text-danger">*</span></label>
                                    <select class="form-control" id="teacher_id" name="teacher_id" required>
                                        <option value="">-- Pilih Guru --</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo $formData['teacher_id'] == $teacher['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['name'] . ' (' . $teacher['nip'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class">Kelas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="class" name="class" value="<?php echo htmlspecialchars($formData['class']); ?>" required maxlength="20">
                                    <small class="text-muted">Contoh: 10A, 11 IPA 2, 12 IPS 1, dll.</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kkm">KKM <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" max="100" class="form-control" id="kkm" name="kkm" value="<?php echo htmlspecialchars($formData['kkm']); ?>" required>
                                    <small class="text-muted">Kriteria Ketuntasan Minimal (0-100)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($formData['academic_year']); ?>" required>
                                    <small class="text-muted">Contoh: 2023/2024</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('subjectForm');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                alert('Harap isi semua kolom yang wajib diisi.');
            }
        });
    }
});
</script>

<?php
// End output buffering and send to browser
ob_end_flush();
?>
