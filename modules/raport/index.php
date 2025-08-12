<?php
// Define BASE_PATH to prevent 403 error in session.php
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

require_once BASE_PATH . '/includes/header.php';
require_once BASE_PATH . '/auth/session.php';
require_once BASE_PATH . '/config/database.php';

// Check if user has permission
if (!hasRole(['admin', 'guru'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

$pageTitle = "Raport Siswa";

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Get distinct classes from subjects
$classes = [];
try {
    $stmt = $db->query("SELECT DISTINCT class FROM subjects WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
    $classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Handle error
    $error = "Error: " . $e->getMessage();
}

// Get academic years for dropdown
$academicYears = [];
$currentYear = date('Y');
for ($i = 0; $i < 5; $i++) {
    $year = ($currentYear - 2 + $i) . '/' . ($currentYear - 1 + $i);
    $academicYears[] = $year;
}

$semesters = [
    '1' => 'Semester 1',
    '2' => 'Semester 2'
];

// Selected class, if any
$selectedClass = isset($_GET['class']) ? htmlspecialchars($_GET['class']) : '';
$selectedSemester = isset($_GET['semester']) ? htmlspecialchars($_GET['semester']) : '';
$selectedYear = isset($_GET['year']) ? htmlspecialchars($_GET['year']) : $academicYears[count($academicYears) - 1];

// Get students of selected class
$students = [];
if (!empty($selectedClass)) {
    try {
        $query = "SELECT s.* FROM students s 
                  JOIN subjects sub ON s.class = sub.class 
                  WHERE sub.class = :class 
                  GROUP BY s.id 
                  ORDER BY s.name ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':class', $selectedClass);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle error
        $error = "Error: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Raport Siswa</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light py-2 px-3 mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_PATH ?>/">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Raport</li>
            </ol>
        </nav>
    </div>

    <!-- Filter Cards -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Filter Raport</h6>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <!-- Class Selection -->
                <div class="col-md-4">
                    <label for="class" class="form-label">Kelas</label>
                    <select name="class" id="class" class="form-control" required>
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= htmlspecialchars($class) ?>" <?= $selectedClass == $class ? 'selected' : '' ?>>
                                <?= htmlspecialchars($class) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Semester Selection -->
                <div class="col-md-4">
                    <label for="semester" class="form-label">Semester</label>
                    <select name="semester" id="semester" class="form-control" required>
                        <option value="">Pilih Semester</option>
                        <?php foreach ($semesters as $key => $value): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $selectedSemester == $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars($value) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Academic Year Selection -->
                <div class="col-md-4">
                    <label for="year" class="form-label">Tahun Ajaran</label>
                    <select name="year" id="year" class="form-control" required>
                        <option value="">Pilih Tahun Ajaran</option>
                        <?php foreach ($academicYears as $year): ?>
                            <option value="<?= htmlspecialchars($year) ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                                <?= htmlspecialchars($year) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student List for Selected Class -->
    <?php if (!empty($selectedClass) && !empty($selectedSemester) && !empty($selectedYear)): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa Kelas <?= htmlspecialchars($selectedClass) ?></h6>
            </div>
            <div class="card-body">
                <?php if (!empty($students)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="studentsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $index => $student): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($student['nis']) ?></td>
                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                        <td>
                                            <a href="generate.php?student_id=<?= $student['id'] ?>&class=<?= $selectedClass ?>&semester=<?= $selectedSemester ?>&year=<?= $selectedYear ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="fas fa-file-alt"></i> Lihat Raport
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <img src="<?= BASE_PATH ?>/assets/img/empty_data.svg" alt="No Data" style="max-height: 150px;">
                        <h4 class="text-gray-500 mt-3">Tidak Ada Data Siswa</h4>
                        <p class="text-gray-500 mb-3">Tidak ada siswa terdaftar untuk kelas <?= htmlspecialchars($selectedClass) ?>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif (isset($_GET['class']) || isset($_GET['semester']) || isset($_GET['year'])): ?>
        <!-- Shown when filter is submitted but incomplete -->
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Silakan pilih Kelas, Semester, dan Tahun Ajaran untuk melihat daftar siswa.
        </div>
    <?php else: ?>
        <!-- Default state - no filter applied yet -->
        <div class="card shadow mb-4">
            <div class="card-body text-center py-5">
                <img src="<?= BASE_PATH ?>/assets/img/select_class.svg" alt="Select Class" style="max-height: 200px;" class="mb-3">
                <h4 class="text-gray-500">Pilih Kelas dan Semester</h4>
                <p class="text-gray-500">Silakan pilih kelas, semester, dan tahun ajaran untuk melihat daftar siswa dan mencetak raport.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#studentsTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
        }
    });
});
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
