<?php
// delete.php
require_once 'config.php';
require_once 'database.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

$db = Database::getInstance(); // Use Singleton pattern for the database connection
$conn = $db->getConnection();

// Get image filename before deleting the student record
$stmt = $conn->prepare("SELECT image FROM student WHERE id = ?");
$stmt->execute([$id]);
$image = $stmt->fetchColumn();

if ($image) {
    // Delete the student record
    $stmt = $conn->prepare("DELETE FROM student WHERE id = ?");
    $stmt->execute([$id]);

    // Delete the image file if it exists
    if (file_exists(UPLOAD_DIR . $image)) {
        unlink(UPLOAD_DIR . $image);
    }
}

header("Location: index.php");
exit();