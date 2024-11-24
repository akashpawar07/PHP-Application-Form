
<?php
require_once 'config.php';
require_once 'database.php';

$db = Database::getInstance(); // Use Singleton pattern to get the database connection
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

$query = "SELECT s.*, c.name AS class_name 
          FROM student s 
          LEFT JOIN classes c ON s.class_id = c.class_id 
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Student Details</h1>
        
        <div class="card">
            <div class="card-body">
                <?php if ($student['image']): ?>
                    <img src="<?= UPLOAD_DIR . $student['image'] ?>" 
                         alt="Student Image" 
                         class="img-fluid mb-3" 
                         style="max-width: 300px;">
                <?php endif; ?>
                
                <h5>Name:</h5>
                <p><?= htmlspecialchars($student['name']) ?></p>
                
                <h5>Email:</h5>
                <p><?= htmlspecialchars($student['email']) ?></p>
                
                <h5>Address:</h5>
                <p><?= nl2br(htmlspecialchars($student['address'])) ?></p>
                
                <h5>Class:</h5>
                <p><?= htmlspecialchars($student['class_name']) ?></p>
                
                <h5>Created At:</h5>
                <p><?= htmlspecialchars($student['created_at']) ?></p>
            </div>
        </div>
        
        <div class="mt-3">
            <a href="edit.php?id=<?= $student['id'] ?>" class="btn btn-warning">Edit</a>
            <a href="index.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
</body>
</html>
