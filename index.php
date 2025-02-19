<?php
require_once 'auth/middleware.php';
checkAuth();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Course Scheduling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Système de Réservation de Salle et de Planification des Cours</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/users.php">
                                <i class="bi bi-people"></i> Gestion des utilisateurs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/departments.php">
                                <i class="bi bi-building"></i> Départements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/classrooms.php">
                                <i class="bi bi-door-open"></i> Salles de classe
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] === 'Coordonnateur'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="director/courses.php">
                                <i class="bi bi-book"></i> Cours
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="director/schedules.php">
                                <i class="bi bi-calendar3"></i> Horaires
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="director/streams.php">
                                <i class="bi bi-people"></i> Flux
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-light" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="auth/logout.php">Déconnexion</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>Bienvenue!, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                <p class="text-muted">Vous êtes connecté en tant que : <?php echo ucfirst($_SESSION['role']); ?></p>
            </div>
        </div>

        <div class="row mt-4">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <!-- Admin Dashboard -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-people"></i> Gérer les utilisateurs</h5>
                            <p class="card-text">Créer et gérer les comptes utilisateurs des directeurs de département.</p>
                            <a href="admin/users.php" class="btn btn-primary">Gérer les utilisateurs</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-building"></i> Départements</h5>
                            <p class="card-text">Gérer les départements académiques et leurs détails.</p>
                            <a href="admin/departments.php" class="btn btn-primary">Gérer les départements</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-door-open"></i> Salles de classe</h5>
                            <p class="card-text">Gérer les salles de classe et leur disponibilité.</p>
                            <a href="admin/classrooms.php" class="btn btn-primary">Gérer les salles de classe</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($_SESSION['role'] === 'department_director'): ?>
                <!-- Department Director Dashboard -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-book"></i> Gestion des cours</h5>
                            <p class="card-text">Gérez les cours de votre département.</p>
                            <a href="director/courses.php" class="btn btn-primary">Gérer les cours</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-calendar3"></i> Gestion des horaires</h5>
                            <p class="card-text">Créez et gérez les plannings de cours.</p>
                            <a href="director/schedules.php" class="btn btn-primary">Gérer les horaires</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-people"></i> Gestion des flux</h5>
                            <p class="card-text">Gérez les flux d'étudiants dans votre département.</p>
                            <a href="director/streams.php" class="btn btn-primary">Gérer les flux</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
