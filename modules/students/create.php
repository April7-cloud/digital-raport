<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

$pageTitle = 'Add Student';
require_once '../../config/config.php';
require_once '../../includes/header.php';
require_once '../../includes/class_helpers.php';

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = "Invalid form submission, please try again";
        logError('CSRF token validation failed on student creation', 'security');
    } else {
        // Validate input
        $nis = sanitize($_POST['nis']);
        $name = sanitize($_POST['name']);
        $gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : null;
        $birthPlace = sanitize($_POST['birth_place']);
        $birthDate = sanitize($_POST['birth_date']);
        $class = sanitize($_POST['class']);
        $address = sanitize($_POST['address']);
        $phone = sanitize($_POST['phone']);
        $parentName = sanitize($_POST['parent_name']);
        $parentPhone = sanitize($_POST['parent_phone']);
        
        // Validation
        if (empty($nis)) {
            $errors[] = "NIS is required";
        } elseif (!preg_match('/^[0-9]{5,20}$/', $nis)) {
            $errors[] = "NIS must be a valid number (5-20 digits)";
        }
        
        if (empty($name)) {
            $errors[] = "Name is required";
        } elseif (strlen($name) < 3 || strlen($name) > 100) {
            $errors[] = "Name must be between 3-100 characters";
        }
        
        if (empty($gender)) {
            $errors[] = "Gender is required";
        } elseif (!in_array($gender, ['L', 'P'])) {
            $errors[] = "Invalid gender value";
        }
        
        if (empty($class)) {
            $errors[] = "Class is required";
        }
        
        if (!empty($birthDate) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
            $errors[] = "Birth date must be in YYYY-MM-DD format";
        }
        
        if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', str_replace(['-', ' '], '', $phone))) {
            $errors[] = "Phone number must be valid (10-15 digits)";
        }
        
        if (!empty($parentPhone) && !preg_match('/^[0-9]{10,15}$/', str_replace(['-', ' '], '', $parentPhone))) {
            $errors[] = "Parent phone number must be valid (10-15 digits)";
        }
        
        // If no errors, insert into database
        if (empty($errors)) {
            $database = new Database();
            $db = $database->connect();
            
            try {
                // Check if NIS already exists
                $checkStmt = $db->prepare("SELECT id FROM students WHERE nis = ?");
                $checkStmt->execute([$nis]);
                
                if ($checkStmt->rowCount() > 0) {
                    $errors[] = "NIS already exists";
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO students 
                        (nis, name, gender, birth_place, birth_date, class, address, phone, parent_name, parent_phone) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $result = $stmt->execute([
                        $nis,
                        $name,
                        $gender,
                        $birthPlace,
                        $birthDate,
                        $class,
                        $address,
                        $phone,
                        $parentName,
                        $parentPhone
                    ]);
                    
                    if ($result) {
                        setFlash('success', 'Student added successfully');
                        logError("Student created: $name ($nis)", 'info');
                        redirect(BASE_URL . '/modules/students/index.php');
                    } else {
                        $errors[] = "Failed to add student";
                        logError("Failed to create student: $name ($nis)", 'error');
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                logError("Database error on student creation: " . $e->getMessage(), 'error');
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-user-graduate me-2"></i> Add Student</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" novalidate>
                <?php echo csrfField(); ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nis" class="form-label">NIS <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nis" name="nis" value="<?php echo isset($_POST['nis']) ? $_POST['nis'] : ''; ?>" required pattern="[0-9]{5,20}">
                        <div class="form-text">Student identification number (5-20 digits)</div>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>" required minlength="3" maxlength="100">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="P" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'P') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="class" class="form-label">Kelas <span class="text-danger">*</span></label>
                        <?php 
                        $database = new Database();
                        $db = $database->connect();
                        
                        // Check if classes are defined in subjects
                        $classesExist = areClassesDefinedInSubjects($db);
                        
                        if ($classesExist) {
                            // Generate dropdown from subjects' class values
                            echo generateSubjectsClassesDropdown(
                                $db, 
                                'class', 
                                isset($_POST['class']) ? $_POST['class'] : null,
                                ['class' => 'form-select', 'required' => true]
                            );
                        } else {
                            // Show text input with a warning
                            echo '<input type="text" class="form-control" id="class" name="class" value="' . 
                                (isset($_POST['class']) ? htmlspecialchars($_POST['class']) : '') . 
                                '" required>';
                            echo '<div class="form-text text-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                Belum ada data kelas. Tambahkan terlebih dahulu di 
                                <a href="../subjects/create.php" target="_blank">menu Mata Pelajaran</a>
                            </div>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="birth_place" class="form-label">Tempat Lahir</label>
                        <input type="text" class="form-control" id="birth_place" name="birth_place" value="<?php echo isset($_POST['birth_place']) ? $_POST['birth_place'] : ''; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="birth_date" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo isset($_POST['birth_date']) ? $_POST['birth_date'] : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? $_POST['address'] : ''; ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Telepon</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>" pattern="[0-9\-\s]{10,15}">
                        <div class="form-text">Format: 0812-3456-7890</div>
                    </div>
                    <div class="col-md-6">
                        <label for="parent_name" class="form-label">Nama Orang Tua</label>
                        <input type="text" class="form-control" id="parent_name" name="parent_name" value="<?php echo isset($_POST['parent_name']) ? $_POST['parent_name'] : ''; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="parent_phone" class="form-label">Telepon Orang Tua</label>
                    <input type="tel" class="form-control" id="parent_phone" name="parent_phone" value="<?php echo isset($_POST['parent_phone']) ? $_POST['parent_phone'] : ''; ?>" pattern="[0-9\-\s]{10,15}">
                    <div class="form-text">Format: 0812-3456-7890</div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Client-side form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // NIS validation
        const nis = document.getElementById('nis');
        if (!nis.value.trim()) {
            isValid = false;
            showError(nis, 'NIS is required');
        } else if (!/^[0-9]{5,20}$/.test(nis.value.trim())) {
            isValid = false;
            showError(nis, 'NIS must be a valid number (5-20 digits)');
        } else {
            clearError(nis);
        }
        
        // Name validation
        const name = document.getElementById('name');
        if (!name.value.trim()) {
            isValid = false;
            showError(name, 'Name is required');
        } else if (name.value.trim().length < 3 || name.value.trim().length > 100) {
            isValid = false;
            showError(name, 'Name must be between 3-100 characters');
        } else {
            clearError(name);
        }
        
        // Gender validation
        const gender = document.getElementById('gender');
        if (!gender.value) {
            isValid = false;
            showError(gender, 'Gender is required');
        } else {
            clearError(gender);
        }
        
        // Class validation
        const classField = document.getElementById('class');
        if (!classField.value.trim()) {
            isValid = false;
            showError(classField, 'Class is required');
        } else {
            clearError(classField);
        }
        
        // Phone validation
        const phone = document.getElementById('phone');
        if (phone.value.trim() && !/^[0-9\-\s]{10,15}$/.test(phone.value.trim())) {
            isValid = false;
            showError(phone, 'Phone number must be valid (10-15 digits)');
        } else {
            clearError(phone);
        }
        
        // Parent phone validation
        const parentPhone = document.getElementById('parent_phone');
        if (parentPhone.value.trim() && !/^[0-9\-\s]{10,15}$/.test(parentPhone.value.trim())) {
            isValid = false;
            showError(parentPhone, 'Parent phone number must be valid (10-15 digits)');
        } else {
            clearError(parentPhone);
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
    
    // Helper functions
    function showError(field, message) {
        const errorElement = field.nextElementSibling?.classList.contains('form-text') 
            ? document.createElement('div') 
            : field.nextElementSibling || document.createElement('div');
        
        errorElement.className = 'invalid-feedback';
        errorElement.textContent = message;
        field.classList.add('is-invalid');
        
        if (!field.nextElementSibling?.classList.contains('invalid-feedback')) {
            if (field.nextElementSibling?.classList.contains('form-text')) {
                field.parentNode.insertBefore(errorElement, field.nextElementSibling.nextSibling);
            } else {
                field.parentNode.appendChild(errorElement);
            }
        }
    }
    
    function clearError(field) {
        field.classList.remove('is-invalid');
        const errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (errorElement) {
            errorElement.remove();
        }
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>