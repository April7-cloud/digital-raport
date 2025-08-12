<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

$pageTitle = 'Tambah Mata Pelajaran';
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

// Get distinct class values from subjects table
try {
    $stmt = $db->query("
        SELECT DISTINCT class FROM subjects 
        WHERE class IS NOT NULL AND class != ''
        ORDER BY class ASC
    ");
    $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    logError('Error fetching classes: ' . $e->getMessage());
    $classes = [];
}

// Form processing
$errors = [];
$success = false;
$formData = [
    'code' => '',
    'name' => '',
    'description' => '',
    'teacher_id' => '',
    'class' => '',
    'kkm' => '70.00',
    'academic_year' => date('Y') . '/' . (date('Y') + 1),
];

$newClass = false; // Flag for when user selects "Add New Class"

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = 'Invalid CSRF token. Please try again.';
        logError('CSRF validation failed in subject creation', 'security');
    } else {
        // Sanitize and validate input
        $formData['code'] = sanitize($_POST['code'] ?? '');
        $formData['name'] = sanitize($_POST['name'] ?? '');
        $formData['description'] = sanitize($_POST['description'] ?? '');
        $formData['teacher_id'] = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : null;
        $formData['kkm'] = (float)($_POST['kkm'] ?? 70.00);
        $formData['academic_year'] = sanitize($_POST['academic_year'] ?? '');
        
        // Handle class selection or new class input
        if (isset($_POST['class_select']) && $_POST['class_select'] === 'new' && isset($_POST['class_new'])) {
            $formData['class'] = sanitize($_POST['class_new']);
            $newClass = true;
        } elseif (isset($_POST['class_select']) && $_POST['class_select'] !== 'new') {
            $formData['class'] = sanitize($_POST['class_select']);
        } else {
            $formData['class'] = '';
        }

        // Validation
        if (empty($formData['code'])) {
            $errors[] = 'Kode mata pelajaran harus diisi.';
        } elseif (strlen($formData['code']) > 10) {
            $errors[] = 'Kode mata pelajaran maksimal 10 karakter.';
        } else {
            // Check if code already exists for the same class
            try {
                $checkStmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE code = ? AND class = ?");
                $checkStmt->execute([$formData['code'], $formData['class']]);
                if ($checkStmt->fetchColumn() > 0) {
                    $errors[] = 'Kode mata pelajaran sudah digunakan untuk kelas ini.';
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
            $errors[] = 'Silahkan menambahkan Kelas baru, pada kolom kelas baru';
        } elseif (strlen($formData['class']) > 20) {
            $errors[] = 'Kelas maksimal 20 karakter.';
        }

        if ($formData['kkm'] < 0 || $formData['kkm'] > 100) {
            $errors[] = 'KKM harus berupa angka antara 0 dan 100.';
        }

        if (empty($formData['academic_year'])) {
            $errors[] = 'Tahun ajaran harus diisi.';
        } elseif (strlen($formData['academic_year']) > 20) {
            $errors[] = 'Tahun ajaran maksimal 20 karakter.';
        }

        // If validation passes, insert into database
        if (empty($errors)) {
            try {
                // Simplified SQL query without class_id
                $stmt = $db->prepare("INSERT INTO subjects (code, name, description, teacher_id, class, kkm, academic_year) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $formData['code'],
                    $formData['name'],
                    $formData['description'],
                    $formData['teacher_id'],
                    $formData['class'],
                    $formData['kkm'],
                    $formData['academic_year'],
                ]);

                if ($result) {
                    $success = true;
                    setFlash('success', 'Mata pelajaran berhasil ditambahkan.');
                    
                    // Log the action
                    logError("New subject added: {$formData['name']} ({$formData['code']})", 'info');
                    
                    // Redirect to prevent form resubmission
                    redirect('index.php');
                } else {
                    $errors[] = 'Gagal menambahkan mata pelajaran. Error: ' . implode(', ', $db->errorInfo());
                    logError('Failed to insert subject: ' . print_r($formData, true) . ' - DB Error: ' . implode(', ', $db->errorInfo()));
                }
            } catch(PDOException $e) {
                $errors[] = 'Terjadi kesalahan database: ' . $e->getMessage();
                logError('Database error adding subject: ' . $e->getMessage());
                // Add detailed error information to log
                logError('Database error details - SQL state: ' . $e->getCode() . ', Error info: ' . print_r($db->errorInfo(), true));
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
                <h1 class="h3 mb-0 text-gray-800">Tambah Mata Pelajaran</h1>
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
                    Mata pelajaran berhasil ditambahkan.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Form Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Form Tambah Mata Pelajaran</h6>
                </div>
                <div class="card-body">
                    <form action="" method="post" id="subjectForm" novalidate>
                        <?php echo csrfField(); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">Kode Mata Pelajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?php echo htmlspecialchars($formData['code']); ?>" required maxlength="10">
                                    <small class="form-text text-muted">Contoh: MTK, BIO, KIM (Maksimal 10 karakter)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($formData['name']); ?>" required maxlength="100">
                                    <small class="form-text text-muted">Contoh: Matematika, Biologi, Kimia</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="teacher_id" class="form-label">Guru Pengajar</label>
                                    <select name="teacher_id" id="teacher_id" class="form-control">
                                        <option value="">-- Pilih Guru --</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?php echo $teacher['id']; ?>" <?php echo ($formData['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($teacher['name'] . ' (' . $teacher['nip'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Silakan pilih guru pengajar.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class">Kelas <span class="text-danger">*</span></label>
                                    <select name="class_select" id="class_select" class="form-control">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo htmlspecialchars($class); ?>" <?php echo ($formData['class'] == $class) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class); ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <option value="new" <?php echo ($newClass) ? 'selected' : ''; ?>>Tambah Kelas Baru</option>
                                    </select>
                                    <div class="invalid-feedback">Silakan pilih kelas.</div>
                                </div>
                                <div class="form-group" id="new-class-input" style="<?php echo ($newClass) ? 'display:block;' : 'display:none;'; ?>">
                                    <label for="class_new">Nama Kelas Baru</label>
                                    <input type="text" class="form-control" id="class_new" name="class_new" value="<?php echo htmlspecialchars($formData['class']); ?>" maxlength="20">
                                    <small class="form-text text-muted">Contoh: X IPA 1, XI IPS 2, XII BAHASA</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kkm">KKM <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="kkm" name="kkm" value="<?php echo htmlspecialchars($formData['kkm']); ?>" required step="0.01" min="0" max="100">
                                    <small class="form-text text-muted">Nilai minimum untuk kelulusan (0-100)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="academic_year">Tahun Ajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo htmlspecialchars($formData['academic_year']); ?>" required maxlength="20">
                                    <small class="form-text text-muted">Contoh: 2025/2026</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($formData['description']); ?></textarea>
                            <small class="form-text text-muted">Deskripsi singkat mata pelajaran (opsional)</small>
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
    $('#subjectForm').on('submit', function(e) {
        let valid = true;
        const code = $('#code').val().trim();
        const name = $('#name').val().trim();
        const teacherId = $('#teacher_id').val();
        const kkm = parseFloat($('#kkm').val());
        const academicYear = $('#academic_year').val().trim();
        const classSelect = $('#class_select').val();
        const classNew = $('#class_new').val().trim();
        
        // Reset previous error messages
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        if (!code) {
            $('#code').addClass('is-invalid').after('<div class="invalid-feedback">Kode mata pelajaran harus diisi</div>');
            valid = false;
        } else if (code.length > 10) {
            $('#code').addClass('is-invalid').after('<div class="invalid-feedback">Kode mata pelajaran maksimal 10 karakter</div>');
            valid = false;
        }
        
        if (!name) {
            $('#name').addClass('is-invalid').after('<div class="invalid-feedback">Nama mata pelajaran harus diisi</div>');
            valid = false;
        } else if (name.length > 100) {
            $('#name').addClass('is-invalid').after('<div class="invalid-feedback">Nama mata pelajaran maksimal 100 karakter</div>');
            valid = false;
        }
        
        if (!teacherId) {
            $('#teacher_id').addClass('is-invalid').after('<div class="invalid-feedback">Guru pengajar harus dipilih</div>');
            valid = false;
        }
        
        if (classSelect === 'new' && !classNew) {
            $('#class_new').addClass('is-invalid').after('<div class="invalid-feedback">Nama kelas baru harus diisi</div>');
            valid = false;
        } else if (classSelect !== 'new' && !classSelect) {
            $('#class_select').addClass('is-invalid').after('<div class="invalid-feedback">Kelas harus dipilih</div>');
            valid = false;
        }
        
        if (isNaN(kkm) || kkm < 0 || kkm > 100) {
            $('#kkm').addClass('is-invalid').after('<div class="invalid-feedback">KKM harus berupa angka antara 0 dan 100</div>');
            valid = false;
        }
        
        if (!academicYear) {
            $('#academic_year').addClass('is-invalid').after('<div class="invalid-feedback">Tahun ajaran harus diisi</div>');
            valid = false;
        } else if (academicYear.length > 20) {
            $('#academic_year').addClass('is-invalid').after('<div class="invalid-feedback">Tahun ajaran maksimal 20 karakter</div>');
            valid = false;
        }
        
        if (!valid) {
            e.preventDefault();
        }
    });
    
    // Toggle new class input
    $('#class_select').on('change', function() {
        const selected = $(this).val();
        if (selected === 'new') {
            $('#new-class-input').show();
        } else {
            $('#new-class-input').hide();
        }
    });
    
    // Save new class
    $('#save-class-btn').on('click', function() {
        const newClassName = $('#new_class_name').val().trim();
        if (newClassName) {
            // Show loading state
            const $btn = $(this);
            const originalText = $btn.text();
            $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');
            $btn.prop('disabled', true);
            
            // Send AJAX request to create the class in the database
            $.ajax({
                url: '../../api/subjects.php',
                method: 'POST',
                data: {
                    action: 'add_class',
                    class_name: newClassName,
                    <?php echo CSRF_TOKEN_NAME; ?>: '<?php echo createCsrfToken(); ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        toastr.success(response.message || 'Kelas berhasil ditambahkan');
                        
                        // Add to dropdown and select it
                        if ($('#class_select option[value="' + newClassName + '"]').length === 0) {
                            $('#class_select').append('<option value="' + newClassName + '">' + newClassName + '</option>');
                        }
                        $('#class_select').val(newClassName);
                        $('#newClassModal').modal('hide');
                        
                        // Clear the input field
                        $('#new_class_name').val('');
                    } else {
                        // Show error message
                        toastr.error(response.message || 'Gagal menambahkan kelas');
                    }
                },
                error: function() {
                    toastr.error('Terjadi kesalahan saat menyimpan kelas baru');
                },
                complete: function() {
                    // Restore button state
                    $btn.html(originalText);
                    $btn.prop('disabled', false);
                }
            });
        } else {
            // Show validation error
            toastr.warning('Silahkan isi Kelas yang baru');
        }
    });
});
</script>

<?php
// End the output buffer and send output to browser
ob_end_flush();
?>
