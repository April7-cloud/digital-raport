<?php
$pageTitle = 'Edit Student';
require_once '../../config/config.php';
require_once '../../includes/header.php';
require_once '../../includes/class_helpers.php'; // Include the class helpers

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlash('error', 'Student ID is required');
    redirect(BASE_URL . '/modules/students/index.php');
}

$id = (int)$_GET['id'];
$database = new Database();
$db = $database->connect();
$errors = [];
$student = null;

// Check if class management is set up
$classManagementEnabled = isClassManagementSetUp($db);

// Get student data
try {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        setFlash('error', 'Student not found');
        redirect(BASE_URL . '/modules/students/index.php');
    }
    
    $student = $stmt->fetch();
} catch (PDOException $e) {
    setFlash('error', 'Database error: ' . $e->getMessage());
    redirect(BASE_URL . '/modules/students/index.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $nisn = sanitize($_POST['nisn']);
    $name = sanitize($_POST['name']);
    $gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : null;
    $birthPlace = sanitize($_POST['birth_place']);
    $birthDate = sanitize($_POST['birth_date']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $parentName = sanitize($_POST['parent_name']);
    $parentPhone = sanitize($_POST['parent_phone']);
    
    // Get class data based on whether class management is enabled
    if ($classManagementEnabled) {
        $classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
        $class = ''; // Will be populated from class_id
        
        if (!empty($classId)) {
            $classData = getClassById($db, $classId);
            if ($classData) {
                // Construct class text value from the selected class record
                $class = $classData['level'] . ' ' . $classData['name'];
            } else {
                $classId = null;
            }
        }
    } else {
        $class = sanitize($_POST['class']);
        $classId = null;
    }
    
    // Validation
    if (empty($nisn)) {
        $errors[] = "NISN is required";
    }
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if ($classManagementEnabled) {
        if (empty($classId)) {
            $errors[] = "Class is required";
        }
    } else {
        if (empty($class)) {
            $errors[] = "Class is required";
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            // Check if NISN already exists for another student
            $checkStmt = $db->prepare("SELECT id FROM students WHERE nisn = ? AND id != ?");
            $checkStmt->execute([$nisn, $id]);
            
            if ($checkStmt->rowCount() > 0) {
                $errors[] = "NISN already exists for another student";
            } else {
                // Prepare SQL based on whether class management is enabled
                if ($classManagementEnabled) {
                    $stmt = $db->prepare("UPDATE students SET nisn = ?, name = ?, gender = ?, birth_place = ?, birth_date = ?, class = ?, class_id = ?, address = ?, phone = ?, parent_name = ?, parent_phone = ? WHERE id = ?");
                    
                    $stmt->execute([
                        $nisn,
                        $name,
                        $gender,
                        $birthPlace,
                        $birthDate,
                        $class,
                        $classId,
                        $address,
                        $phone,
                        $parentName,
                        $parentPhone,
                        $id
                    ]);
                } else {
                    $stmt = $db->prepare("UPDATE students SET nisn = ?, name = ?, gender = ?, birth_place = ?, birth_date = ?, class = ?, address = ?, phone = ?, parent_name = ?, parent_phone = ? WHERE id = ?");
                    
                    $stmt->execute([
                        $nisn,
                        $name,
                        $gender,
                        $birthPlace,
                        $birthDate,
                        $class,
                        $address,
                        $phone,
                        $parentName,
                        $parentPhone,
                        $id
                    ]);
                }
                
                setFlash('success', 'Student updated successfully');
                redirect(BASE_URL . '/modules/students/index.php');
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-user-graduate me-2"></i> Edit Student</h2>
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
            
            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nisn" class="form-label">NISN <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nisn" name="nisn" value="<?php echo isset($_POST['nisn']) ? $_POST['nisn'] : $student['nisn']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nama <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? $_POST['name'] : $student['name']; ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Jenis Kelamin</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="male" <?php echo (isset($_POST['gender']) ? $_POST['gender'] === 'male' : $student['gender'] === 'male') ? 'selected' : ''; ?>>Laki-laki</option>
                            <option value="female" <?php echo (isset($_POST['gender']) ? $_POST['gender'] === 'female' : $student['gender'] === 'female') ? 'selected' : ''; ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="class" class="form-label">Kelas <span class="text-danger">*</span></label>
                        
                        <?php if ($classManagementEnabled): ?>
                            <?php 
                                // Get selected class ID from student data if exists
                                $selectedClassId = isset($_POST['class_id']) ? $_POST['class_id'] : ($student['class_id'] ?? null); 
                                
                                // If no class_id exists but class text exists, try to find matching class
                                if (empty($selectedClassId) && !empty($student['class'])) {
                                    // Try to find a class ID that matches the text value
                                    try {
                                        $classLookupStmt = $db->prepare("SELECT id FROM classes WHERE CONCAT(level, ' ', name) = ?");
                                        $classLookupStmt->execute([$student['class']]);
                                        if ($classLookupStmt->rowCount() > 0) {
                                            $selectedClassId = $classLookupStmt->fetchColumn();
                                        }
                                    } catch (PDOException $e) {
                                        logError('Error looking up class: ' . $e->getMessage());
                                    }
                                }
                                
                                // Generate class dropdown with the selected class ID
                                echo generateClassesDropdown($db, 'class_id', $selectedClassId, ['required' => true]);
                            ?>
                            <small class="form-text text-muted">
                                <a href="<?php echo BASE_URL; ?>/modules/classes" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> Kelola Daftar Kelas
                                </a>
                            </small>
                        <?php else: ?>
                            <?php 
                                $classesExist = areClassesDefinedInSubjects($db);
                                
                                if ($classesExist) {
                                    // Generate dropdown from subjects' class values
                                    echo generateSubjectsClassesDropdown(
                                        $db, 
                                        'class', 
                                        isset($_POST['class']) ? $_POST['class'] : $student['class'],
                                        ['class' => 'form-select', 'required' => true]
                                    );
                                } else {
                                    // Show text input with a warning
                                    echo '<input type="text" class="form-control" id="class" name="class" value="' . 
                                        (isset($_POST['class']) ? htmlspecialchars($_POST['class']) : htmlspecialchars($student['class'])) . 
                                        '" required>';
                                    echo '<div class="form-text text-warning">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Belum ada data kelas. Tambahkan terlebih dahulu di 
                                        <a href="../subjects/create.php" target="_blank">menu Mata Pelajaran</a>
                                    </div>';
                                }
                            ?>
                            
                            <?php if (hasRole(['admin'])): ?>
                                <small class="form-text text-warning">
                                    <i class="fas fa-info-circle"></i>
                                    <a href="<?php echo BASE_URL; ?>/modules/classes/setup.php" target="_blank">
                                        Aktifkan sistem manajemen kelas untuk kemudahan pengelolaan
                                    </a>
                                </small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="birth_place" class="form-label">Tempat Lahir</label>
                        <input type="text" class="form-control" id="birth_place" name="birth_place" value="<?php echo isset($_POST['birth_place']) ? $_POST['birth_place'] : $student['birth_place']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="birth_date" class="form-label">Tanggal Lahir</label>
                        <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo isset($_POST['birth_date']) ? $_POST['birth_date'] : $student['birth_date']; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Alamat</label>
                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo isset($_POST['address']) ? $_POST['address'] : $student['address']; ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Telepon</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : $student['phone']; ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="parent_name" class="form-label">Nama Orang Tua</label>
                        <input type="text" class="form-control" id="parent_name" name="parent_name" value="<?php echo isset($_POST['parent_name']) ? $_POST['parent_name'] : $student['parent_name']; ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="parent_phone" class="form-label">Telepon Orang Tua</label>
                    <input type="text" class="form-control" id="parent_phone" name="parent_phone" value="<?php echo isset($_POST['parent_phone']) ? $_POST['parent_phone'] : $student['parent_phone']; ?>">
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>