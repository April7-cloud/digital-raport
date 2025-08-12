<?php
$pageTitle = 'Data Kelas';
require_once '../../config/config.php';
require_once '../../includes/header.php';
require_once '../../includes/class_helpers.php';

// Check permission
if (!hasRole(['admin', 'teacher'])) {
    setFlash('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('../dashboard');
}

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Get search parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query to get distinct classes from subjects table
$query = "SELECT DISTINCT class, 
          (SELECT COUNT(*) FROM students WHERE students.class = subjects.class) as student_count 
          FROM subjects WHERE class IS NOT NULL AND class != ''";
$countQuery = "SELECT COUNT(DISTINCT class) FROM subjects WHERE class IS NOT NULL AND class != ''";
$params = [];

if (!empty($search)) {
    $query .= " AND class LIKE ?";
    $countQuery .= " AND class LIKE ?";
    $searchParam = "%$search%";
    $params = [$searchParam];
}

$query .= " ORDER BY class ASC LIMIT $offset, $perPage";

// Execute query
try {
    // Count total records
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $perPage);
    
    // Get records for current page
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $classes = [];
    $totalPages = 0;
    setFlash('error', 'Database error: ' . $e->getMessage());
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
                <h1 class="h3 mb-0 text-gray-800">Data Kelas</h1>
                <div>
                    <span class="text-info me-3"><i class="fas fa-info-circle"></i> Kelas dikelola melalui menu Mata Pelajaran</span>
                    <a href="../subjects/create.php" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Mata Pelajaran Baru
                    </a>
                </div>
            </div>
            
            <!-- Search Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <div class="form-group mb-0">
                                    <label for="search">Pencarian:</label>
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Cari berdasarkan kelas" 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group mb-0">
                                    <a href="index.php" class="btn btn-secondary btn-block">
                                        <i class="fas fa-sync"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Flash Messages -->
            <?php displayFlash(); ?>
            
            <!-- Info Card -->
            <div class="card bg-info text-white shadow mb-4">
                <div class="card-body">
                    <div class="text-white-50 small"><i class="fas fa-info-circle"></i> Informasi</div>
                    <div class="text-lg">Data kelas ini berasal dari kolom kelas pada tabel mata pelajaran. Untuk menambah atau mengubah kelas, silakan gunakan menu <a href="../subjects" class="text-white"><u>Mata Pelajaran</u></a>.</div>
                </div>
            </div>
            
            <!-- DataTales Example -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th>Nama Kelas</th>
                                    <th>Jumlah Siswa</th>
                                    <th>Jumlah Mata Pelajaran</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($classes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data kelas yang ditemukan</td>
                                </tr>
                                <?php else: ?>
                                    <?php 
                                    $no = ($page - 1) * $perPage + 1;
                                    foreach ($classes as $class): 
                                        // Count subjects in this class
                                        $subjectCountStmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE class = ?");
                                        $subjectCountStmt->execute([$class['class']]);
                                        $subjectCount = $subjectCountStmt->fetchColumn();
                                    ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo htmlspecialchars($class['class']); ?></td>
                                        <td><?php echo $class['student_count']; ?></td>
                                        <td><?php echo $subjectCount; ?></td>
                                        <td>
                                            <a href="../subjects/index.php?search=<?php echo urlencode($class['class']); ?>" class="btn btn-info btn-sm mb-1">
                                                <i class="fas fa-book"></i> Lihat Mata Pelajaran
                                            </a>
                                            <a href="../students/index.php?search=<?php echo urlencode($class['class']); ?>" class="btn btn-primary btn-sm mb-1">
                                                <i class="fas fa-user-graduate"></i> Lihat Siswa
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-3">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search=<?php echo urlencode($search); ?>">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $activeClass = ($i == $page) ? 'active' : '';
                                echo '<li class="page-item ' . $activeClass . '">';
                                echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search=<?php echo urlencode($search); ?>">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- /.container-fluid -->
    </div>
    <!-- End of Main Content -->
    
    <?php require_once '../../includes/footer.php'; ?>
</div>
<!-- End of Content Wrapper -->
