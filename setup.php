<?php
require_once 'config.php';

class DatabaseSetup {
    private $conn;
    
    public function __construct() {
        try {
            // Connect without database name first
            $dsn = "mysql:host=" . HOSTNAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, USERNAME, PASSWORD);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public function createDatabase() {
        try {
            // Create database if it doesn't exist
            $sql = "CREATE DATABASE IF NOT EXISTS " . DATABASE_NAME . " 
                    CHARACTER SET utf8mb4 
                    COLLATE utf8mb4_unicode_ci";
            
            $this->conn->exec($sql);
            echo "Database '" . DATABASE_NAME . "' created successfully<br>";
            
            // Select the database
            $this->conn->exec("USE " . DATABASE_NAME);
            
            // Create initial tables
            $this->createTables();
            
        } catch(PDOException $e) {
            die("Error creating database: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        // Array of table creation SQL statements
        $tables = [
            'students' => "CREATE TABLE IF NOT EXISTS students (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100) UNIQUE,
                phone VARCHAR(20),
                date_of_birth DATE,
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            'teachers' => "CREATE TABLE IF NOT EXISTS teachers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                email VARCHAR(100) UNIQUE,
                phone VARCHAR(20),
                subject VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            
            'classes' => "CREATE TABLE IF NOT EXISTS classes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                class_name VARCHAR(50) NOT NULL,
                class_code VARCHAR(20) UNIQUE NOT NULL,
                teacher_id INT,
                room_number VARCHAR(20),
                schedule TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES teachers(id)
            )",
            
            'enrollments' => "CREATE TABLE IF NOT EXISTS enrollments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT,
                class_id INT,
                enrollment_date DATE,
                status ENUM('active', 'dropped', 'completed') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES students(id),
                FOREIGN KEY (class_id) REFERENCES classes(id)
            )"
        ];
        
        // Create each table
        foreach($tables as $tableName => $sql) {
            try {
                $this->conn->exec($sql);
                echo "Table '$tableName' created successfully<br>";
            } catch(PDOException $e) {
                echo "Error creating table '$tableName': " . $e->getMessage() . "<br>";
            }
        }
    }
}

// Run the setup
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    try {
        $setup = new DatabaseSetup();
        $setup->createDatabase();
        echo "<br>Database setup completed successfully!";
    } catch(Exception $e) {
        die("Setup failed: " . $e->getMessage());
    }
}
?>