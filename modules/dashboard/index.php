<?php
$pageTitle = 'Dashboard';
$MODULE_PATH = dirname(__FILE__);

require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/header.php';

// Get counts from database
$database = new Database();
$db = $database->connect();

try {
    $studentsCount = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    $teachersCount = $db->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $subjectsCount = $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    $assessmentsCount = $db->query("SELECT COUNT(*) FROM assessments")->fetchColumn();
} catch (PDOException $e) {
    $studentsCount = $teachersCount = $subjectsCount = $assessmentsCount = 0;
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Dashboard</h1>
    
    <!-- Academic Info Card -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Tahun Ajaran: <?php echo CURRENT_ACADEMIC_YEAR; ?></h5>
            <h6 class="card-subtitle mb-2 text-muted">Semester: <?php echo CURRENT_SEMESTER; ?></h6>
        </div>
    </div>
    
    <!-- Cards Row -->
    <div class="row">
        <!-- Students Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Data Siswa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $studentsCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teachers Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Data Guru</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $teachersCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subjects Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mata Pelajaran</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $subjectsCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessments Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Penilaian</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $assessmentsCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(__DIR__)) . '/includes/footer.php'; ?>