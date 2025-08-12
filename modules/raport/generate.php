<?php
// Define BASE_PATH to prevent 403 error in session.php
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));

require_once BASE_PATH . '/auth/session.php';
require_once BASE_PATH . '/config/database.php';

// Check if user has permission
if (!hasRole(['admin', 'guru'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// Check required parameters
if (!isset($_GET['student_id']) || !isset($_GET['class']) || !isset($_GET['semester']) || !isset($_GET['year'])) {
    die("Error: Missing required parameters.");
}

// Initialize variables
$student_id = intval($_GET['student_id']);
$class = htmlspecialchars($_GET['class']);
$semester = htmlspecialchars($_GET['semester']);
$year = htmlspecialchars($_GET['year']);
$language = isset($_GET['lang']) ? htmlspecialchars($_GET['lang']) : 'id'; // Default to Indonesian

// Initialize database connection
$database = new Database();
$db = $database->connect();

// Get student data
try {
    $query = "SELECT * FROM students WHERE id = :id LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $student_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        die("Error: Student not found.");
    }
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get subjects and grades for this student
try {
    $query = "SELECT s.id as subject_id, s.name, s.code, s.kkm, 
              g.grade as score
              FROM subjects s
              LEFT JOIN grades g ON s.id = g.subject_id AND g.student_id = :student_id
              WHERE s.class = :class
              ORDER BY s.name ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':class', $class);
    $stmt->execute();
    
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get attendance data
// In a real application, you would fetch this from an attendance table
// For this example, we'll use placeholder data
$attendance = [
    'sick' => 0,
    'permission' => 0,
    'absence' => 0
];

// Get extracurricular activities
// In a real application, you would fetch this from an extracurricular table
// For this example, we'll use placeholder data
$extracurricular = [
    [
        'name' => 'Hizbul Wathan',
        'description' => 'Peserta didik dapat mengikuti kegiatan dengan baik dan teratur, disiplin, mau bekerjasama, dan memahami materi dengan cukup baik.'
    ],
    [
        'name' => 'Tapak Suci',
        'description' => 'Peserta didik dapat mengikuti latihan secara teratur, disiplin, menguasai gerakan dasar dengan baik dan mampu bekerjasama.'
    ],
    [
        'name' => 'Khat',
        'description' => 'Peserta didik dapat aktif, disiplin, dan menunjukkan keterampilan tinggi dalam menulis kaligrafi. Hasil karya rapi, artistik, dan sesuai kaidah khat.'
    ]
];

// Get teacher/academic advisor notes
// In a real application, you would fetch this from a notes table
// For this example, we'll use placeholder data
$advisor_notes = "Ananda sangat baik dalam mengikuti pembelajaran dalam kelas, akan tetapi masih butuh peningkatan dalam materi tertentu seperti Matematika dan Bahasa Inggris sehingga diharapkan wali dapat membantu ananda dalam bentuk dukungan dan semangat.";

// School information
$school_info = [
    'name' => 'PONDOK PESANTREN INTERNASIONAL ABDUL MALIK FADJAR',
    'name_en' => 'ABDUL MALIK FADJAR INTERNATIONAL ISLAMIC BOARDING SCHOOL',
    'address' => 'Jl. Pangestu, Dsn. Telasih, Desa Kepuharjo, Kec. Karangploso Kab. Malang, Jawa Timur 65152',
    'email' => 'ppi.amf.malang@gmail.com',
    'website' => 'www.amf-ibs.id',
    'phone' => '0811-2919-9998',
    'logo' => 'path/to/school_logo.png', // Update with actual logo path
    'principal' => [
        'name' => 'KH. Fahri, S.Ag., M.M.',
        'id' => 'NBM. 899103'
    ],
    'academic_advisor' => [
        'name' => 'Nuril Dwi Maulida, Lc., M.A.',
        'id' => 'NBM. -'
    ]
];

// Current date for report generation
$report_date = 'Malang, ' . date('d F Y');
if ($language == 'en') {
    // Convert to English date format
    $report_date = 'Malang, ' . date('F d\t\h Y');
}

// Helper function to determine grade predicate
function getPredicate($score, $language = 'id') {
    if ($score >= 90) {
        return $language == 'id' ? 'A (Sangat Baik)' : 'A (Excellent)';
    } elseif ($score >= 80) {
        return $language == 'id' ? 'B (Baik)' : 'B (Very Good)';
    } elseif ($score >= 70) {
        return $language == 'id' ? 'C (Cukup)' : 'C (Good)';
    } elseif ($score >= 60) {
        return $language == 'id' ? 'D (Kurang)' : 'D (Fair)';
    } else {
        return $language == 'id' ? 'E (Sangat Kurang)' : 'E (Poor)';
    }
}

// Helper function to get letter grade only
function getGradeLetter($score) {
    if ($score >= 90) {
        return 'A';
    } elseif ($score >= 80) {
        return 'B';
    } elseif ($score >= 70) {
        return 'C';
    } elseif ($score >= 60) {
        return 'D';
    } else {
        return 'E';
    }
}

// Set document title
$title = 'Raport ' . $student['name'] . ' - ' . $class . ' - ' . $semester . ' - ' . $year;
?>
<!DOCTYPE html>
<html lang="<?= $language ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            font-size: 12pt;
        }
        .raport-container {
            width: 21cm;
            min-height: 29.7cm;
            padding: 1cm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
        }
        .school-logo {
            position: absolute;
            left: 0;
            top: 0;
            width: 80px;
        }
        .header h2, .header h3 {
            margin: 0;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0;
            font-size: 10pt;
        }
        .title {
            text-align: center;
            margin: 20px 0;
            text-decoration: underline;
            font-weight: bold;
        }
        .student-info {
            margin-bottom: 20px;
        }
        .student-info table {
            width: 100%;
            border: none;
        }
        .student-info td {
            padding: 3px;
            vertical-align: top;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .grades-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .attendance-table, .extracurricular-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .attendance-table th, .attendance-table td,
        .extracurricular-table th, .extracurricular-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }
        .notes {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 20px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature {
            width: 30%;
            text-align: center;
        }
        .signature-line {
            margin-top: 60px;
            border-bottom: 1px solid #000;
        }
        .page-break {
            page-break-before: always;
        }
        .text-muted {
            color: #6c757d;
        }
        .no-print {
            margin: 20px 0;
        }
        @media print {
            body {
                background: white;
            }
            .raport-container {
                box-shadow: none;
                margin: 0;
                padding: 0.5cm;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="no-print text-center mb-3">
        <div class="btn-group">
            <a href="generate.php?student_id=<?= $student_id ?>&class=<?= $class ?>&semester=<?= $semester ?>&year=<?= $year ?>&lang=id" class="btn btn-outline-primary <?= $language == 'id' ? 'active' : '' ?>">Bahasa Indonesia</a>
            <a href="generate.php?student_id=<?= $student_id ?>&class=<?= $class ?>&semester=<?= $semester ?>&year=<?= $year ?>&lang=en" class="btn btn-outline-primary <?= $language == 'en' ? 'active' : '' ?>">English</a>
        </div>
        <button class="btn btn-primary ms-2" onclick="window.print()">
            <i class="fas fa-print"></i> <?= $language == 'id' ? 'Cetak Raport' : 'Print Report Card' ?>
        </button>
        <a href="index.php" class="btn btn-secondary ms-2">
            <i class="fas fa-arrow-left"></i> <?= $language == 'id' ? 'Kembali' : 'Back' ?>
        </a>
    </div>

    <div class="raport-container">
        <!-- Header/Letterhead -->
        <div class="header">
            <img src="<?= BASE_PATH ?>/assets/img/school_logo.png" alt="School Logo" class="school-logo">
            <h3><?= $language == 'id' ? 'MAJELIS DIKDASMEN PIMPINAN WILAYAH MUHAMMADIYAH JAWA TIMUR' : 'COUNCIL FOR ELEMENTARY, SECONDARY AND NON-FORMAL EDUCATION' ?></h3>
            <h2><?= $language == 'id' ? $school_info['name'] : $school_info['name_en'] ?></h2>
            <p><?= $school_info['address'] ?></p>
            <p>Email: <?= $school_info['email'] ?> | <?= $school_info['website'] ?></p>
        </div>

        <!-- Report Title -->
        <div class="title">
            <?php if ($language == 'id'): ?>
                LAPORAN HASIL CAPAIAN KOMPETENSI PESERTA DIDIK
            <?php else: ?>
                STUDENT'S LEARNING ACHIEVEMENT REPORT
            <?php endif; ?>
        </div>

        <!-- Student Information -->
        <div class="student-info">
            <table>
                <tr>
                    <td width="150"><?= $language == 'id' ? 'Nama Lengkap' : 'Full Name' ?></td>
                    <td width="10">:</td>
                    <td><?= strtoupper($student['name']) ?></td>
                </tr>
                <tr>
                    <td>NIS</td>
                    <td>:</td>
                    <td><?= $student['nis'] ?></td>
                </tr>
                <tr>
                    <td>NISN</td>
                    <td>:</td>
                    <td><?= $student['nisn'] ?? '-' ?></td>
                </tr>
                <tr>
                    <td><?= $language == 'id' ? 'Semester' : 'Semester' ?></td>
                    <td>:</td>
                    <td><?= $semester ?></td>
                </tr>
                <tr>
                    <td><?= $language == 'id' ? 'Tahun Ajaran' : 'Academic Year' ?></td>
                    <td>:</td>
                    <td><?= $year ?></td>
                </tr>
            </table>
        </div>

        <!-- Academic Results -->
        <div class="section-title">
            <?= $language == 'id' ? 'A. KOMPETENSI PENGETAHUAN DAN KETERAMPILAN' : 'A. COGNITIVE AND SKILL' ?>
        </div>

        <table class="grades-table">
            <thead>
                <tr>
                    <th width="5%">NO</th>
                    <th width="40%"><?= $language == 'id' ? 'SUBJEK' : 'SUBJECT' ?></th>
                    <th width="10%"><?= $language == 'id' ? 'KKTP' : 'SLO' ?></th>
                    <th width="15%"><?= $language == 'id' ? 'NILAI' : 'SCORE' ?></th>
                    <th width="10%"><?= $language == 'id' ? 'PREDIKAT' : 'PREDICATE' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($subjects) > 0): ?>
                    <?php foreach($subjects as $index => $subject): ?>
                        <?php 
                            $score = $subject['score'] ?? 0;
                            $letterGrade = getGradeLetter($score);
                            $predicate = $language == 'id' ? 
                                ($letterGrade == 'A' ? 'Sangat Baik' : 
                                ($letterGrade == 'B' ? 'Baik' : 
                                ($letterGrade == 'C' ? 'Cukup' : 
                                ($letterGrade == 'D' ? 'Kurang' : 'Sangat Kurang')))) : 
                                ($letterGrade == 'A' ? 'Excellent' : 
                                ($letterGrade == 'B' ? 'Very Good' : 
                                ($letterGrade == 'C' ? 'Good' : 
                                ($letterGrade == 'D' ? 'Fair' : 'Poor'))));
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td style="text-align: left"><?= $subject['name'] ?></td>
                            <td><?= $subject['kkm'] ?? 75 ?></td>
                            <td><?= number_format($score, 2) ?></td>
                            <td><?= $letterGrade ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5"><?= $language == 'id' ? 'Tidak ada data nilai' : 'No grade data available' ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <p class="text-muted">
            <?= $language == 'id' ? 'Catatan : <br>KKTP = Kriteria Ketercapaian Tujuan' : 'Note : <br>SLO = Standard of Learning Objective' ?>
        </p>

        <!-- Attendance -->
        <div class="section-title">
            <?= $language == 'id' ? 'B. KEHADIRAN' : 'B. ATTENDANCE' ?>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th><?= $language == 'id' ? 'KEHADIRAN' : 'ATTENDANCE' ?></th>
                    <th width="20%"><?= $language == 'id' ? 'HARI' : 'DAYS' ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td style="text-align: left"><?= $language == 'id' ? 'Sakit' : 'Sickness' ?></td>
                    <td><?= $attendance['sick'] ?></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td style="text-align: left"><?= $language == 'id' ? 'Izin' : 'Permission' ?></td>
                    <td><?= $attendance['permission'] ?></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td style="text-align: left"><?= $language == 'id' ? 'Tanpa Keterangan' : 'Absence' ?></td>
                    <td><?= $attendance['absence'] ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Academic Advisor Notes -->
        <div class="section-title">
            <?= $language == 'id' ? 'C. CATATAN WALI KELAS' : 'C. ACADEMIC ADVISOR NOTES' ?>
        </div>

        <div class="notes">
            <?php if ($language == 'id'): ?>
                <?= $advisor_notes ?>
            <?php else: ?>
                <?= "The student is quite active and enthusiastic in the learning process" ?>
            <?php endif; ?>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature">
                <?= $language == 'id' ? 'Orang Tua/Wali Santri' : 'Parents' ?>
                <div class="signature-line"></div>
                <p><?= $student['parent_name'] ?? '(................................)' ?></p>
            </div>
            
            <div class="signature">
                <?= $report_date ?><br>
                <?= $language == 'id' ? 'Wali Kelas' : 'Academic Advisor' ?>
                <div class="signature-line"></div>
                <p><?= $school_info['academic_advisor']['name'] ?><br>
                <?= $school_info['academic_advisor']['id'] ?></p>
            </div>
            
            <div class="signature">
                <?= $language == 'id' ? 'Kepala Sekolah' : 'School Principal' ?>
                <div class="signature-line"></div>
                <p><?= $school_info['principal']['name'] ?><br>
                <?= $school_info['principal']['id'] ?></p>
            </div>
        </div>
    </div>

    <?php if (count($extracurricular) > 0): ?>
    <!-- Page 2 - Extracurricular (for high school) -->
    <div class="raport-container page-break">
        <!-- Header/Letterhead -->
        <div class="header">
            <img src="<?= BASE_PATH ?>/assets/img/school_logo.png" alt="School Logo" class="school-logo">
            <h3><?= $language == 'id' ? 'PIMPINAN WILAYAH MUHAMMADIYAH JAWA TIMUR' : 'MUHAMMADIYAH EAST JAVA' ?></h3>
            <h2><?= $language == 'id' ? 'SMA MUHAMMADIYAH ABDUL MALIK FADJAR' : 'SMA MUHAMMADIYAH ABDUL MALIK FADJAR' ?></h2>
            <p><?= $school_info['address'] ?></p>
            <p>Telp. <?= $school_info['phone'] ?> • e-mail: <?= $school_info['email'] ?> | <?= $school_info['website'] ?></p>
        </div>

        <!-- Extracurricular -->
        <div class="section-title">
            <?= $language == 'id' ? 'B. EKSTRAKURIKULER' : 'B. EXTRACURRICULAR' ?>
        </div>

        <table class="extracurricular-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="25%"><?= $language == 'id' ? 'Ekstrakurikuler' : 'Extracurricular' ?></th>
                    <th><?= $language == 'id' ? 'Keterangan' : 'Description' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($extracurricular as $index => $activity): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= $activity['name'] ?></td>
                        <td style="text-align: left"><?= $activity['description'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Achievements (if any) -->
        <div class="section-title">
            <?= $language == 'id' ? 'C. PRESTASI' : 'C. ACHIEVEMENTS' ?>
        </div>

        <table class="extracurricular-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="25%"><?= $language == 'id' ? 'Jenis Prestasi' : 'Type of Achievement' ?></th>
                    <th><?= $language == 'id' ? 'Keterangan' : 'Description' ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>-</td>
                    <td>-</td>
                </tr>
            </tbody>
        </table>

        <!-- Attendance (repeat) -->
        <div class="section-title">
            <?= $language == 'id' ? 'D. KEHADIRAN' : 'D. ATTENDANCE' ?>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th><?= $language == 'id' ? 'Kehadiran' : 'Attendance' ?></th>
                    <th width="20%"><?= $language == 'id' ? 'Jumlah Hari' : 'Days' ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td style="text-align: left"><?= $language == 'id' ? 'Sakit' : 'Sickness' ?></td>
                    <td><?= $attendance['sick'] ?> <?= $language == 'id' ? 'Hari' : 'Days' ?></td>
                </tr>
                <tr>
                    <td>2</td>
                    <td style="text-align: left"><?= $language == 'id' ? 'Izin' : 'Permission' ?></td>
                    <td><?= $attendance['permission'] ?> <?= $language == 'id' ? 'Hari' : 'Days' ?></td>
                </tr>
                <tr>
                    <td>3</td>
                    <td style="text-align: left"><?= $language == 'id' ? 'Tanpa Keterangan' : 'Absence' ?></td>
                    <td><?= $attendance['absence'] ?> <?= $language == 'id' ? 'Hari' : 'Days' ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Decision (for high school) -->
        <div style="margin-top: 20px; margin-bottom: 20px;">
            <table width="100%">
                <tr>
                    <td width="20%"><?= $language == 'id' ? 'Keputusan' : 'Decision' ?>:</td>
                    <td>
                        <?= $language == 'id' ? 'Berdasarkan pencapaian seluruh kompetensi, peserta didik dinyatakan:' : 'Based on the achievement of all competencies, the student is declared:' ?>
                        <br>
                        <b><?= $language == 'id' ? 'Naik/Tinggal(*) kelas .................. (................)' : 'Promoted/Retained(*) to class .................. (................)' ?></b>
                        <br>
                        <small><?= $language == 'id' ? '(*) Coret yang tidak perlu' : '(*) Cross out as necessary' ?></small>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <div class="signature">
                <?= $language == 'id' ? 'Mengetahui,' : 'Acknowledged,' ?><br>
                <?= $language == 'id' ? 'Orang Tua / Wali Murid' : 'Parent / Guardian' ?>
                <div class="signature-line"></div>
                <p><?= $student['parent_name'] ?? '(................................)' ?></p>
            </div>
            
            <div class="signature">
                <?= $report_date ?><br>
                <?= $language == 'id' ? 'Wali Kelas' : 'Class Teacher' ?>
                <div class="signature-line"></div>
                <p><?= $school_info['academic_advisor']['name'] ?><br>
                <?= $school_info['academic_advisor']['id'] ?></p>
            </div>
        </div>

        <div class="signatures" style="margin-top: 20px;">
            <div class="signature" style="visibility: hidden;">
                <!-- Empty space to maintain layout -->
            </div>
            <div class="signature">
                <?= $language == 'id' ? 'Mengetahui,' : 'Acknowledged,' ?><br>
                <?= $language == 'id' ? 'Kepala Sekolah' : 'School Principal' ?>
                <div class="signature-line"></div>
                <p><?= $school_info['principal']['name'] ?><br>
                <?= $school_info['principal']['id'] ?></p>
            </div>
            <div class="signature" style="visibility: hidden;">
                <!-- Empty space to maintain layout -->
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; font-style: italic; color: #555;">
            Educating, Leading, Inspiring, and Empowering
        </div>
    </div>
    <?php endif; ?>

    <!-- JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
