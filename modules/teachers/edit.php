<?php
$pageTitle = 'Edit Teacher';
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlash('error', 'Teacher ID is required');
    redirect(BASE_URL . '/modules/teachers/index.php');
}

$id = (int)$_GET['id'];
$database = new Database();
$db = $database->connect();
$errors = [];
$teacher = null;

// Get teacher data
try {
    $stmt = $db->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        setFlash('error', 'Teacher not found');
        redirect(BASE_URL . '/modules/teachers/index.php');
    }
    
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    setFlash('error', 'Database error: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/teachers/index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !validateCsrfToken($_POST[CSRF_TOKEN_NAME])) {
        $errors[] = "Invalid form submission, please try again";
        logError('CSRF token validation failed on teacher update', 'security');
    } else {
        // Validate input
        $nip = sanitize($_POST['nip']);
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        // Validation
        if (empty($nip)) {
            $errors[] = "NIP is required";
        } elseif (!preg_match('/^[0-9]{5,20}$/', $nip)) {
            $errors[] = "NIP must be a valid number (5-20 digits)";
        }
        
        if (empty($name)) {
            $errors[] = "Name is required";
        } elseif (strlen($name) < 3 || strlen($name) > 100) {
            $errors[] = "Name must be between 3-100 characters";
        }
        
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email must be a valid email address";
        }
        
        if (!empty($phone) && !preg_match('/^[0-9]{10,15}$/', str_replace(['-', ' '], '', $phone))) {
            $errors[] = "Phone number must be valid (10-15 digits)";
        }
        
        // If no errors, update database
        if (empty($errors)) {
            try {
                // Check if NIP already exists for another teacher
                $checkStmt = $db->prepare("SELECT id FROM teachers WHERE nip = ? AND id != ?");
                $checkStmt->execute([$nip, $id]);
                
                if ($checkStmt->rowCount() > 0) {
                    $errors[] = "NIP already exists for another teacher";
                } else {
                    $stmt = $db->prepare("UPDATE teachers SET nip = ?, name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                    
                    $result = $stmt->execute([
                        $nip,
                        $name,
                        $email,
                        $phone,
                        $address,
                        $id
                    ]);
                    
                    if ($result) {
                        setFlash('success', 'Teacher updated successfully');
                        logError("Teacher updated: $name ($nip)", 'info');
                        redirect(BASE_URL . '/modules/teachers/index.php');
                    } else {
                        $errors[] = "Failed to update teacher";
                        logError("Failed to update teacher: $name ($nip)", 'error');
                    }
                }
            } catch (PDOException $e) {
                $errors[] = "Database error: " . $e->getMessage();
                logError("Database error on teacher update: " . $e->getMessage(), 'error');
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-chalkboard-teacher me-2"></i> Edit Teacher</h2>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to List
        </a>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Teacher Information</h6>
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
                        <label for="nip" class="form-label">NIP <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nip" name="nip" 
                               value="<?php echo isset($_POST['nip']) ? $_POST['nip'] : $teacher['nip']; ?>" 
                               required pattern="[0-9]{5,20}">
                        <div class="form-text">Teacher identification number (5-20 digits)</div>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($_POST['name']) ? $_POST['name'] : $teacher['name']; ?>" 
                               required minlength="3" maxlength="100">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($_POST['email']) ? $_POST['email'] : $teacher['email']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Telepon</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : $teacher['phone']; ?>" 
                               pattern="[0-9\-\s]{10,15}">
                        <div class="form-text">Format: 0812-3456-7890</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? $_POST['address'] : $teacher['address']; ?></textarea>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
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
        
        // NIP validation
        const nip = document.getElementById('nip');
        if (!nip.value.trim()) {
            isValid = false;
            showError(nip, 'NIP is required');
        } else if (!/^[0-9]{5,20}$/.test(nip.value.trim())) {
            isValid = false;
            showError(nip, 'NIP must be a valid number (5-20 digits)');
        } else {
            clearError(nip);
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
        
        // Email validation
        const email = document.getElementById('email');
        if (email.value.trim() && !email.validity.valid) {
            isValid = false;
            showError(email, 'Email must be a valid email address');
        } else {
            clearError(email);
        }
        
        // Phone validation
        const phone = document.getElementById('phone');
        if (phone.value.trim() && !/^[0-9\-\s]{10,15}$/.test(phone.value.trim())) {
            isValid = false;
            showError(phone, 'Phone number must be valid (10-15 digits)');
        } else {
            clearError(phone);
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
