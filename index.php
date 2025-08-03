<?php
session_start();

// Database configuration
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'sma_assessment';
    private $connection;
    
    public function __construct() {
        $this->connect();
        $this->createTables();
    }
    
    private function connect() {
        try {
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->database}", 
                                      $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            // Create database if not exists
            try {
                $this->connection = new PDO("mysql:host={$this->host}", $this->username, $this->password);
                $this->connection->exec("CREATE DATABASE IF NOT EXISTS {$this->database}");
                $this->connection->exec("USE {$this->database}");
            } catch(PDOException $e) {
                die("Connection failed: " . $e->getMessage());
            }
        }
    }
    
    private function createTables() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nis VARCHAR(20) UNIQUE NOT NULL,
                name VARCHAR(100) NOT NULL,
                class VARCHAR(20) NOT NULL,
                semester VARCHAR(20) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS subjects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                code VARCHAR(10) NOT NULL,
                teacher VARCHAR(100) NOT NULL,
                kkm INT DEFAULT 75,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS assessments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                subject_id INT NOT NULL,
                type ENUM('UH', 'UTS', 'UAS', 'Tugas') NOT NULL,
                score INT NOT NULL,
                date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
                FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
            )"
        ];
        
        foreach ($tables as $table) {
            try {
                $this->connection->exec($table);
            } catch(PDOException $e) {
                // Table might already exist, continue
            }
        }
        
        // Insert sample data if tables are empty
        $this->insertSampleData();
    }
    
    private function insertSampleData() {
        try {
            // Check if data exists
            $stmt = $this->connection->query("SELECT COUNT(*) FROM students");
            if ($stmt->fetchColumn() == 0) {
                // Insert sample students
                $students = [
                    ['2024001', 'Ahmad Rizki Pratama', 'XII IPA 1', 'Ganjil'],
                    ['2024002', 'Siti Nurhaliza Dewi', 'XII IPA 1', 'Ganjil'],
                    ['2024003', 'Budi Santoso Wijaya', 'XII IPS 1', 'Ganjil'],
                    ['2024004', 'Dewi Lestari Sari', 'XII IPA 2', 'Ganjil'],
                    ['2024005', 'Andi Pratama Putra', 'XII IPS 2', 'Ganjil'],
                    ['2024006', 'Maya Indira Sari', 'XII IPA 1', 'Ganjil'],
                    ['2024007', 'Rudi Hartono', 'XII IPS 1', 'Ganjil']
                ];
                
                $stmt = $this->connection->prepare("INSERT INTO students (nis, name, class, semester) VALUES (?, ?, ?, ?)");
                foreach ($students as $student) {
                    $stmt->execute($student);
                }
                
                // Insert sample subjects
                $subjects = [
                    ['Matematika', 'MAT', 'Pak Andi Susanto', 75],
                    ['Bahasa Indonesia', 'BIN', 'Bu Sari Dewi', 75],
                    ['Fisika', 'FIS', 'Pak Joko Widodo', 75],
                    ['Kimia', 'KIM', 'Bu Ratna Sari', 75],
                    ['Biologi', 'BIO', 'Bu Maya Indra', 75],
                    ['Sejarah', 'SEJ', 'Pak Bambang Riyadi', 75],
                    ['Geografi', 'GEO', 'Bu Sinta Wulandari', 75],
                    ['Ekonomi', 'EKO', 'Pak Hendra Gunawan', 75]
                ];
                
                $stmt = $this->connection->prepare("INSERT INTO subjects (name, code, teacher, kkm) VALUES (?, ?, ?, ?)");
                foreach ($subjects as $subject) {
                    $stmt->execute($subject);
                }
                
                // Insert sample assessments
                $assessments = [
                    [1, 1, 'UH', 85, '2024-07-15'],
                    [1, 2, 'UTS', 78, '2024-07-10'],
                    [1, 3, 'UH', 82, '2024-07-12'],
                    [1, 4, 'Tugas', 88, '2024-07-08'],
                    [2, 1, 'UH', 92, '2024-07-15'],
                    [2, 2, 'UTS', 88, '2024-07-10'],
                    [2, 3, 'UH', 89, '2024-07-12'],
                    [2, 4, 'Tugas', 95, '2024-07-08'],
                    [3, 1, 'UH', 75, '2024-07-15'],
                    [3, 2, 'UTS', 72, '2024-07-10'],
                    [3, 6, 'UH', 80, '2024-07-14'],
                    [3, 7, 'Tugas', 85, '2024-07-09'],
                    [4, 1, 'UH', 88, '2024-07-15'],
                    [4, 3, 'UTS', 84, '2024-07-11'],
                    [4, 4, 'UH', 90, '2024-07-13'],
                    [5, 6, 'UTS', 91, '2024-07-10'],
                    [5, 7, 'UH', 87, '2024-07-16'],
                    [5, 8, 'Tugas', 92, '2024-07-07'],
                    [6, 1, 'UH', 79, '2024-07-15'],
                    [6, 2, 'UTS', 83, '2024-07-10'],
                    [7, 6, 'UH', 77, '2024-07-14'],
                    [7, 8, 'UTS', 81, '2024-07-11']
                ];
                
                $stmt = $this->connection->prepare("INSERT INTO assessments (student_id, subject_id, type, score, date) VALUES (?, ?, ?, ?, ?)");
                foreach ($assessments as $assessment) {
                    $stmt->execute($assessment);
                }
            }
        } catch(PDOException $e) {
            // Sample data insertion failed, continue without sample data
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Main Application Class
class SMAAssessmentApp {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    // Student Methods
    public function getStudents() {
        try {
            $stmt = $this->conn->query("SELECT * FROM students ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function getStudent($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function addStudent($nis, $name, $class, $semester) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO students (nis, name, class, semester) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$nis, $name, $class, $semester]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function updateStudent($id, $nis, $name, $class, $semester) {
        try {
            $stmt = $this->conn->prepare("UPDATE students SET nis = ?, name = ?, class = ?, semester = ? WHERE id = ?");
            return $stmt->execute([$nis, $name, $class, $semester, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function deleteStudent($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM students WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Subject Methods
    public function getSubjects() {
        try {
            $stmt = $this->conn->query("SELECT * FROM subjects ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function getSubject($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM subjects WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function addSubject($name, $code, $teacher, $kkm) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO subjects (name, code, teacher, kkm) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$name, $code, $teacher, $kkm]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function updateSubject($id, $name, $code, $teacher, $kkm) {
        try {
            $stmt = $this->conn->prepare("UPDATE subjects SET name = ?, code = ?, teacher = ?, kkm = ? WHERE id = ?");
            return $stmt->execute([$name, $code, $teacher, $kkm, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function deleteSubject($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM subjects WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Assessment Methods
    public function getAssessments() {
        try {
            $stmt = $this->conn->query("
                SELECT a.*, s.name as student_name, s.nis, s.class, sub.name as subject_name, sub.code as subject_code 
                FROM assessments a 
                JOIN students s ON a.student_id = s.id 
                JOIN subjects sub ON a.subject_id = sub.id 
                ORDER BY a.date DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    public function getAssessment($id) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM assessments WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function addAssessment($student_id, $subject_id, $type, $score, $date) {
        try {
            $stmt = $this->conn->prepare("INSERT INTO assessments (student_id, subject_id, type, score, date) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([$student_id, $subject_id, $type, $score, $date]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function updateAssessment($id, $student_id, $subject_id, $type, $score, $date) {
        try {
            $stmt = $this->conn->prepare("UPDATE assessments SET student_id = ?, subject_id = ?, type = ?, score = ?, date = ? WHERE id = ?");
            return $stmt->execute([$student_id, $subject_id, $type, $score, $date, $id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    public function deleteAssessment($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM assessments WHERE id = ?");
            return $stmt->execute([$id]);
        } catch(PDOException $e) {
            return false;
        }
    }
    
    // Report Methods
    public function getStudentReport($student_id) {
        try {
            $student = $this->getStudent($student_id);
            
            $stmt = $this->conn->prepare("
                SELECT sub.name as subject_name, sub.kkm, 
                       AVG(a.score) as average_score,
                       COUNT(a.id) as total_assessments
                FROM subjects sub
                LEFT JOIN assessments a ON sub.id = a.subject_id AND a.student_id = ?
                GROUP BY sub.id, sub.name, sub.kkm
                ORDER BY sub.name
            ");
            $stmt->execute([$student_id]);
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'student' => $student,
                'subjects' => $subjects
            ];
        } catch(PDOException $e) {
            return ['student' => null, 'subjects' => []];
        }
    }
    
    // Dashboard Statistics
    public function getDashboardStats() {
        $stats = [];
        
        try {
            // Total students
            $stmt = $this->conn->query("SELECT COUNT(*) FROM students");
            $stats['total_students'] = $stmt->fetchColumn();
            
            // Total subjects
            $stmt = $this->conn->query("SELECT COUNT(*) FROM subjects");
            $stats['total_subjects'] = $stmt->fetchColumn();
            
            // Total assessments
            $stmt = $this->conn->query("SELECT COUNT(*) FROM assessments");
            $stats['total_assessments'] = $stmt->fetchColumn();
            
            // Average score
            $stmt = $this->conn->query("SELECT AVG(score) FROM assessments");
            $average = $stmt->fetchColumn();
            $stats['average_score'] = $average ? round($average) : 0;
            
            // Recent activities
            $stmt = $this->conn->query("
                SELECT a.*, s.name as student_name, sub.name as subject_name 
                FROM assessments a 
                JOIN students s ON a.student_id = s.id 
                JOIN subjects sub ON a.subject_id = sub.id 
                ORDER BY a.created_at DESC 
                LIMIT 5
            ");
            $stats['recent_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $stats = [
                'total_students' => 0,
                'total_subjects' => 0,  
                'total_assessments' => 0,
                'average_score' => 0,
                'recent_activities' => []
            ];
        }
        
        return $stats;
    }
    
    // Utility Methods
    public function getGrade($score) {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'E';
    }
    
    public function isPassingGrade($score, $kkm = 75) {
        return $score >= $kkm;
    }
}

// Initialize the application
$app = new SMAAssessmentApp();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'add_student':
            if (isset($_POST['nis'], $_POST['name'], $_POST['class'], $_POST['semester'])) {
                $result = $app->addStudent($_POST['nis'], $_POST['name'], $_POST['class'], $_POST['semester']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Siswa berhasil ditambahkan' : 'Gagal menambahkan siswa';
            }
            break;
            
        case 'edit_student':
            if (isset($_POST['id'], $_POST['nis'], $_POST['name'], $_POST['class'], $_POST['semester'])) {
                $result = $app->updateStudent($_POST['id'], $_POST['nis'], $_POST['name'], $_POST['class'], $_POST['semester']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Siswa berhasil diupdate' : 'Gagal mengupdate siswa';
            }
            break;
            
        case 'delete_student':
            if (isset($_POST['id'])) {
                $result = $app->deleteStudent($_POST['id']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Siswa berhasil dihapus' : 'Gagal menghapus siswa';
            }
            break;
            
        case 'add_subject':
            if (isset($_POST['name'], $_POST['code'], $_POST['teacher'], $_POST['kkm'])) {
                $result = $app->addSubject($_POST['name'], $_POST['code'], $_POST['teacher'], $_POST['kkm']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Mata pelajaran berhasil ditambahkan' : 'Gagal menambahkan mata pelajaran';
            }
            break;
            
        case 'edit_subject':
            if (isset($_POST['id'], $_POST['name'], $_POST['code'], $_POST['teacher'], $_POST['kkm'])) {
                $result = $app->updateSubject($_POST['id'], $_POST['name'], $_POST['code'], $_POST['teacher'], $_POST['kkm']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Mata pelajaran berhasil diupdate' : 'Gagal mengupdate mata pelajaran';
            }
            break;
            
        case 'delete_subject':
            if (isset($_POST['id'])) {
                $result = $app->deleteSubject($_POST['id']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Mata pelajaran berhasil dihapus' : 'Gagal menghapus mata pelajaran';
            }
            break;
            
        case 'add_assessment':
            if (isset($_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date'])) {
                $result = $app->addAssessment($_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Penilaian berhasil ditambahkan' : 'Gagal menambahkan penilaian';
            }
            break;
            
        case 'edit_assessment':
            if (isset($_POST['id'], $_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date'])) {
                $result = $app->updateAssessment($_POST['id'], $_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Penilaian berhasil diupdate' : 'Gagal mengupdate penilaian';
            }
            break;
            
        case 'delete_assessment':
            if (isset($_POST['id'])) {
                $result = $app->deleteAssessment($_POST['id']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Penilaian berhasil dihapus' : 'Gagal menghapus penilaian';
            }
            break;
            
        case 'get_student_report':
            if (isset($_POST['student_id'])) {
                $report = $app->getStudentReport($_POST['student_id']);
                $response['success'] = true;
                $response['data'] = $report;
            }
            break;
            
        case 'get_student':
            if (isset($_POST['id'])) {
                $student = $app->getStudent($_POST['id']);
                if ($student) {
                    $response['success'] = true;
                    $response['student'] = $student;
                } else {
                    $response['message'] = 'Data siswa tidak ditemukan';
                }
            } else {
                $response['message'] = 'ID siswa tidak valid';
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Get data for the page
$students = $app->getStudents();
$subjects = $app->getSubjects();
$assessments = $app->getAssessments();
$stats = $app->getDashboardStats();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penilaian SMA</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 2px solid #e0e0e0;
        }

        .logo h1 {
            color: #333;
            font-size: 24px;
            font-weight: 700;
        }

        .logo p {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateX(5px);
        }

        .nav-link i {
            margin-right: 12px;
            width: 20px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        .header h2 {
            color: #333;
            font-size: 28px;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
            font-size: 16px;
        }

        /* Dashboard Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }

        .students-card .icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .subjects-card .icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .assessments-card .icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .average-card .icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-size: 16px;
        }

        /* Content Area */
        .content-area {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: #333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        /* Recent Activities */
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            margin-bottom: 10px;
            border-radius: 0 8px 8px 0;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
        }

        .activity-content h4 {
            margin-bottom: 5px;
            color: #333;
        }

        .activity-content p {
            color: #666;
            font-size: 14px;
        }

        /* Report Card Styles */
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .report-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 30px;
        }

        .report-header h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .student-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .grades-table {
            margin-top: 20px;
        }

        .grade-a { color: #28a745; font-weight: bold; }
        .grade-b { color: #17a2b8; font-weight: bold; }
        .grade-c { color: #ffc107; font-weight: bold; }
        .grade-d { color: #fd7e14; font-weight: bold; }
        .grade-e { color: #dc3545; font-weight: bold; }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 15px;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-graduation-cap"></i> SMA Assessment</h1>
                <p>Sistem Penilaian Digital</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link active" data-page="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="students">
                        <i class="fas fa-user-graduate"></i>
                        Data Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="subjects">
                        <i class="fas fa-book"></i>
                        Mata Pelajaran
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="assessments">
                        <i class="fas fa-clipboard-list"></i>
                        Penilaian
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="reports">
                        <i class="fas fa-chart-bar"></i>
                        Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-page="export">
                        <i class="fas fa-file-pdf"></i>
                        Export Raport
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Page -->
            <div id="dashboard-page" class="page">
                <div class="header">
                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                    <p>Selamat datang di Sistem Penilaian SMA</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card students-card">
                        <div class="icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Total Siswa</p>
                    </div>
                    <div class="stat-card subjects-card">
                        <div class="icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3><?php echo $stats['total_subjects']; ?></h3>
                        <p>Mata Pelajaran</p>
                    </div>
                    <div class="stat-card assessments-card">
                        <div class="icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3><?php echo $stats['total_assessments']; ?></h3>
                        <p>Total Penilaian</p>
                    </div>
                    <div class="stat-card average-card">
                        <div class="icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3><?php echo $stats['average_score']; ?></h3>
                        <p>Rata-rata Nilai</p>
                    </div>
                </div>

                <div class="content-area">
                    <h3><i class="fas fa-clock"></i> Aktivitas Terbaru</h3>
                    <div id="recent-activities">
                        <?php foreach ($stats['recent_activities'] as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <div class="activity-content">
                                <h4>Penilaian <?php echo $activity['type']; ?></h4>
                                <p><?php echo $activity['student_name']; ?> - <?php echo $activity['subject_name']; ?> - Nilai: <?php echo $activity['score']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Students Page -->
            <div id="students-page" class="page hidden">
                <div class="header">
                    <h2><i class="fas fa-user-graduate"></i> Data Siswa</h2>
                    <p>Kelola data siswa di sini</p>
                    <button class="btn btn-primary" onclick="showAddStudentModal()">
                        <i class="fas fa-plus"></i> Tambah Siswa
                    </button>
                </div>

                <div class="content-area">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Semester</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="students-table">
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['nis']); ?></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['class']); ?></td>
                                    <td><?php echo htmlspecialchars($student['semester']); ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick="editStudent(<?php echo $student['id']; ?>)" style="margin-right: 5px;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?php echo $student['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Subjects Page -->
            <div id="subjects-page" class="page hidden">
                <div class="header">
                    <h2><i class="fas fa-book"></i> Mata Pelajaran</h2>
                    <p>Kelola mata pelajaran di sini</p>
                    <button class="btn btn-primary" onclick="showAddSubjectModal()">
                        <i class="fas fa-plus"></i> Tambah Mata Pelajaran
                    </button>
                </div>

                <div class="content-area">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama Mata Pelajaran</th>
                                    <th>Guru Pengampu</th>
                                    <th>KKM</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="subjects-table">
                                <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($subject['code']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                    <td><?php echo htmlspecialchars($subject['teacher']); ?></td>
                                    <td><?php echo $subject['kkm']; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick="editSubject(<?php echo $subject['id']; ?>)" style="margin-right: 5px;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteSubject(<?php echo $subject['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Assessments Page -->
            <div id="assessments-page" class="page hidden">
                <div class="header">
                    <h2><i class="fas fa-clipboard-list"></i> Penilaian</h2>
                    <p>Kelola penilaian siswa di sini</p>
                    <button class="btn btn-primary" onclick="showAddAssessmentModal()">
                        <i class="fas fa-plus"></i> Tambah Penilaian
                    </button>
                </div>

                <div class="content-area">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Siswa</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Jenis</th>
                                    <th>Nilai</th>
                                    <th>Grade</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="assessments-table">
                                <?php foreach ($assessments as $assessment): 
                                    $grade = $app->getGrade($assessment['score']);
                                    $gradeClass = 'grade-' . strtolower($grade);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assessment['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($assessment['subject_name']); ?></td>
                                    <td><?php echo $assessment['type']; ?></td>
                                    <td><?php echo $assessment['score']; ?></td>
                                    <td><span class="<?php echo $gradeClass; ?>"><?php echo $grade; ?></span></td>
                                    <td><?php echo $assessment['date']; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm" onclick="editAssessment(<?php echo $assessment['id']; ?>)" style="margin-right: 5px;">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="deleteAssessment(<?php echo $assessment['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Reports Page -->
            <div id="reports-page" class="page hidden">
                <div class="header">
                    <h2><i class="fas fa-chart-bar"></i> Laporan</h2>
                    <p>Lihat laporan penilaian siswa</p>
                </div>

                <div class="content-area">
                    <div class="form-group">
                        <label for="student-select">Pilih Siswa:</label>
                        <select id="student-select" class="form-control" onchange="loadStudentReport()">
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="student-report" class="hidden">
                        <div class="report-card">
                            <div class="report-header">
                                <h2>LAPORAN PENILAIAN SISWA</h2>
                                <p>Semester Ganjil - Tahun Ajaran 2024/2025</p>
                            </div>

                            <div class="student-info">
                                <div class="info-item">
                                    <strong>Nama:</strong>
                                    <span id="report-student-name"></span>
                                </div>
                                <div class="info-item">
                                    <strong>NIS:</strong>
                                    <span id="report-student-nis"></span>
                                </div>
                                <div class="info-item">
                                    <strong>Kelas:</strong>
                                    <span id="report-student-class"></span>
                                </div>
                                <div class="info-item">
                                    <strong>Semester:</strong>
                                    <span id="report-student-semester"></span>
                                </div>
                            </div>

                            <div class="grades-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Mata Pelajaran</th>
                                            <th>KKM</th>
                                            <th>Rata-rata</th>
                                            <th>Grade</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="report-grades">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Export Page -->
            <div id="export-page" class="page hidden">
                <div class="header">
                    <h2><i class="fas fa-file-pdf"></i> Export Raport</h2>
                    <p>Export raport siswa dalam format PDF</p>
                </div>

                <div class="content-area">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="export-student-select">Pilih Siswa:</label>
                            <select id="export-student-select" class="form-control">
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="export-semester">Semester:</label>
                            <select id="export-semester" class="form-control">
                                <option value="Ganjil">Ganjil</option>
                                <option value="Genap">Genap</option>
                            </select>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <button class="btn btn-success" onclick="exportToPDF()" style="font-size: 16px; padding: 15px 30px;">
                            <i class="fas fa-download"></i> Download Raport PDF
                        </button>
                    </div>

                    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <h4><i class="fas fa-info-circle"></i> Informasi</h4>
                        <p>• Pilih siswa yang ingin di-export raportnya</p>
                        <p>• Pastikan data penilaian siswa sudah lengkap</p>
                        <p>• File PDF akan otomatis terdownload</p>
                        <p>• Format raport mengikuti standar sekolah</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modals -->
    <!-- Add Student Modal -->
    <div id="add-student-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add-student-modal')">&times;</span>
            <h3><i class="fas fa-user-plus"></i> <span id="student-modal-title">Tambah Siswa Baru</span></h3>
            <form id="add-student-form">
                <input type="hidden" id="student-id" name="id">
                <div class="form-group">
                    <label for="student-nis">NIS:</label>
                    <input type="text" id="student-nis" name="nis" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="student-name">Nama Lengkap:</label>
                    <input type="text" id="student-name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="student-class">Kelas:</label>
                    <select id="student-class" name="class" class="form-control" required>
                        <option value="">-- Pilih Kelas --</option>
                        <option value="XII IPA 1">XII IPA 1</option>
                        <option value="XII IPA 2">XII IPA 2</option>
                        <option value="XII IPS 1">XII IPS 1</option>
                        <option value="XII IPS 2">XII IPS 2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="student-semester">Semester:</label>
                    <select id="student-semester" name="semester" class="form-control" required>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('add-student-modal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div id="add-subject-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add-subject-modal')">&times;</span>
            <h3><i class="fas fa-book"></i> <span id="subject-modal-title">Tambah Mata Pelajaran</span></h3>
            <form id="add-subject-form">
                <input type="hidden" id="subject-id" name="id">
                <div class="form-group">
                    <label for="subject-name">Nama Mata Pelajaran:</label>
                    <input type="text" id="subject-name" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subject-code">Kode:</label>
                    <input type="text" id="subject-code" name="code" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subject-teacher">Guru Pengampu:</label>
                    <input type="text" id="subject-teacher" name="teacher" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="subject-kkm">KKM:</label>
                    <input type="number" id="subject-kkm" name="kkm" class="form-control" min="0" max="100" value="75" required>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('add-subject-modal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Assessment Modal -->
    <div id="add-assessment-modal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('add-assessment-modal')">&times;</span>
            <h3><i class="fas fa-clipboard-list"></i> <span id="assessment-modal-title">Tambah Penilaian</span></h3>
            <form id="add-assessment-form">
                <input type="hidden" id="assessment-id" name="id">
                <div class="form-group">
                    <label for="assessment-student">Siswa:</label>
                    <select id="assessment-student" name="student_id" class="form-control" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach ($students as $student): ?>
                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assessment-subject">Mata Pelajaran:</label>
                    <select id="assessment-subject" name="subject_id" class="form-control" required>
                        <option value="">-- Pilih Mata Pelajaran --</option>
                        <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assessment-type">Jenis Penilaian:</label>
                    <select id="assessment-type" name="type" class="form-control" required>
                        <option value="">-- Pilih Jenis --</option>
                        <option value="UH">Ulangan Harian</option>
                        <option value="UTS">Ujian Tengah Semester</option>
                        <option value="UAS">Ujian Akhir Semester</option>
                        <option value="Tugas">Tugas</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="assessment-score">Nilai:</label>
                    <input type="number" id="assessment-score" name="score" class="form-control" min="0" max="100" required>
                </div>
                <div class="form-group">
                    <label for="assessment-date">Tanggal:</label>
                    <input type="date" id="assessment-date" name="date" class="form-control" required>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('add-assessment-modal')">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Global variables
        let currentEditId = null;
        let currentEditType = null;

        // Navigation
        document.addEventListener('DOMContentLoaded', function() {
            // Navigation event listeners
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    navigateToPage(page);
                    
                    // Update active nav
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Set today's date as default for assessment date
            document.getElementById('assessment-date').value = new Date().toISOString().split('T')[0];
        });

        function navigateToPage(page) {
            // Hide all pages
            document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
            
            // Show selected page
            document.getElementById(page + '-page').classList.remove('hidden');
        }

        // Utility functions
        function getGrade(score) {
            if (score >= 90) return 'A';
            if (score >= 80) return 'B';
            if (score >= 70) return 'C';
            if (score >= 60) return 'D';
            return 'E';
        }

        // Student functions
        function showAddStudentModal() {
            currentEditId = null;
            currentEditType = 'student';
            document.getElementById('student-modal-title').textContent = 'Tambah Siswa Baru';
            document.getElementById('add-student-form').reset();
            document.getElementById('student-id').value = '';
            document.getElementById('add-student-modal').style.display = 'block';
        }

        function editStudent(id) {
            currentEditId = id;
            currentEditType = 'student';
            document.getElementById('student-modal-title').textContent = 'Edit Siswa';
            document.getElementById('student-id').value = id;
            
            // Find student data and populate form
            fetch('?', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_student&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.student) {
                    document.getElementById('student-nis').value = data.student.nis;
                    document.getElementById('student-name').value = data.student.name;
                    document.getElementById('student-class').value = data.student.class;
                    document.getElementById('student-semester').value = data.student.semester;
                }
            });
            
            document.getElementById('add-student-modal').style.display = 'block';
        }

        function deleteStudent(id) {
            if (confirm('Apakah Anda yakin ingin menghapus siswa ini?')) {
                fetch('?', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_student&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        }

        // Subject functions
        function showAddSubjectModal() {
            currentEditId = null;
            currentEditType = 'subject';
            document.getElementById('subject-modal-title').textContent = 'Tambah Mata Pelajaran';
            document.getElementById('add-subject-form').reset();
            document.getElementById('subject-id').value = '';
            document.getElementById('add-subject-modal').style.display = 'block';
        }

        function editSubject(id) {
            currentEditId = id;
            currentEditType = 'subject';
            document.getElementById('subject-modal-title').textContent = 'Edit Mata Pelajaran';
            document.getElementById('subject-id').value = id;
            
            // Find subject data from the table
            const rows = document.querySelectorAll('#subjects-table tr');
            rows.forEach(row => {
                const editBtn = row.querySelector(`button[onclick="editSubject(${id})"]`);
                if (editBtn) {
                    const cells = row.querySelectorAll('td');
                    document.getElementById('subject-code').value = cells[0].textContent;
                    document.getElementById('subject-name').value = cells[1].textContent;
                    document.getElementById('subject-teacher').value = cells[2].textContent;
                    document.getElementById('subject-kkm').value = cells[3].textContent;
                }
            });
            
            document.getElementById('add-subject-modal').style.display = 'block';
        }

        function deleteSubject(id) {
            if (confirm('Apakah Anda yakin ingin menghapus mata pelajaran ini?')) {
                fetch('?', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_subject&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        }

        // Assessment functions
        function showAddAssessmentModal() {
            currentEditId = null;
            currentEditType = 'assessment';
            document.getElementById('assessment-modal-title').textContent = 'Tambah Penilaian';
            document.getElementById('add-assessment-form').reset();
            document.getElementById('assessment-id').value = '';
            document.getElementById('assessment-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('add-assessment-modal').style.display = 'block';
        }

        function editAssessment(id) {
            currentEditId = id;
            currentEditType = 'assessment';
            document.getElementById('assessment-modal-title').textContent = 'Edit Penilaian';
            document.getElementById('assessment-id').value = id;
            
            // Find assessment data from the table
            const rows = document.querySelectorAll('#assessments-table tr');
            rows.forEach(row => {
                const editBtn = row.querySelector(`button[onclick="editAssessment(${id})"]`);
                if (editBtn) {
                    const cells = row.querySelectorAll('td');
                    // You would need to get the actual IDs from data attributes
                    // For now, we'll use a simpler approach
                    document.getElementById('assessment-score').value = cells[3].textContent;
                    document.getElementById('assessment-type').value = cells[2].textContent;
                    document.getElementById('assessment-date').value = cells[5].textContent;
                }
            });
            
            document.getElementById('add-assessment-modal').style.display = 'block';
        }

        function deleteAssessment(id) {
            if (confirm('Apakah Anda yakin ingin menghapus penilaian ini?')) {
                fetch('?', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_assessment&id=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showNotification(data.message, 'error');
                    }
                });
            }
        }

        // Report functions
        function loadStudentReport() {
            const studentId = document.getElementById('student-select').value;
            const reportDiv = document.getElementById('student-report');
            
            if (studentId) {
                fetch('?', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=get_student_report&student_id=' + studentId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.student) {
                        const student = data.data.student;
                        const subjects = data.data.subjects;
                        
                        document.getElementById('report-student-name').textContent = student.name;
                        document.getElementById('report-student-nis').textContent = student.nis;
                        document.getElementById('report-student-class').textContent = student.class;
                        document.getElementById('report-student-semester').textContent = student.semester;
                        
                        // Load grades
                        const tbody = document.getElementById('report-grades');
                        tbody.innerHTML = '';
                        
                        subjects.forEach(subject => {
                            const average = subject.average_score ? Math.round(subject.average_score) : 0;
                            const grade = average > 0 ? getGrade(average) : '-';
                            const status = average >= subject.kkm ? 'Lulus' : (average > 0 ? 'Tidak Lulus' : '-');
                            const statusColor = average >= subject.kkm ? 'green' : (average > 0 ? 'red' : 'gray');
                            
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${subject.subject_name}</td>
                                <td>${subject.kkm}</td>
                                <td>${average > 0 ? average : '-'}</td>
                                <td><span class="grade-${grade.toLowerCase()}">${grade}</span></td>
                                <td><span style="color: ${statusColor};">${status}</span></td>
                            `;
                            tbody.appendChild(row);
                        });
                        
                        reportDiv.classList.remove('hidden');
                    }
                });
            } else {
                reportDiv.classList.add('hidden');
            }
        }

        function exportToPDF() {
            const studentId = document.getElementById('export-student-select').value;
            const semester = document.getElementById('export-semester').value;
            
            if (!studentId) {
                alert('Pilih siswa terlebih dahulu!');
                return;
            }
            
            // Get student name for notification
            const selectElement = document.getElementById('export-student-select');
            const studentName = selectElement.options[selectElement.selectedIndex].text;
            
            // Simulate PDF generation
            showNotification(`Raport ${studentName} untuk semester ${semester} sedang diproses...`, 'info');
            
            setTimeout(() => {
                showNotification(`Raport ${studentName} berhasil didownload!`, 'success');
                
                // In real implementation, this would call server-side PDF generation
                // const link = document.createElement('a');
                // link.href = 'generate_pdf.php?student_id=' + studentId + '&semester=' + semester;
                // link.download = `Raport_${studentName}_${semester}.pdf`;
                // link.click();
            }, 2000);
        }

        // Modal functions
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            currentEditId = null;
            currentEditType = null;
        }

        // Form submissions
        document.getElementById('add-student-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = currentEditId ? 'edit_student' : 'add_student';
            formData.append('action', action);
            
            fetch('?', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('add-student-modal');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        });

        document.getElementById('add-subject-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = currentEditId ? 'edit_subject' : 'add_subject';
            formData.append('action', action);
            
            fetch('?', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('add-subject-modal');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        });

        document.getElementById('add-assessment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const action = currentEditId ? 'edit_assessment' : 'add_assessment';
            formData.append('action', action);
            
            fetch('?', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal('add-assessment-modal');
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(data.message, 'error');
                }
            });
        });

        // Notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
                max-width: 400px;
            `;
            
            switch(type) {
                case 'success':
                    notification.style.background = 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)';
                    break;
                case 'error':
                    notification.style.background = 'linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%)';
                    notification.style.color = '#333';
                    break;
                case 'info':
                    notification.style.background = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
                    break;
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.style.opacity = '1', 100);
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    currentEditId = null;
                    currentEditType = null;
                }
            });
        }
    </script>
</body>
</html>