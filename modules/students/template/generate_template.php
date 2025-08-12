<?php
// Start output buffering to prevent "headers already sent" issues
ob_start();

// We'll make this file work standalone without requiring session.php
// Include PhpSpreadsheet library using composer autoload
if (file_exists('../../../vendor/autoload.php')) {
    require '../../../vendor/autoload.php';
} else {
    // Fallback if the path is wrong
    die("Composer autoload not found. Please install dependencies.");
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Create a new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Students Template');

// Set headers (first row)
$headers = [
    'nis', 'name', 'gender', 'class', 'address', 'phone', 'parent_name'
];
$col = 1;
foreach ($headers as $header) {
    $colLetter = Coordinate::stringFromColumnIndex($col);
    $sheet->setCellValue($colLetter . '1', $header);
    $col++;
}

// Format header row
$lastColumn = Coordinate::stringFromColumnIndex(count($headers));
$headerRange = 'A1:' . $lastColumn . '1';
$sheet->getStyle($headerRange)->getFont()->setBold(true);
$sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD3D3D3');

// Add example data
$exampleData = [
    ['123456', 'John Doe', 'L', '10A', '123 Main St', '555-1234', 'Jane Doe'],
    ['654321', 'Jane Smith', 'P', '10B', '456 Elm St', '555-5678', 'John Smith'],
    ['', '', '', '', '', '', '']
];

$row = 2;
foreach ($exampleData as $dataRow) {
    $col = 1;
    foreach ($dataRow as $value) {
        $colLetter = Coordinate::stringFromColumnIndex($col);
        $sheet->setCellValue($colLetter . $row, $value);
        $col++;
    }
    $row++;
}

// Auto size columns
for ($col = 1; $col <= count($headers); $col++) {
    $colLetter = Coordinate::stringFromColumnIndex($col);
    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
}

// Add comments/notes to header cells
$sheet->getComment('A1')->getText()->createTextRun('Nomor Induk Siswa (Required)');
$sheet->getComment('B1')->getText()->createTextRun('Student Full Name (Required)');
$sheet->getComment('C1')->getText()->createTextRun('Gender: L for Male, P for Female (Required)');
$sheet->getComment('D1')->getText()->createTextRun('Class/Grade (Required)');
$sheet->getComment('E1')->getText()->createTextRun('Home Address (Optional)');
$sheet->getComment('F1')->getText()->createTextRun('Phone Number (Optional)');
$sheet->getComment('G1')->getText()->createTextRun('Parent/Guardian Name (Optional)');

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="student_import_template.xlsx"');
header('Cache-Control: max-age=0');

// Save file to output
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
