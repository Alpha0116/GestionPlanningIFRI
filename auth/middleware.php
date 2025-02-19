<?php
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: /GESTION EMPOIE IFRI/auth/login.php");
        exit();
    }
}

function checkAdmin() {
    checkAuth();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: /GESTION EMPOIE IFRI/index.php");
        exit();
    }
}

function checkDepartmentDirector() {
    checkAuth();
    if ($_SESSION['role'] !== 'department_director') {
        header("Location: /GESTION EMPOIE IFRI/index.php");
        exit();
    }
}

function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_destroy();
    header("Location: /GESTION EMPOIE IFRI/auth/login.php");
    exit();
}
?>
