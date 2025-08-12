<?php
// Prevent direct access
if (!defined('BASE_PATH')) {
    http_response_code(403);
    die('Forbidden');
}
?>
<div class="sidebar d-none d-md-flex flex-column">
    <div class="sidebar-brand">
        <i class="fas fa-graduation-cap me-2"></i>
        <?php echo SITE_NAME; ?>
    </div>
    <hr class="my-0 bg-white opacity-25">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/students/">
                <i class="fas fa-user-graduate"></i> Data Siswa
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/teachers/">
                <i class="fas fa-chalkboard-teacher"></i> Data Guru
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/subjects/">
                <i class="fas fa-book"></i> Mata Pelajaran
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/penilaian/">
                <i class="fas fa-chart-bar"></i> Penilaian
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/jurnal/">
                <i class="fas fa-journal-whills"></i> Jurnal Kelas
            </a>
        </li>
        
        <li class="nav-item">
            <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/raport/">
                <i class="fas fa-file-alt"></i> Raport
            </a>
        </li>
    </ul>
</div>