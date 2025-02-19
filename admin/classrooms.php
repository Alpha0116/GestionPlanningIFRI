<?php
require_once '../auth/middleware.php';
require_once '../config/database.php';
checkAuth();
checkAdmin();

$database = new Database();
$db = $database->getConnection();

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = trim($_POST['room_number']);
    $capacity = (int)$_POST['capacity'];
    $building = trim($_POST['building']);
    $floor = trim($_POST['floor']);

    if (empty($room_number) || $capacity <= 0) {
        $error = "Room number and valid capacity are required";
    } else {
        $query = "INSERT INTO classrooms (room_number, capacity, building, floor) VALUES (?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        try {
            $stmt->execute([$room_number, $capacity, $building, $floor]);
            $success = "Classroom created successfully!";
        } catch(PDOException $e) {
            $error = "Error creating classroom: " . $e->getMessage();
        }
    }
}

// Handle availability toggle
if (isset($_POST['toggle_availability']) && isset($_POST['classroom_id'])) {
    $classroom_id = $_POST['classroom_id'];
    $query = "UPDATE classrooms SET is_available = NOT is_available WHERE id = ?";
    $stmt = $db->prepare($query);
    try {
        $stmt->execute([$classroom_id]);
        $success = "Classroom availability updated successfully!";
    } catch(PDOException $e) {
        $error = "Error updating classroom: " . $e->getMessage();
    }
}

// Fetch all classrooms
$query = "SELECT * FROM classrooms ORDER BY building, room_number";
$stmt = $db->prepare($query);
$stmt->execute();
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classrooms - Course Scheduling System</title>
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
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Gérer les salles de classe</h2>
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
                        <h4>Ajouter une nouvelle salle de classe</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="room_number" class="form-label">Numéro de chambre</label>
                                <input type="text" class="form-control" id="room_number" name="room_number" required>
                            </div>

                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacité</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" required>
                            </div>

                            <div class="mb-3">
                                <label for="building" class="form-label">Bâtiment</label>
                                <input type="text" class="form-control" id="building" name="building">
                            </div>

                            <div class="mb-3">
                                <label for="floor" class="form-label">Sol</label>
                                <input type="text" class="form-control" id="floor" name="floor">
                            </div>

                            <button type="submit" class="btn btn-primary">Créer une salle de classe</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Liste des salles de classe</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Numéro de salle</th>
                                        <th>Bâtiment</th>
                                        <th>Sol</th>
                                        <th>Capacité</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classrooms as $classroom): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($classroom['room_number']); ?></td>
                                        <td><?php echo htmlspecialchars($classroom['building']); ?></td>
                                        <td><?php echo htmlspecialchars($classroom['floor']); ?></td>
                                        <td><?php echo htmlspecialchars($classroom['capacity']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $classroom['is_available'] ? 'success' : 'danger'; ?>">
                                                <?php echo $classroom['is_available'] ? 'Available' : 'Not Available'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="classroom_id" value="<?php echo $classroom['id']; ?>">
                                                <input type="hidden" name="toggle_availability" value="1">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $classroom['is_available'] ? 'warning' : 'success'; ?>">
                                                    <?php echo $classroom['is_available'] ? 'Mark Unavailable' : 'Mark Available'; ?>
                                                </button>
                                            </form>
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
