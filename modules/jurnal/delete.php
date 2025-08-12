<?php
// Define BASE_PATH to prevent 403 error in session.php
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

require_once BASE_PATH . '/auth/session.php';
require_once BASE_PATH . '/config/database.php';

// Check if user has permission
if (!hasRole(['admin', 'guru'])) {
    $_SESSION['error'] = 'Anda tidak memiliki izin untuk mengakses halaman ini';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Check for CSRF token
if (!isset($_GET['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
    $_SESSION['error'] = 'Invalid CSRF token';
    header('Location: index.php');
    exit;
}

// Get journal ID
$journalId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($journalId <= 0) {
    $_SESSION['error'] = 'ID jurnal tidak valid';
    header('Location: index.php');
    exit;
}

// Get journal data to retrieve redirection parameters
try {
    $stmt = $db->prepare("SELECT class, subject_id, semester, academic_year FROM journal_entries WHERE id = ?");
    $stmt->execute([$journalId]);
    $journal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$journal) {
        $_SESSION['error'] = 'Entri jurnal tidak ditemukan';
        header('Location: index.php');
        exit;
    }
    
    // Start transaction
    $db->beginTransaction();
    
    // Delete assignments first (due to foreign key constraint)
    $stmt = $db->prepare("DELETE FROM journal_assignments WHERE journal_id = ?");
    $stmt->execute([$journalId]);
    
    // Delete journal entry
    $stmt = $db->prepare("DELETE FROM journal_entries WHERE id = ?");
    $stmt->execute([$journalId]);
    
    // Commit transaction
    $db->commit();
    
    $_SESSION['success_message'] = 'Entri jurnal berhasil dihapus';
    
    // Redirect back to the view page with appropriate filters
    header('Location: view.php?' . 
        'kelas=' . urlencode($journal['class']) . 
        '&subject_id=' . $journal['subject_id'] . 
        '&semester=' . $journal['semester'] . 
        '&academic_year=' . urlencode($journal['academic_year'])
    );
    exit;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    $_SESSION['error'] = 'Terjadi kesalahan saat menghapus entri jurnal: ' . $e->getMessage();
    error_log('Database error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
    
    header('Location: index.php');
    exit;
}
