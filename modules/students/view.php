<?php
$pageTitle = 'Student Details';
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setFlash('error', 'Student ID is required');
    redirect(BASE_URL . '/modules/students/index.php');
}

$id = (int)$_GET['id'];
$database = new Database();
$db = $database->connect();
$student = null;

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

// Get student's assessments
try {
    $stmt = $db->prepare("
        SELECT a.*, s.name as subject_name
        FROM assessments a
        JOIN subjects s ON a.subject_id = s.id
        WHERE a.student_id = ?
        ORDER BY a.date DESC
    ");
    $stmt->execute([$id]);
    $assessments = $stmt->fetchAll();
} catch (PDOException $e) {
    $assessments = [];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-user-graduate me-2"></i> Student Details</h2>
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
                    <h6 class="m-0 font-weight-bold text-primary">Student Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">NISN</th>
                            <td><?php echo $student['nisn']; ?></td>
                        </tr>
                        <tr>
                            <th>Nama</th>
                            <td><?php echo $student['name']; ?></td>
                        </tr>
                        <tr>
                            <th>Kelas</th>
                            <td><?php echo $student['class']; ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Kelamin</th>
                            <td><?php echo $student['gender'] == 'male' ? 'Laki-laki' : 'Perempuan'; ?></td>
                        </tr>
                        <tr>
                            <th>Tempat, Tanggal Lahir</th>
                            <td>
                                <?php 
                                echo $student['birth_place']; 
                                if (!empty($student['birth_date'])) {
                                    echo ', ' . date('d F Y', strtotime($student['birth_date']));
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Alamat</th>
                            <td><?php echo $student['address']; ?></td>
                        </tr>
                        <tr>
                            <th>Telepon</th>
                            <td><?php echo $student['phone']; ?></td>
                        </tr>
                        <tr>
                            <th>Orang Tua</th>
                            <td><?php echo $student['parent_name']; ?></td>
                        </tr>
                        <tr>
                            <th>Telepon Orang Tua</th>
                            <td><?php echo $student['parent_phone']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assessment History</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($assessments)): ?>
                        <div class="text-center">No assessments found</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Type</th>
                                        <th>Score</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assessments as $assessment): ?>
                                        <tr>
                                            <td><?php echo $assessment['subject_name']; ?></td>
                                            <td><?php echo $assessment['assessment_type']; ?></td>
                                            <td><?php echo $assessment['score']; ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($assessment['date'])); ?></td>
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