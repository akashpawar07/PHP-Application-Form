<?php
require_once 'config.php';
require_once 'database.php';

// Use Singleton pattern to get the database connection
$db = Database::getInstance();
$conn = $db->getConnection();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $class_id = trim($_POST['class_id']);

    // Validate input
    if (empty($name)) {
        $error = "Name is required.";
    } elseif (empty($class_id)) {
        $error = "Class is required.";
    } else {
        // Check if class_id exists in the classes table
        $stmt = $conn->prepare("SELECT COUNT(*) FROM classes WHERE class_id = ?");
        $stmt->execute([$class_id]);
        if ($stmt->fetchColumn() == 0) {
            $error = "Selected class does not exist.";
        }
    }

    // Proceed only if no error
    if (empty($error)) {
        $image_query = "";
        $image_params = [$name, $email, $address, $class_id, $id];

        // Handle image upload if provided
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (in_array($file_extension, $allowed)) {
                // Get old image to delete
                $stmt = $conn->prepare("SELECT image FROM student WHERE id = ?");
                $stmt->execute([$id]);
                $old_image = $stmt->fetchColumn();

                // Delete old image if it exists
                if ($old_image && file_exists(UPLOAD_DIR . $old_image)) {
                    unlink(UPLOAD_DIR . $old_image);
                }

                // Upload new image
                $image_name = uniqid() . '.' . $file_extension;
                move_uploaded_file($_FILES['image']['tmp_name'], UPLOAD_DIR . $image_name);

                $image_query = ", image = ?";
                $image_params = [$name, $email, $address, $class_id, $image_name, $id];
            } else {
                $error = "Invalid image format.";
            }
        }

        if (empty($error)) {
            $query = "UPDATE student SET name = ?, email = ?, address = ?, class_id = ?" . $image_query . " WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute($image_params);

            header("Location: index.php");
            exit();
        }
    }
}

// Get student data
$query = "SELECT * FROM student WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    header("Location: index.php");
    exit();
}

// Get classes for dropdown
$query = "SELECT * FROM classes ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->execute();
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Edit Student</h1>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label>Name:</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($student['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label>Email:</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($student['email']) ?>" required>
            </div>

            <div class="mb-3">
                <label>Address:</label>
                <textarea name="address" class="form-control"><?= htmlspecialchars($student['address']) ?></textarea>
            </div>

            <div class="mb-3">
                <label>Class:</label>
                <select name="class_id" class="form-control" required>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?= $class['class_id'] ?>" 
                                <?= $class['class_id'] == $student['class_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Current Image:</label>
                <?php if ($student['image']): ?>
                    <img src="<?= UPLOAD_DIR . $student['image'] ?>" 
                         alt="Student Image" style="max-width: 200px;">
                <?php else: ?>
                    <p>No image uploaded</p>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label>New Image:</label>
                <input type="file" name="image" class="form-control" 
                       accept=".jpg,.jpeg,.png">
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
