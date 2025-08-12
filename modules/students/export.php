<?php
require_once '../../config/config.php';

// Check if user has permission to export
if (!hasRole(['admin', 'teacher'])) {
    http_response_code(403);
    die('Access Denied');
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$class = isset($_GET['class']) ? sanitize($_GET['class']) : '';

// Build query
$query = "SELECT * FROM students WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR nis LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($class)) {
    $query .= " AND class = ?";
    $params[] = $class;
}

$query .= " ORDER BY name";

try {
    $database = new Database();
    $db = $database->connect();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        die('No data to export');
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fputs($output, "\xEF\xBB\xBF");
    
    // Add headers
    fputcsv($output, [
        'NIS',
        'Nama',
        'Jenis Kelamin',
        'Kelas',
        'Tempat Lahir',
        'Tanggal Lahir',
        'Alamat',
        'Telepon',
        'Nama Orang Tua',
        'Telepon Orang Tua'
    ]);
    
    // Add data rows
    foreach ($students as $student) {
        fputcsv($output, [
            $student['nis'],
            $student['name'],
            $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan',
            $student['class'],
            $student['birth_place'],
            $student['birth_date'],
            $student['address'],
            $student['phone'],
            $student['parent_name'],
            $student['parent_phone']
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    logError('Export error: ' . $e->getMessage());
    die('Error generating export');
}
