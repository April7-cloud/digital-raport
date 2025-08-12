<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

$pageTitle = 'Import Students';
require_once '../../config/config.php';
require_once '../../includes/header.php';

// Check if user has required role
if (!hasRole(['admin'])) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'message' => 'You do not have permission to access this page.'
    ];
    header('Location: ' . BASE_URL . '/403.php');
    exit;
}

// Connect to database
$database = new Database();
$db = $database->connect();

// Initialize variables
$importResult = null;
$errors = [];
$success = [];

// Process import form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    // Check if file was uploaded properly
    if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed. Please try again.';
    } else {
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $filename = $_FILES['import_file']['name'];
        $fileTmpPath = $_FILES['import_file']['tmp_name'];
        $fileSize = $_FILES['import_file']['size'];
        $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        // Validate file extension
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = 'Invalid file type. Please upload .xls, .xlsx, or .csv file.';
        } 
        // Validate file size (max 5MB)
        else if ($fileSize > 5 * 1024 * 1024) {
            $errors[] = 'File size exceeds maximum limit of 5MB.';
        } 
        else {
            // Process the file based on its type
            try {
                // Include PhpSpreadsheet library (assumed to be installed via Composer)
                require '../../vendor/autoload.php';
                
                // Initialize appropriate reader based on file extension
                if ($fileExtension === 'csv') {
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
                    // Configure CSV reader
                    $reader->setDelimiter(',');
                    $reader->setEnclosure('"');
                    $reader->setLineEnding("\r\n");
                    $reader->setSheetIndex(0);
                } else {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fileTmpPath);
                }
                
                // Load the spreadsheet
                $spreadsheet = $reader->load($fileTmpPath);
                $worksheet = $spreadsheet->getActiveSheet();
                
                // Get all rows as array
                $rows = $worksheet->toArray();
                
                // Skip header row
                $headerRow = array_shift($rows);
                
                // Expected columns (lowercase for case-insensitive matching)
                $expectedColumns = ['nis', 'name', 'gender', 'class', 'address', 'phone', 'parent_name'];
                $headerColumns = array_map('strtolower', $headerRow);
                
                // Validate header structure
                $missingColumns = array_diff($expectedColumns, $headerColumns);
                if (!empty($missingColumns)) {
                    $errors[] = 'File is missing required columns: ' . implode(', ', $missingColumns);
                } else {
                    // Map column indexes
                    $columnMap = [];
                    foreach ($expectedColumns as $column) {
                        $columnMap[$column] = array_search(strtolower($column), $headerColumns);
                    }
                    
                    // Begin transaction
                    $db->beginTransaction();
                    
                    // Import data row by row
                    $totalRows = count($rows);
                    $importedCount = 0;
                    $skippedCount = 0;
                    $errorCount = 0;
                    
                    foreach ($rows as $index => $row) {
                        // Skip empty rows
                        if (empty(array_filter($row))) {
                            continue;
                        }
                        
                        // Extract data using column mapping
                        $nis = trim($row[$columnMap['nis']]);
                        $name = trim($row[$columnMap['name']]);
                        $gender = trim($row[$columnMap['gender']]);
                        $class = trim($row[$columnMap['class']]);
                        $address = trim($row[$columnMap['address']]);
                        $phone = trim($row[$columnMap['phone']]);
                        $parentName = trim($row[$columnMap['parent_name']]);
                        
                        // Validate required fields
                        if (empty($nis) || empty($name) || empty($class)) {
                            $skippedCount++;
                            continue;
                        }
                        
                        // Check if student already exists (by NIS)
                        $checkStmt = $db->prepare("SELECT id FROM students WHERE nis = ?");
                        $checkStmt->execute([$nis]);
                        $existingStudent = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        try {
                            if ($existingStudent) {
                                // Update existing student
                                $stmt = $db->prepare("UPDATE students SET name = ?, gender = ?, class = ?, address = ?, phone = ?, parent_name = ?, updated_at = NOW() WHERE nis = ?");
                                $stmt->execute([$name, $gender, $class, $address, $phone, $parentName, $nis]);
                            } else {
                                // Insert new student
                                $stmt = $db->prepare("INSERT INTO students (nis, name, gender, class, address, phone, parent_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                                $stmt->execute([$nis, $name, $gender, $class, $address, $phone, $parentName]);
                            }
                            $importedCount++;
                        } catch (PDOException $e) {
                            $errorCount++;
                            // Log error but continue with other rows
                            error_log('Error importing student row ' . ($index + 2) . ': ' . $e->getMessage());
                        }
                    }
                    
                    // Commit transaction if no errors occurred
                    if ($errorCount === 0) {
                        $db->commit();
                        $success[] = "Import completed successfully. Imported: $importedCount, Skipped: $skippedCount";
                    } else {
                        $db->rollBack();
                        $errors[] = "Import failed. $errorCount errors occurred during import.";
                    }
                    
                    $importResult = [
                        'total' => $totalRows,
                        'imported' => $importedCount,
                        'skipped' => $skippedCount,
                        'errors' => $errorCount
                    ];
                }
            } catch (Exception $e) {
                $errors[] = 'Error processing file: ' . $e->getMessage();
                error_log('Import error: ' . $e->getMessage());
            }
        }
    }
}

?>

<div class="container-fluid">
    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    
    <!-- Display Errors -->
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Display Success -->
    <?php if (!empty($success)): ?>
        <?php foreach ($success as $msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Import Data Siswa</h1>
        <a href="index.php" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
        </a>
    </div>
            
    <!-- Import Result Summary -->
    <?php if ($importResult): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Hasil Import</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th>Total Baris</th>
                            <td><?php echo $importResult['total']; ?></td>
                        </tr>
                        <tr>
                            <th>Berhasil Diimport</th>
                            <td><?php echo $importResult['imported']; ?></td>
                        </tr>
                        <tr>
                            <th>Dilewati</th>
                            <td><?php echo $importResult['skipped']; ?></td>
                        </tr>
                        <tr>
                            <th>Error</th>
                            <td><?php echo $importResult['errors']; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Import Form Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Upload File</h6>
        </div>
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="import_file">File (Excel atau CSV)</label>
                    <input type="file" class="form-control-file" id="import_file" name="import_file" accept=".xls,.xlsx,.csv" required>
                    <small class="form-text text-muted">Format yang didukung: XLS, XLSX, CSV. Ukuran maksimum: 5MB.</small>
                </div>
                
                <div class="alert alert-info">
                    <h5>Format File Import</h5>
                    <p>File harus memiliki kolom berikut:</p>
                    <ul>
                        <li><strong>nis</strong> - Nomor Induk Siswa (wajib)</li>
                        <li><strong>name</strong> - Nama Lengkap (wajib)</li>
                        <li><strong>gender</strong> - Jenis Kelamin (L/P)</li>
                        <li><strong>class</strong> - Kelas (wajib)</li>
                        <li><strong>address</strong> - Alamat</li>
                        <li><strong>phone</strong> - Nomor Telepon</li>
                        <li><strong>parent_name</strong> - Nama Orang Tua/Wali</li>
                    </ul>
                    <p>Contoh file template dapat diunduh <a href="template/generate_template.php">di sini</a>.</p>
                </div>
                
                <button type="submit" name="import" class="btn btn-primary">
                    <i class="fas fa-file-import"></i> Import Data
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// Clear output buffer and send content
ob_end_flush();
?>
