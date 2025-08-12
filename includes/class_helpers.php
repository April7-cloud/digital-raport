<?php
/**
 * Class/Grade Helper Functions
 * 
 * This file contains functions for managing and displaying classes/grades throughout the application
 */

/**
 * Get all classes from the database
 * 
 * @param PDO $db Database connection
 * @param array $options Optional parameters (order, limit, etc)
 * @return array List of classes
 */
function getAllClasses($db, $options = []) {
    $defaultOptions = [
        'orderBy' => 'level ASC, name ASC',
        'limit' => null,
        'where' => null,
        'params' => []
    ];
    
    $options = array_merge($defaultOptions, $options);
    $query = "SELECT * FROM classes WHERE 1=1";
    $params = $options['params'];
    
    if ($options['where']) {
        $query .= " AND " . $options['where'];
    }
    
    $query .= " ORDER BY " . $options['orderBy'];
    
    if ($options['limit']) {
        $query .= " LIMIT " . (int)$options['limit'];
    }
    
    try {
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError('Error getting classes: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get a single class by ID
 * 
 * @param PDO $db Database connection
 * @param int $id Class ID
 * @return array|false Class data or false if not found
 */
function getClassById($db, $id) {
    try {
        $stmt = $db->prepare("SELECT * FROM classes WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        logError('Error getting class by ID: ' . $e->getMessage());
        return false;
    }
}

/**
 * Generate a dropdown select element for classes
 * 
 * @param PDO $db Database connection
 * @param string $name Form field name
 * @param mixed $selectedId Currently selected class ID (if any)
 * @param array $attributes Additional HTML attributes
 * @return string HTML select element
 */
function generateClassesDropdown($db, $name = 'class_id', $selectedId = null, $attributes = []) {
    $classes = getAllClasses($db);
    
    $defaultAttributes = [
        'class' => 'form-control',
        'id' => $name,
        'required' => false
    ];
    
    $mergedAttributes = array_merge($defaultAttributes, $attributes);
    $attributeString = '';
    
    foreach ($mergedAttributes as $key => $value) {
        if ($value === true) {
            $attributeString .= ' ' . $key;
        } elseif ($value !== false) {
            $attributeString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    $html = '<select name="' . htmlspecialchars($name) . '"' . $attributeString . '>';
    $html .= '<option value="">-- Pilih Kelas --</option>';
    
    $groupedClasses = [];
    foreach ($classes as $class) {
        $level = $class['level'];
        if (!isset($groupedClasses[$level])) {
            $groupedClasses[$level] = [];
        }
        $groupedClasses[$level][] = $class;
    }
    
    ksort($groupedClasses);
    
    foreach ($groupedClasses as $level => $levelClasses) {
        $html .= '<optgroup label="Tingkat ' . htmlspecialchars($level) . '">';
        foreach ($levelClasses as $class) {
            $selected = ($selectedId == $class['id']) ? ' selected' : '';
            $html .= '<option value="' . $class['id'] . '"' . $selected . '>' . 
                htmlspecialchars($class['level'] . ' ' . $class['name']) . '</option>';
        }
        $html .= '</optgroup>';
    }
    
    $html .= '</select>';
    return $html;
}

/**
 * Get the formatted class name (level + name)
 * 
 * @param array $class Class data array with 'level' and 'name' keys
 * @return string Formatted class name
 */
function getFormattedClassName($class) {
    if (!$class || !isset($class['level']) || !isset($class['name'])) {
        return '';
    }
    return htmlspecialchars($class['level'] . ' ' . $class['name']);
}

/**
 * Get class name from ID
 * 
 * @param PDO $db Database connection
 * @param int $classId Class ID
 * @return string Formatted class name or empty string if not found
 */
function getClassNameById($db, $classId) {
    $class = getClassById($db, $classId);
    return $class ? getFormattedClassName($class) : '';
}

/**
 * Add SQL for database modification to add class_id column to existing tables
 * This is used when migrating from text-based class to ID-based class
 * 
 * @return string SQL commands
 */
function getClassMigrationSQL() {
    return "
    -- Create the classes table if it doesn't exist
    CREATE TABLE IF NOT EXISTS `classes` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `level` varchar(10) NOT NULL COMMENT 'Class level (e.g., X, XI, XII)',
      `name` varchar(50) NOT NULL COMMENT 'Class name (e.g., IPA 1, IPS 2)',
      `description` varchar(255) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `level_name_unique` (`level`,`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    
    -- Add class_id column to students table if it doesn't exist
    ALTER TABLE `students` 
    ADD COLUMN IF NOT EXISTS `class_id` int(11) DEFAULT NULL AFTER `class`,
    ADD CONSTRAINT IF NOT EXISTS `fk_students_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;
    
    -- Add class_id column to subjects table if it doesn't exist
    ALTER TABLE `subjects` 
    ADD COLUMN IF NOT EXISTS `class_id` int(11) DEFAULT NULL AFTER `class`,
    ADD CONSTRAINT IF NOT EXISTS `fk_subjects_class_id` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;
    ";
}

/**
 * Check if the class management system is set up
 * 
 * @param PDO $db Database connection
 * @return bool True if class management is set up (classes table exists)
 */
function isClassManagementSetUp($db) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'classes'");
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        logError('Error checking class management setup: ' . $e->getMessage());
        return false;
    }
}

/**
 * Migrate existing class text data to class IDs
 * 
 * @param PDO $db Database connection
 * @return array Result with status and message
 */
function migrateExistingClassData($db) {
    $result = [
        'success' => false,
        'message' => '',
        'imported' => 0
    ];
    
    try {
        // Start transaction
        $db->beginTransaction();
        
        // Get unique class values from students
        $studentClassesStmt = $db->query("SELECT DISTINCT class FROM students WHERE class IS NOT NULL AND class != ''");
        $studentClasses = $studentClassesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get unique class values from subjects
        $subjectClassesStmt = $db->query("SELECT DISTINCT class FROM subjects WHERE class IS NOT NULL AND class != ''");
        $subjectClasses = $subjectClassesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Combine and make unique
        $allClasses = array_unique(array_merge($studentClasses, $subjectClasses));
        
        $imported = 0;
        
        foreach ($allClasses as $className) {
            // Parse level and name
            $parts = explode(' ', $className, 2);
            $level = $parts[0];
            $name = isset($parts[1]) ? $parts[1] : '';
            
            if (empty($name)) {
                $name = $level;
                $level = 'X'; // Default level if not specified
            }
            
            // Check if class already exists
            $checkStmt = $db->prepare("SELECT id FROM classes WHERE level = ? AND name = ?");
            $checkStmt->execute([$level, $name]);
            
            if ($checkStmt->rowCount() === 0) {
                // Insert new class
                $insertStmt = $db->prepare("INSERT INTO classes (level, name) VALUES (?, ?)");
                $insertStmt->execute([$level, $name]);
                $classId = $db->lastInsertId();
                $imported++;
            } else {
                $classId = $checkStmt->fetchColumn();
            }
            
            // Update students with this class
            $db->prepare("UPDATE students SET class_id = ? WHERE class = ?")->execute([$classId, $className]);
            
            // Update subjects with this class
            $db->prepare("UPDATE subjects SET class_id = ? WHERE class = ?")->execute([$classId, $className]);
        }
        
        // Commit transaction
        $db->commit();
        
        $result['success'] = true;
        $result['message'] = "Successfully migrated $imported new classes";
        $result['imported'] = $imported;
        
    } catch (PDOException $e) {
        // Roll back transaction
        $db->rollBack();
        $result['message'] = 'Error migrating class data: ' . $e->getMessage();
        logError($result['message']);
    }
    
    return $result;
}

/**
 * Get all unique classes from the subjects table
 * 
 * @param PDO $db Database connection
 * @return array List of unique classes
 */
function getClassesFromSubjects($db) {
    try {
        $stmt = $db->query("SELECT DISTINCT class FROM subjects WHERE class IS NOT NULL AND class != '' ORDER BY class ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        logError('Error getting classes from subjects: ' . $e->getMessage());
        return [];
    }
}

/**
 * Generate a dropdown select element for classes from subjects table
 * 
 * @param PDO $db Database connection
 * @param string $name Form field name
 * @param mixed $selectedValue Currently selected class value (if any)
 * @param array $attributes Additional HTML attributes
 * @return string HTML select element with class options from subjects table
 */
function generateSubjectsClassesDropdown($db, $name = 'class', $selectedValue = null, $attributes = []) {
    $classes = getClassesFromSubjects($db);
    
    $defaultAttributes = [
        'class' => 'form-control',
        'id' => $name,
        'required' => false
    ];
    
    $mergedAttributes = array_merge($defaultAttributes, $attributes);
    $attributeString = '';
    
    foreach ($mergedAttributes as $key => $value) {
        if ($value === true) {
            $attributeString .= ' ' . $key;
        } elseif ($value !== false) {
            $attributeString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    $html = '<select name="' . htmlspecialchars($name) . '"' . $attributeString . '>';
    $html .= '<option value="">-- Pilih Kelas --</option>';
    
    if (empty($classes)) {
        $html .= '<option value="" disabled>Belum ada data kelas. Tambahkan di menu Mata Pelajaran</option>';
    } else {
        foreach ($classes as $class) {
            $selected = ($selectedValue == $class) ? ' selected' : '';
            $html .= '<option value="' . htmlspecialchars($class) . '"' . $selected . '>' . 
                htmlspecialchars($class) . '</option>';
        }
    }
    
    $html .= '</select>';
    return $html;
}

/**
 * Check if any classes are defined in subjects table
 * 
 * @param PDO $db Database connection
 * @return bool True if at least one class is defined
 */
function areClassesDefinedInSubjects($db) {
    try {
        $stmt = $db->query("SELECT COUNT(DISTINCT class) FROM subjects WHERE class IS NOT NULL AND class != ''");
        return (int)$stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        logError('Error checking if classes are defined: ' . $e->getMessage());
        return false;
    }
}
