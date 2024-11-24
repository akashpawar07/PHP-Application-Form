<?php
require_once 'config.php';
require_once 'database.php';

// Initialize database connection using singleton
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $name = trim($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $address = trim($_POST['address'] ?? '');
    $class_id = filter_var($_POST['class_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($class_id)) {
        $errors[] = "Class selection is required";
    }
    
    // Handle image upload
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
            $errors[] = "Invalid image format. Allowed formats: " . implode(', ', ALLOWED_EXTENSIONS);
        } elseif ($_FILES['image']['size'] > MAX_FILE_SIZE) {
            $errors[] = "File size exceeds limit of " . (MAX_FILE_SIZE / 1048576) . "MB";
        } else {
            $image_name = uniqid() . '.' . $file_extension;
            $upload_path = UPLOAD_DIR . $image_name;
            
            if (!is_dir(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    // Database insertion if no errors
    if (empty($errors)) {
        try {
            $query = "INSERT INTO student (name, email, address, class_id, image) 
                     VALUES (:name, :email, :address, :class_id, :image)";
            
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':address' => $address,
                ':class_id' => $class_id,
                ':image' => $image_name
            ]);
            
            if ($result) {
                header("Location: index.php");
                exit();
            } else {
                $errors[] = "Failed to add student";
            }
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                $errors[] = "Database error: " . $e->getMessage();
            } else {
                error_log($e->getMessage(), 3, ERROR_LOG_PATH);
                $errors[] = "System error occurred. Please try again later.";
            }
        }
    }
}

// Fetch classes for dropdown
try {
    $query = "SELECT * FROM classes ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        $errors[] = "Failed to fetch classes: " . $e->getMessage();
    } else {
        error_log($e->getMessage(), 3, ERROR_LOG_PATH);
        $errors[] = "Failed to load class list. Please try again later.";
    }
    $classes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Add New Student</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="create.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Name:</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email:</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address:</label>
                <textarea id="address" name="address" class="form-control"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="class_id" class="form-label">Class:</label>
                <select id="class_id" name="class_id" class="form-control" required>
                    <option value="">Select a class</option>
                    <?php foreach($classes as $class): ?>
                        <option value="<?= htmlspecialchars($class['class_id']) ?>"
                                <?= (isset($_POST['class_id']) && $_POST['class_id'] == $class['class_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($class['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label for="image" class="form-label">Image:</label>
                <input type="file" id="image" name="image" class="form-control" 
                       accept="<?= '.'.implode(',.', ALLOWED_EXTENSIONS) ?>">
                <small class="text-muted">Allowed formats: <?= strtoupper(implode(', ', ALLOWED_EXTENSIONS)) ?><br>
                    Maximum size: <?= MAX_FILE_SIZE / 1048576 ?>MB</small>
            </div>
            
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>