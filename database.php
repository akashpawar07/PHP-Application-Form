<?php
require_once 'config.php';

class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . HOSTNAME . ";dbname=" . DATABASE_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $this->conn = new PDO($dsn, USERNAME, PASSWORD, $options);
            
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    public function __clone() {}
    
    // Prevent from unserialization
    public function __wakeup() {}
}

// Test connection
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        echo "Database connection successful!";
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>