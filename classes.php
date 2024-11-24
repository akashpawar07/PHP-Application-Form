<?php
require_once 'database.php';

class ClassesManager {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function getClasses() {
        try {
            $query = "SELECT 
                        c.class_id,
                        c.name,
                        c.created_at,
                        COUNT(s.id) as student_count
                      FROM classes c
                      LEFT JOIN student s ON c.class_id = s.class_id
                      GROUP BY c.class_id, c.name, c.created_at
                      ORDER BY c.name";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            error_log("Error fetching classes: " . $e->getMessage());
            return [];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Manage Classes</h1>
        
        <!-- Add Class Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Add New Class</h5>
                <form id="addClassForm" class="row g-3">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="ajax" value="true">
                    <div class="col-auto">
                        <input type="text" 
                               name="name" 
                               class="form-control" 
                               placeholder="Class Name" 
                               required 
                               minlength="2" 
                               maxlength="50"
                               pattern="[A-Za-z0-9\s\-]+"
                               title="Class name can only contain letters, numbers, spaces, and hyphens">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Add Class</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Classes List -->
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Students</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $classManager = new ClassesManager();
                    $classes = $classManager->getClasses();
                    
                    if (!empty($classes)): 
                        foreach($classes as $class): ?>
                        <tr>
                            <td><?= htmlspecialchars($class['name']) ?></td>
                            <td><?= (int)$class['student_count'] ?></td>
                            <td><?= htmlspecialchars($class['created_at']) ?></td>
                            <td>
                                <button type="button" 
                                        class="btn btn-warning btn-sm edit-class" 
                                        data-class-id="<?= (int)$class['class_id'] ?>"
                                        data-class-name="<?= htmlspecialchars($class['name']) ?>">
                                    Edit
                                </button>
                                <?php if($class['student_count'] == 0): ?>
                                    <button type="button" 
                                            class="btn btn-danger btn-sm delete-class"
                                            data-class-id="<?= (int)$class['class_id'] ?>">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; 
                    else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No classes found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <a href="index.php" class="btn btn-secondary">Back to Students</a>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editClassForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="ajax" value="true">
                        <input type="hidden" name="class_id" id="editClassId">
                        <div class="mb-3">
                            <label for="editClassName" class="form-label">Class Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="editClassName" 
                                   name="name" 
                                   required
                                   minlength="2" 
                                   maxlength="50"
                                   pattern="[A-Za-z0-9\s\-]+">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Required Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const editModal = new bootstrap.Modal(document.getElementById('editClassModal'));
        
        // Configure toastr notifications
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000
        };

        function handleFormSubmit(form, successCallback) {
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            const formData = new FormData(form);
            
            fetch('classes_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    toastr.success(data.message);
                    if (successCallback) successCallback();
                } else {
                    toastr.error(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Request failed:', error);
                toastr.error('An error occurred while processing your request.');
            })
            .finally(() => {
                submitButton.disabled = false;
            });
        }

        // Add Class Form Handler
        document.getElementById('addClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this, () => setTimeout(() => window.location.reload(), 1500));
        });

        // Edit Class Button Handler
        document.querySelectorAll('.edit-class').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('editClassId').value = this.dataset.classId;
                document.getElementById('editClassName').value = this.dataset.className;
                editModal.show();
            });
        });

        // Edit Class Form Handler
        document.getElementById('editClassForm').addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this, () => {
                editModal.hide();
                setTimeout(() => window.location.reload(), 1500);
            });
        });

        // Delete Class Button Handler
        document.querySelectorAll('.delete-class').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this class?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('ajax', 'true');
                    formData.append('class_id', this.dataset.classId);
                    
                    fetch('classes_api.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            toastr.success(data.message);
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            toastr.error(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Request failed:', error);
                        toastr.error('An error occurred while processing your request.');
                    });
                }
            });
        });
    });
    </script>
</body>
</html>