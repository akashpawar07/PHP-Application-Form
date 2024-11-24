<?php
require_once 'config.php';
require_once 'database.php';

try {
    // Using the singleton pattern from your Database class
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $query = "SELECT s.*, c.name as class_name FROM student s LEFT JOIN classes c ON s.class_id = c.class_id ORDER BY s.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    if (DEBUG_MODE) {
        $error_message = $e->getMessage();
    } else {
        error_log($e->getMessage(), 3, ERROR_LOG_PATH);
        $error_message = "System error occurred. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Student Management</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <a href="create.php" class="btn btn-primary mb-3">Add New Student</a>
        <a href="classes.php" class="btn btn-secondary mb-3">Manage Classes</a>

        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Image</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($students)): ?>
                    <?php foreach($students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['name']) ?></td>
                            <td><?= htmlspecialchars($student['email']) ?></td>
                            <td><?= htmlspecialchars($student['class_name'] ?? 'Not Assigned') ?></td>
                            <td>
                                <?php if(!empty($student['image'])): ?>
                                    <img src="<?= UPLOAD_DIR . htmlspecialchars($student['image']) ?>" 
                                         alt="Student Image" 
                                         style="max-width: 50px;">
                                <?php endif; ?>
                            </td>
                            <td><?= $student['created_at'] ?></td>
                            <td>
                                <a href="view.php?id=<?= $student['id'] ?>" 
                                   class="btn btn-info btn-sm">View</a>
                                <a href="edit.php?id=<?= $student['id'] ?>" 
                                   class="btn btn-warning btn-sm">Edit</a>
                                <a href="delete.php?id=<?= $student['id'] ?>" 
                                   class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No students found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>