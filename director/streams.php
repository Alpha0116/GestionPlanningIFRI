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
    $stream_name = trim($_POST['stream_name']);
    $year_level = (int)$_POST['year_level'];
    $capacity = (int)$_POST['capacity'];
    $department_id = $_SESSION['department'];

    if (empty($stream_name) || $year_level <= 0 || $capacity <= 0) {
        $error = "Stream name, valid year level, and capacity are required";
    } else {
        $query = "INSERT INTO streams (stream_name, department_id, year_level, capacity) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute([$stream_name, $department_id, $year_level, $capacity]);
            $success = "Stream created successfully!";
        } catch(PDOException $e) {
            $error = "Error creating stream: " . $e->getMessage();
        }
    }
}

// Fetch department name
$query = "SELECT name FROM departments WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['department']]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all streams for the department
$query = "SELECT s.*, 
          (SELECT COUNT(*) FROM schedules WHERE stream_id = s.id) as schedule_count
          FROM streams s 
          WHERE s.department_id = ? 
          ORDER BY s.year_level, s.stream_name";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['department']]);
$streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Streams - Course Scheduling System</title>
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
                        <a class="nav-link" href="courses.php">Cours</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedules.php">Horaires</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Gérer les flux - <?php echo htmlspecialchars($department['name']); ?></h2>
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
                        <h4>Ajouter un nouveau flux</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="stream_name" class="form-label">Nom du flux</label>
                                <input type="text" class="form-control" id="stream_name" name="stream_name" required>
                                <div class="form-text">Exemple : Informatique A, Mathématiques B, etc.</div>
                            </div>

                            <div class="mb-3">
                                <label for="year_level" class="form-label">Année d'étude</label>
                                <select class="form-control" id="year_level" name="year_level" required>
                                    <option value="1">1ère année</option>
                                    <option value="2">2ème année</option>
                                    <option value="3">3ème année</option>
                                    <option value="4">4ème année</option>
                                    <option value="5">5ème année</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacité</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                                <div class="form-text">Nombre maximum d'étudiants dans cette filière</div>
                            </div>

                            <button type="submit" class="btn btn-primary">Créer un flux</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Liste des flux</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nom du flux</th>
                                        <th>Année d'étude</th>
                                        <th>Capacité</th>
                                        <th>Cours programmés</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($streams as $stream): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stream['stream_name']); ?></td>
                                        <td><?php echo htmlspecialchars($stream['year_level']); ?> Year</td>
                                        <td><?php echo htmlspecialchars($stream['capacity']); ?> students</td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $stream['schedule_count']; ?> cours
                                            </span>
                                        </td>
                                        <td>
                                            <a href="schedules.php?stream_id=<?php echo $stream['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-calendar"></i> Voir le calendrier
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
</body>
</html>
