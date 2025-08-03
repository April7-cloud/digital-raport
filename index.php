<?php
session_start();

// Database configuration
class Database {
    private $host = 'localhost';
    private $username = 'moodleuser';
    private $password = 'Moodle2025';
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
            
        case 'add_assessment':
            if (isset($_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date'])) {
                $result = $app->addAssessment($_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Assessment berhasil ditambahkan' : 'Gagal menambahkan assessment';
            }
            break;
            
        case 'edit_assessment':
            if (isset($_POST['id'], $_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date'])) {
                $result = $app->updateAssessment($_POST['id'], $_POST['student_id'], $_POST['subject_id'], $_POST['type'], $_POST['score'], $_POST['date']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Assessment berhasil diupdate' : 'Gagal mengupdate assessment';
            }
            break;
            
        case 'delete_assessment':
            if (isset($_POST['id'])) {
                $result = $app->deleteAssessment($_POST['id']);
                $response['success'] = $result;
                $response['message'] = $result ? 'Assessment berhasil dihapus' : 'Gagal menghapus assessment';
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Get current page
$page = $_GET['page'] ?? 'dashboard';
$student_id = $_GET['student_id'] ?? null;

// Get data for the page
$students = $app->getStudents();
$subjects = $app->getSubjects();
$assessments = $app->getAssessments();
$stats = $app->getDashboardStats();

if ($student_id) {
    $student_report = $app->getStudentReport($student_id);
}
?>
