<?php
// Database Configuration
define('HOSTNAME', 'localhost');
define('DATABASE_NAME', 'school_db');
define('USERNAME', 'root');
define('PASSWORD', '');
define('DEBUG_MODE', true); // Set to false in production
define('ERROR_LOG_PATH', '/path/to/your/error.log'); // Optional

// File Upload Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5242880);        // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png']);

// Only enable error reporting in development
if ($_SERVER['SERVER_NAME'] === 'localhost') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>