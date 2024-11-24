<?php
require_once 'database.php';

class ClassesAPI {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function handleRequest() {
        header('Content-Type: application/json');
        
        try {
            $this->validateRequest();
            
            switch ($_POST['action']) {
                case 'add':
                    return $this->addClass();
                case 'edit':
                    return $this->editClass();
                case 'delete':
                    return $this->deleteClass();
                default:
                    throw new Exception('Invalid action');
            }
        } catch (Exception $e) {
            http_response_code(400);
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    private function validateRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Invalid request method');
        }
        
        if (!isset($_POST['action'])) {
            throw new Exception('Action is required');
        }
        
        if (!isset($_POST['ajax']) || $_POST['ajax'] !== 'true') {
            throw new Exception('Invalid request source');
        }
    }

    private function addClass() {
        $name = $this->validateClassName();
        
        $stmt = $this->conn->prepare("INSERT INTO classes (name) VALUES (:name)");
        $stmt->execute([':name' => $name]);
        
        return [
            'status' => 'success',
            'message' => 'Class added successfully'
        ];
    }

    private function editClass() {
        $name = $this->validateClassName();
        $classId = $this->validateClassId();
        
        $stmt = $this->conn->prepare("UPDATE classes SET name = :name WHERE class_id = :id");
        $stmt->execute([':name' => $name, ':id' => $classId]);
        
        return [
            'status' => 'success',
            'message' => 'Class updated successfully'
        ];
    }

    private function deleteClass() {
        $classId = $this->validateClassId();
        
        // Check if class has students
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM student WHERE class_id = :id");
        $stmt->execute([':id' => $classId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            throw new Exception('Cannot delete class with enrolled students');
        }
        
        $stmt = $this->conn->prepare("DELETE FROM classes WHERE class_id = :id");
        $stmt->execute([':id' => $classId]);
        
        return [
            'status' => 'success',
            'message' => 'Class deleted successfully'
        ];
    }

    private function validateClassName() {
        if (empty($_POST['name'])) {
            throw new Exception('Class name is required');
        }
        
        $name = trim($_POST['name']);
        if (strlen($name) < 2 || strlen($name) > 50) {
            throw new Exception('Class name must be between 2 and 50 characters');
        }
        
        return $name;
    }

    private function validateClassId() {
        if (empty($_POST['class_id'])) {
            throw new Exception('Class ID is required');
        }
        
        $classId = filter_var($_POST['class_id'], FILTER_VALIDATE_INT);
        if ($classId === false) {
            throw new Exception('Invalid class ID');
        }
        
        return $classId;
    }
}

// Handle API requests
if (basename($_SERVER['PHP_SELF']) === 'classes_api.php') {
    $api = new ClassesAPI();
    echo json_encode($api->handleRequest());
}
?>