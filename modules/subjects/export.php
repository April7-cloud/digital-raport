<?php
// Export subjects data to CSV
require_once '../../config/config.php';
require_once '../../auth/session.php';  // Fix include path to correct file

// Check permission
if (!hasRole(['admin', 'teacher'])) {
    setFlash('error', 'Anda tidak memiliki izin untuk mengakses halaman ini.');
    redirect('../dashboard');
}

// Connect to database
$database = new Database();
$db = $database->connect();

// Get parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$class = isset($_GET['class']) ? sanitize($_GET['class']) : '';
$filename = 'subjects_export_' . date('Y-m-d_H-i-s') . '.csv';

// Query building
$query = "
    SELECT 
        s.id, s.code, s.name, s.description, s.class, s.kkm, s.academic_year,
        t.name AS teacher_name, t.nip AS teacher_nip
    FROM subjects s
    LEFT JOIN teachers t ON s.teacher_id = t.id
    WHERE 1
";

$params = [];

// Apply search filter if provided
if (!empty($search)) {
    $query .= " AND (
        s.code LIKE ? OR 
        s.name LIKE ? OR 
        s.description LIKE ? OR 
        s.class LIKE ? OR 
        t.name LIKE ? OR 
        t.nip LIKE ?
    )";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
}

// Apply class filter if provided
if (!empty($class)) {
    $query .= " AND s.class = ?";
    $params[] = $class;
}

$query .= " ORDER BY s.name";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create a file handle for php://output
    $output = fopen('php://output', 'w');
    
    // Set UTF-8 BOM for Excel to correctly display UTF-8 characters
    fputs($output, "\xEF\xBB\xBF");
    
    // Add CSV headers
    fputcsv($output, [
        'ID',
        'Kode',
        'Nama Mata Pelajaran',
        'Deskripsi',
        'Guru Pengajar',
        'NIP Guru',
        'Kelas',
        'KKM',
        'Tahun Ajaran',
        'Tanggal Dibuat'
    ]);
    
    // Add data rows
    foreach ($subjects as $subject) {
        fputcsv($output, [
            $subject['id'],
            $subject['code'],
            $subject['name'],
            $subject['description'],
            $subject['teacher_name'] ?? '-',
            $subject['teacher_nip'] ?? '-',
            $subject['class'],
            $subject['kkm'],
            $subject['academic_year'],
            isset($subject['created_at']) ? date('d-m-Y H:i', strtotime($subject['created_at'])) : '-'
        ]);
    }
    
    // Close the file handle
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    logError('Error exporting subjects: ' . $e->getMessage());
    setFlash('error', 'Terjadi kesalahan saat mengekspor data mata pelajaran.');
    redirect('index.php');
}
?>
