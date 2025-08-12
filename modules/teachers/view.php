<?php
$pageTitle = 'Teacher Details';
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

// Get teacher's subjects
try {
    $stmt = $db->prepare("
        SELECT * FROM subjects 
        WHERE teacher_id = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$id]);
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    $subjects = [];
    logError("Error fetching subjects for teacher ID $id: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-chalkboard-teacher me-2"></i> Teacher Details</h2>
        <div>
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
                <i class="fas fa-edit me-2"></i>Edit
            </a>
            <a href="index.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Teacher Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">NIP</th>
                            <td><?php echo htmlspecialchars($teacher['nip'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Nama</th>
                            <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo htmlspecialchars($teacher['email'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Telepon</th>
                            <td><?php echo htmlspecialchars($teacher['phone'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td><?php echo htmlspecialchars($teacher['address'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal Registrasi</th>
                            <td>
                                <?php
                                if (!empty($teacher['created_at'])) {
                                    echo date('d F Y', strtotime($teacher['created_at']));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assigned Subjects</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($subjects)): ?>
                        <div class="text-center">No subjects assigned to this teacher</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Mata Pelajaran</th>
                                        <th>Kelas</th>
                                        <th>KKM</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach ($subjects as $subject): 
                                    ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['class'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($subject['kkm'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
