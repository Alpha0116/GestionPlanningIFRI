<?php
require_once '../auth/middleware.php';
require_once '../config/database.php';
checkAuth();
checkDepartmentDirector();

$database = new Database();
$db = $database->getConnection();

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $credits = (int)$_POST['credits'];
    $description = trim($_POST['description']);
    $department_id = $_SESSION['department']; // Department ID from session
    $created_by = $_SESSION['user_id'];

    if (empty($course_code) || empty($course_name) || $credits <= 0) {
        $error = "Course code, name, and valid credits are required";
    } else {
        $query = "INSERT INTO courses (course_code, course_name, department_id, credits, description, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute([$course_code, $course_name, $department_id, $credits, $description, $created_by]);
            $success = "Course created successfully!";
        } catch(PDOException $e) {
            $error = "Error creating course: " . $e->getMessage();
        }
    }
}

// Fetch department name
$query = "SELECT name FROM departments WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['department']]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all courses for the department
$query = "SELECT c.*, u.full_name as creator_name 
          FROM courses c 
          LEFT JOIN users u ON c.created_by = u.id 
          WHERE c.department_id = ? 
          ORDER BY c.course_code";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['department']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Course Scheduling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Système de Réservation de Salle et de Planification des Cours</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">Horaires</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="streams.php">Flux</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Gérer les cours - <?php echo htmlspecialchars($department['name']); ?></h2>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Ajouter un nouveau cours</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="course_code" class="form-label">Code du cours</label>
                                <input type="text" class="form-control" id="course_code" name="course_code" required>
                            </div>

                            <div class="mb-3">
                                <label for="course_name" class="form-label">Nom du cours</label>
                                <input type="text" class="form-control" id="course_name" name="course_name" required>
                            </div>

                            <div class="mb-3">
                                <label for="credits" class="form-label">Crédits</label>
                                <input type="number" class="form-control" id="credits" name="credits" min="1" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Créer un cours</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Liste des cours</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Code du cours</th>
                                        <th>Nom du cours</th>
                                        <th>Crédits</th>
                                        <th>Créé par</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                            <?php if ($course['description']): ?>
                                                <i class="bi bi-info-circle" data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($course['description']); ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($course['credits']); ?></td>
                                        <td><?php echo htmlspecialchars($course['creator_name']); ?></td>
                                        <td>
                                            <a href="schedules.php?course_id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-calendar"></i> Calendrier
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
