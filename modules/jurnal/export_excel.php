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

// Get parameters with defaults
$semester = isset($_GET['semester']) ? $_GET['semester'] : '1';
$academicYear = isset($_GET['academic_year']) ? $_GET['academic_year'] : date('Y') . '/' . (date('Y') + 1);
$subjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
$studentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Set file name with timestamp
$timestamp = date('Ymd_His');
$fileName = "jurnal_kelas_{$kelas}_{$timestamp}.xls";

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

// Get subject data
$subject = [];
if ($subjectId > 0) {
    $stmt = $db->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$subjectId]);
    $subject = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get student data if student_id is provided
$student = [];
if ($studentId > 0) {
    $stmt = $db->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get journal entries
$entries = [];
$whereConditions = [];
$params = [];

if (!empty($kelas)) {
    // Use case-insensitive comparison for better matching
    $whereConditions[] = "LOWER(REPLACE(j.class, ' ', '')) = LOWER(REPLACE(?, ' ', ''))";
    $params[] = $kelas;
}

if ($subjectId > 0) {
    $whereConditions[] = "j.subject_id = ?";
    $params[] = $subjectId;
}

if ($studentId > 0) {
    $whereConditions[] = "j.student_id = ?";
    $params[] = $studentId;
}

if (!empty($semester)) {
    $whereConditions[] = "j.semester = ?";
    $params[] = $semester;
}

if (!empty($academicYear)) {
    $whereConditions[] = "j.academic_year = ?";
    $params[] = $academicYear;
}

if (!empty($startDate) && !empty($endDate)) {
    $whereConditions[] = "j.entry_date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

// Build and execute query
$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

$query = "
    SELECT j.*, s.name as student_name, s.nisn, sub.name as subject_name
    FROM journal_entries j
    LEFT JOIN students s ON j.student_id = s.id
    LEFT JOIN subjects sub ON j.subject_id = sub.id
    $whereClause
    ORDER BY j.entry_date DESC, j.entry_time DESC, s.name ASC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch assignments for each entry
foreach ($entries as &$entry) {
    $stmt = $db->prepare("
        SELECT * FROM journal_assignments 
        WHERE journal_id = ? 
        ORDER BY id ASC
    ");
    $stmt->execute([$entry['id']]);
    $entry['assignments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// HTML output for Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Export Jurnal Kelas</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            padding: 10px;
        }
        .sub-header {
            font-size: 14px;
            font-weight: bold;
            padding: 5px;
        }
        .success {
            background-color: #d4edda;
        }
        .warning {
            background-color: #fff3cd;
        }
        .danger {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <!-- Title and Filter Info -->
    <div class="header">
        JURNAL KELAS
        <br>
        <?= htmlspecialchars($kelas ? $kelas : ($student ? $student['class'] : '')) ?>
        <?= $subject ? " - " . htmlspecialchars($subject['name']) : "" ?>
        <br>
        <?= $semester === '1' ? 'Semester 1' : 'Semester 2' ?> - 
        Tahun Ajaran <?= htmlspecialchars($academicYear) ?>
        <br>
        Periode: <?= date('d/m/Y', strtotime($startDate)) ?> - <?= date('d/m/Y', strtotime($endDate)) ?>
    </div>
    
    <?php if ($studentId > 0 && $student): ?>
    <div style="margin: 10px 0;">
        <table>
            <tr>
                <th style="width: 15%">Nama Siswa</th>
                <td><?= htmlspecialchars($student['name']) ?></td>
                <th style="width: 15%">NISN</th>
                <td><?= htmlspecialchars($student['nisn']) ?></td>
            </tr>
            <tr>
                <th>Kelas</th>
                <td><?= htmlspecialchars($student['class']) ?></td>
                <th>Jumlah Entri</th>
                <td><?= count($entries) ?></td>
            </tr>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (count($entries) > 0): ?>
        <?php foreach ($entries as $entryIndex => $entry): ?>
            <div style="margin-top: 20px;">
                <div class="sub-header">
                    Entri #<?= $entryIndex + 1 ?> - 
                    <?= date('d F Y', strtotime($entry['entry_date'])) ?> | 
                    <?= date('H:i', strtotime($entry['entry_time'])) ?>
                    <?php if (empty($studentId)): ?> | 
                    <?= htmlspecialchars($entry['student_name']) ?>
                    <?php endif; ?>
                </div>
                
                <table>
                    <tr>
                        <th style="width: 15%">Nama Siswa</th>
                        <td><?= htmlspecialchars($entry['student_name']) ?></td>
                        <th style="width: 15%">NISN</th>
                        <td><?= htmlspecialchars($entry['nisn']) ?></td>
                    </tr>
                    <tr>
                        <th>Kelas</th>
                        <td><?= htmlspecialchars($entry['class']) ?></td>
                        <th>Mata Pelajaran</th>
                        <td><?= htmlspecialchars($entry['subject_name']) ?></td>
                    </tr>
                </table>
                
                <div class="sub-header">Daftar Tugas:</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 5%">No</th>
                            <th style="width: 40%">Nama Tugas</th>
                            <th>Tanggal</th>
                            <th>Nilai</th>
                            <th>Nilai Maks</th>
                            <th>Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($entry['assignments'])): ?>
                            <?php foreach ($entry['assignments'] as $aIndex => $assignment): ?>
                                <?php 
                                $percentage = 0;
                                $class = '';
                                
                                if ($assignment['score'] !== null) {
                                    $percentage = ($assignment['score'] / $assignment['max_score']) * 100;
                                    $class = $percentage >= 75 ? 'success' : ($percentage >= 60 ? 'warning' : 'danger');
                                }
                                ?>
                                <tr class="<?= $class ?>">
                                    <td><?= $aIndex + 1 ?></td>
                                    <td><?= htmlspecialchars($assignment['assignment_name']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($assignment['assignment_date'])) ?></td>
                                    <td><?= $assignment['score'] !== null ? htmlspecialchars($assignment['score']) : '-' ?></td>
                                    <td><?= htmlspecialchars($assignment['max_score']) ?></td>
                                    <td>
                                        <?= $assignment['score'] !== null ? number_format($percentage, 1) . '%' : 'Belum dinilai' ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Tidak ada data tugas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if (!empty($entry['notes'])): ?>
                    <div style="margin-top: 10px;">
                        <table>
                            <tr>
                                <th>Catatan untuk Siswa</th>
                            </tr>
                            <tr>
                                <td><?= nl2br(htmlspecialchars($entry['notes'])) ?></td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="text-align: center; margin-top: 20px;">
            <h3>Tidak ada data jurnal yang ditemukan.</h3>
        </div>
    <?php endif; ?>
</body>
</html>
