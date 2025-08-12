<?php
require_once '../../config/config.php';

// Check if user has permission to export
if (!hasRole(['admin', 'teacher'])) {
    http_response_code(403);
    die('Access Denied');
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT * FROM teachers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR nip LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY name";

try {
    $database = new Database();
    $db = $database->connect();
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($teachers)) {
        die('No data to export');
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=teachers_export_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fputs($output, "\xEF\xBB\xBF");
    
    // Add headers
    fputcsv($output, [
        'NIP',
        'Nama',
        'Email',
        'Telepon',
        'Alamat'
    ]);
    
    // Add data rows
    foreach ($teachers as $teacher) {
        fputcsv($output, [
            $teacher['nip'] ?? '',
            $teacher['name'] ?? '',
            $teacher['email'] ?? '',
            $teacher['phone'] ?? '',
            $teacher['address'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
    
} catch (PDOException $e) {
    logError('Export error: ' . $e->getMessage());
    die('Error generating export');
}
