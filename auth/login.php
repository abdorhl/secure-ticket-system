<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!validateCSRFToken($csrfToken)) {
        $_SESSION['error'] = "Invalid security token. Please try again.";
        header('Location: ../index.php');
        exit;
    }

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Veuillez remplir tous les champs.";
        header('Location: ../index.php');
        exit;
    }

    // Check rate limiting
    if (!checkLoginAttempts($email)) {
        $_SESSION['error'] = "Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.";
        header('Location: ../index.php');
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format d'email invalide.";
        recordLoginAttempt($email, false);
        header('Location: ../index.php');
        exit;
    }

    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Successful login
        recordLoginAttempt($email, true);
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['login_time'] = time();

        // Log successful login
        error_log("Successful login for user: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR']);

        if ($user['role'] === 'admin') {
            header('Location: ../admin_dashboard.php');
        } else {
            header('Location: ../user_dashboard.php');
        }
        exit;
    } else {
        // Failed login
        recordLoginAttempt($email, false);
        $_SESSION['error'] = "Email ou mot de passe incorrect.";
        
        // Log failed login attempt
        error_log("Failed login attempt for email: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR']);
        
        header('Location: ../index.php');
        exit;
    }
} else {
    header('Location: ../index.php');
    exit;
}
