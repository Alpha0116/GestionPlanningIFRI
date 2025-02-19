<?php
require_once '../auth/middleware.php';
require_once '../config/database.php';
checkAuth();
checkDepartmentDirector();

$database = new Database();
$db = $database->getConnection();

$success = $error = '';
// Démarrer la session si ce n'est pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Vérification de la session avant la requête
if (!isset($_SESSION['department']) || empty($_SESSION['department'])) {
    die("Erreur : Département non défini ou vide dans la session.");
}

// ✅ Préparation et exécution sécurisée de la requête
$query = "SELECT name FROM departments WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$_SESSION['department']])) {
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$department) {
        die("Aucun département trouvé pour l'ID : " . htmlspecialchars($_SESSION['department']));
    }
} else {
    die("Erreur lors de l'exécution de la requête.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = (int)$_POST['course_id'];
    $classroom_id = (int)$_POST['classroom_id'];
    $stream_id = (int)$_POST['stream_id'];
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $created_by = $_SESSION['user_id'];

    if (empty($course_id) || empty($classroom_id) || empty($stream_id) || empty($day_of_week) || empty($start_time) || empty($end_time)) {
        $error = "All fields are required";
    } else {
        // Check for scheduling conflicts
        $query = "SELECT s.*, c.course_name, r.room_number, st.stream_name 
                  FROM schedules s
                  JOIN courses c ON s.course_id = c.id
                  JOIN classrooms r ON s.classroom_id = r.id
                  JOIN streams st ON s.stream_id = st.id
                  WHERE (s.classroom_id = ? OR s.stream_id = ?)
                  AND s.day_of_week = ?
                  AND ((s.start_time BETWEEN ? AND ?) 
                  OR (s.end_time BETWEEN ? AND ?))";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$classroom_id, $stream_id, $day_of_week, $start_time, $end_time, $start_time, $end_time]);
        
        if ($conflict = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = "Scheduling conflict detected with: " . $conflict['course_name'] . 
                    " in room " . $conflict['room_number'] . 
                    " for stream " . $conflict['stream_name'];
        } else {
            // Check if classroom is available
            $query = "SELECT is_available FROM classrooms WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$classroom_id]);
            $classroom = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$classroom['is_available']) {
                $error = "Selected classroom is not available";
            } else {
                $query = "INSERT INTO schedules (course_id, classroom_id, stream_id, day_of_week, start_time, end_time, created_by) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                try {
                    $stmt->execute([$course_id, $classroom_id, $stream_id, $day_of_week, $start_time, $end_time, $created_by]);
                    $success = "Schedule created successfully!";
                } catch(PDOException $e) {
                    $error = "Error creating schedule: " . $e->getMessage();
                }
            }
        }
    }
}

// // Fetch department name
// $query = "SELECT name FROM departments WHERE id = ?";
// $stmt = $db->prepare($query);
// $stmt->execute([$_SESSION['department']]);
// $department = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch courses for this department
$query = "SELECT * FROM courses WHERE department_id = ? ORDER BY course_code";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['department']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available classrooms
$query = "SELECT * FROM classrooms WHERE is_available = 1 ORDER BY room_number";
$stmt = $db->prepare($query);
$stmt->execute();
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch streams for this department
$query = "SELECT * FROM streams WHERE department_id = ? ORDER BY year_level, stream_name";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['department']]);
$streams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing schedules
$query = "SELECT s.*, c.course_name, c.course_code, r.room_number, st.stream_name 
          FROM schedules s
          JOIN courses c ON s.course_id = c.id
          JOIN classrooms r ON s.classroom_id = r.id
          JOIN streams st ON s.stream_id = st.id
          WHERE c.department_id = ?
          ORDER BY s.day_of_week, s.start_time";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['department']]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group schedules by day
$schedules_by_day = [];
foreach ($schedules as $schedule) {
    $schedules_by_day[$schedule['day_of_week']][] = $schedule;
}

$days_of_week = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedules - Course Scheduling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .schedule-card {
            margin-bottom: 20px;
        }
        .time-slot {
            font-weight: bold;
            color: #666;
        }
    </style>
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
            <h2>Gérer les horaires -<?php echo isset($department['name']) ? htmlspecialchars($department['name']) : 'Inconnu'; ?></h2>
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
                        <h4>Créer un nouveau calendrier</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="course_id" class="form-label">Cours</label>
                                <select class="form-control" id="course_id" name="course_id" required>
                                    <option value="">Sélectionnez un cours</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="stream_id" class="form-label">Flux</label>
                                <select class="form-control" id="stream_id" name="stream_id" required>
                                    <option value="">Sélectionnez le flux</option>
                                    <?php foreach ($streams as $stream): ?>
                                        <option value="<?php echo $stream['id']; ?>">
                                            <?php echo htmlspecialchars($stream['stream_name'] . ' (Year ' . $stream['year_level'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="classroom_id" class="form-label">Salle de classe</label>
                                <select class="form-control" id="classroom_id" name="classroom_id" required>
                                    <option value="">Sélectionnez une salle de classe</option>
                                    <?php foreach ($classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['id']; ?>">
                                            <?php echo htmlspecialchars($classroom['room_number'] . ' (Capacity: ' . $classroom['capacity'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="day_of_week" class="form-label">Jour de la semaine</label>
                                <select class="form-control" id="day_of_week" name="day_of_week" required>
                                    <option value="">Sélectionnez le jour</option>
                                    <?php foreach ($days_of_week as $day): ?>
                                        <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="start_time" class="form-label">Heure de début</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>

                            <div class="mb-3">
                                <label for="end_time" class="form-label">Heure de fin</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Créer un calendrier</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Horaires actuels</h4>
                    </div>
                    <div class="card-body">
                        <?php foreach ($days_of_week as $day): ?>
                            <div class="schedule-card">
                                <h5 class="mb-3"><?php echo $day; ?></h5>
                                <?php if (isset($schedules_by_day[$day])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Heure</th>
                                                    <th>Cours</th>
                                                    <th>Flux</th>
                                                    <th>Salle</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($schedules_by_day[$day] as $schedule): ?>
                                                    <tr>
                                                        <td class="time-slot">
                                                            <?php 
                                                            echo date('H:i', strtotime($schedule['start_time'])) . ' - ' . 
                                                                 date('H:i', strtotime($schedule['end_time'])); 
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($schedule['course_code'] . ' - ' . $schedule['course_name']); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($schedule['stream_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($schedule['room_number']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">Pas d'horaires pour cette journée</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
