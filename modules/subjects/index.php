<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

$pageTitle = 'Mata Pelajaran';
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Connect to database
$database = new Database();
$db = $database->connect();

// Pagination settings
$recordsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $recordsPerPage;

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$class = isset($_GET['class']) ? sanitize($_GET['class']) : '';
$searchWhere = '';
$searchParams = [];

if (!empty($search) || !empty($class)) {
    $searchWhere = "WHERE ";
    
    if (!empty($search)) {
        $searchWhere .= "(s.code LIKE ? OR s.name LIKE ? OR t.name LIKE ?)";
        $searchParams = ["%$search%", "%$search%", "%$search%"];
    }
    
    if (!empty($class)) {
        if (!empty($search)) {
            $searchWhere .= " AND ";
        }
        $searchWhere .= "s.class = ?";
        $searchParams[] = $class;
    }
}

// Get distinct classes for the filter dropdown
try {
    $classesStmt = $db->query("SELECT DISTINCT class FROM subjects ORDER BY class");
    $classes = $classesStmt->fetchAll(PDO::FETCH_COLUMN);
} catch(PDOException $e) {
    logError('Error getting subject classes: ' . $e->getMessage());
    $classes = [];
}

// Get total records for pagination
try {
    $countQuery = "SELECT COUNT(*) FROM subjects s 
                   LEFT JOIN teachers t ON s.teacher_id = t.id
                   $searchWhere";
    $countStmt = $db->prepare($countQuery);
    if (!empty($searchParams)) {
        $countStmt->execute($searchParams);
    } else {
        $countStmt->execute();
    }
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
} catch(PDOException $e) {
    logError('Error getting subject count: ' . $e->getMessage());
    $totalRecords = 0;
    $totalPages = 1;
}

// Get subjects with pagination and teacher information
try {
    $query = "SELECT s.*, t.name as teacher_name 
              FROM subjects s
              LEFT JOIN teachers t ON s.teacher_id = t.id
              $searchWhere
              ORDER BY s.name
              LIMIT $offset, $recordsPerPage";
    
    $stmt = $db->prepare($query);
    if (!empty($searchParams)) {
        $stmt->execute($searchParams);
    } else {
        $stmt->execute();
    }
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    logError('Error retrieving subjects: ' . $e->getMessage());
    $subjects = [];
    setFlash('error', 'Terjadi kesalahan saat mengambil data mata pelajaran.');
}
?>

<!-- Add jQuery at the top of the content wrapper, before any script that needs it -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Content Wrapper -->
<div id="content-wrapper" class="d-flex flex-column">
    <!-- Main Content -->
    <div id="content">
        
        <!-- Begin Page Content -->
        <div class="container-fluid">
            <!-- Flash Messages -->
            <?php if (hasFlash()): ?>
                <?php $flash = getFlash(); ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Page Heading -->
            <h1 class="h3 mb-4 text-gray-800">Data Mata Pelajaran</h1>
            
            <!-- DataTables Example -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Mata Pelajaran</h6>
                    <form method="GET" action="" class="d-flex">
                        <div class="input-group mr-2" style="width: 200px;">
                            <select name="class" class="form-control">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($classes as $cls): ?>
                                    <option value="<?php echo htmlspecialchars($cls); ?>" <?php echo ($class === $cls) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cls); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group" style="width: 300px;">
                            <input type="text" name="search" class="form-control bg-light border-0 small" placeholder="Cari mata pelajaran..." value="<?php echo htmlspecialchars($search); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="create.php" class="btn btn-success btn-sm">
                            <i class="fas fa-plus"></i> Tambah Mata Pelajaran
                        </a>
                        <?php if (!empty($subjects)): ?>
                            <a href="export.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo !empty($class) ? '&class=' . urlencode($class) : ''; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-download"></i> Export Data
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (empty($subjects)): ?>
                        <div class="alert alert-info">
                            <?php echo empty($search) && empty($class) ? 'Belum ada data mata pelajaran.' : 'Tidak ada mata pelajaran yang sesuai dengan pencarian.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="subjectsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Mata Pelajaran</th>
                                        <th>Kelas</th>
                                        <th>Guru Pengajar</th>
                                        <th>KKM</th>
                                        <th>Tahun Ajaran</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['class']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Tidak ditentukan'); ?></td>
                                            <td><?php echo htmlspecialchars($subject['kkm']); ?></td>
                                            <td><?php echo htmlspecialchars($subject['academic_year'] ?? '-'); ?></td>
                                            <td>
                                                <a href="view.php?id=<?php echo $subject['id']; ?>" class="btn btn-info btn-sm" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $subject['id']; ?>" class="btn btn-warning btn-sm" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $subject['id']; ?>" data-name="<?php echo htmlspecialchars($subject['name']); ?>" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class) ? '&class=' . urlencode($class) : ''; ?>" aria-label="Previous">
                                                    <span aria-hidden="true">&laquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $totalPages); $i++): ?>
                                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class) ? '&class=' . urlencode($class) : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($class) ? '&class=' . urlencode($class) : ''; ?>" aria-label="Next">
                                                    <span aria-hidden="true">&raquo;</span>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus mata pelajaran <strong id="delete-subject-name"></strong>?
                <p class="text-danger">Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait.</p>
            </div>
            <div class="modal-footer">
                <!-- Using a direct form submission instead of AJAX to avoid encoding issues -->
                <form id="delete-form" action="<?php echo BASE_URL; ?>/api/subjects.php" method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete-subject-id">
                    <input type="hidden" name="return_url" value="<?php echo BASE_URL; ?>/modules/subjects/index.php">
                    <?php echo csrfField(); ?>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast for notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 5;">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
        <div class="toast-header">
            <strong class="me-auto" id="toastTitle">Notifikasi</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

<script>
// Wait for document to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize delete buttons with click event
    document.querySelectorAll('.delete-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var name = this.getAttribute('data-name');
            document.getElementById('delete-subject-id').value = id;
            document.getElementById('delete-subject-name').innerText = name;
            
            // Create and show the modal using Bootstrap 5 API
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        });
    });
    
    // Show toast if there's a message from server
    <?php if (isset($_GET['message'])): ?>
    document.getElementById('toastMessage').innerText = '<?php echo htmlspecialchars($_GET['message']); ?>';
    document.getElementById('toastTitle').innerText = '<?php echo isset($_GET['success']) && $_GET['success'] == '1' ? 'Berhasil' : 'Peringatan'; ?>';
    var toast = new bootstrap.Toast(document.getElementById('liveToast'));
    toast.show();
    <?php endif; ?>
});
</script>

<?php
// End the output buffer and send output to browser
ob_end_flush();
?>
