<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

$pageTitle = 'Teachers';
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
$searchWhere = '';
$searchParams = [];

if (!empty($search)) {
    $searchWhere = "WHERE (nip LIKE ? OR name LIKE ? OR email LIKE ?)";
    $searchParams = ["%$search%", "%$search%", "%$search%"];
}

// Get total records for pagination
try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM teachers $searchWhere");
    if (!empty($searchParams)) {
        $countStmt->execute($searchParams);
    } else {
        $countStmt->execute();
    }
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
} catch(PDOException $e) {
    logError('Error getting teacher count: ' . $e->getMessage());
    $totalRecords = 0;
    $totalPages = 1;
}

// Get teachers with pagination
try {
    $stmt = $db->prepare("SELECT * FROM teachers $searchWhere ORDER BY name LIMIT $offset, $recordsPerPage");
    if (!empty($searchParams)) {
        $stmt->execute($searchParams);
    } else {
        $stmt->execute();
    }
    $teachers = $stmt->fetchAll();
} catch(PDOException $e) {
    setFlash('error', 'Error retrieving teachers: ' . $e->getMessage());
    logError('Error retrieving teachers: ' . $e->getMessage());
    $teachers = [];
}
?>

<div class="container-fluid">
    <!-- Flash Messages -->
    <?php if (hasFlash()): ?>
        <?php $flash = getFlash(); ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Header section -->
    <div class="d-flex justify-content-between mb-4">
        <h2><i class="fas fa-chalkboard-teacher me-2"></i> Data Guru</h2>
        <div>
            <a href="export.php?<?php echo http_build_query(['search' => $search]); ?>" 
               class="btn btn-success me-2" 
               title="Export to Excel">
                <i class="fas fa-file-export"></i> Export
            </a>
            <a href="create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Tambah Guru
            </a>
        </div>
    </div>

    <!-- Teachers table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Guru</h6>
            <form method="GET" action="" class="d-flex">
                <div class="input-group" style="width: 300px;">
                    <input type="text" id="search" name="search" class="form-control" placeholder="Cari guru..." value="<?php echo $search; ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
        <div class="card-body">
            <?php if (empty($teachers)): ?>
                <div class="alert alert-info">
                    <?php echo empty($search) ? 'Belum ada data guru.' : 'Tidak ada guru yang sesuai dengan pencarian.'; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="teachersTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>No. Telepon</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = $offset + 1;
                            foreach ($teachers as $teacher): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($teacher['nip'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['name']); ?></td>
                                <td><?php echo htmlspecialchars($teacher['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($teacher['phone'] ?? '-'); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $teacher['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $teacher['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm delete-btn" 
                                            data-id="<?php echo $teacher['id']; ?>" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal">
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
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                    First
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                    Previous
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                    Next
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">
                                    Last
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete this teacher?</div>
            <div class="modal-footer">
                <form id="deleteForm" action="../../api/teachers.php" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toast for notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 5">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i> <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
// Check for flash messages and show toast
document.addEventListener('DOMContentLoaded', function() {
    <?php if (hasFlash()): ?>
        <?php $flash = getFlash(); ?>
        <?php if ($flash['type'] === 'success'): ?>
            const toast = new bootstrap.Toast(document.getElementById('successToast'));
            document.getElementById('toastMessage').innerText = "<?php echo addslashes($flash['message']); ?>";
            toast.show();
            
            // Refresh the data table to show updated content
            setTimeout(function() {
                // Only refresh if not already from a refresh
                if (!window.location.href.includes('refreshed=1')) {
                    window.location.href = window.location.href + 
                        (window.location.search ? '&' : '?') + 'refreshed=1';
                }
            }, 1000);
        <?php endif; ?>
    <?php endif; ?>

    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            document.getElementById('deleteId').value = id;
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>