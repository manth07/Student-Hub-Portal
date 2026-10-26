<?php
/**
 * ==========================================================
 * StudentHub Portal - Logout Handler
 * Practical 10: Session Invalidation & Cookie Termination
 * ==========================================================
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db_connect.php';

// Log logout action if user is known
if (isset($_SESSION['email'])) {
    $user_email = $_SESSION['email'];
    $audit_action = 'USER_LOGOUT';
    $audit_details = "User {$user_email} logged out safely.";
    $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $user_email, $audit_action, $audit_details);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear Remember-Me cookie
if (isset($_COOKIE['studenthub_remember'])) {
    setcookie('studenthub_remember', '', time() - 3600, '/');
}

// Destroy server session
session_destroy();

// Redirect to login page
header("Location: /studenthub/login.html?logout=1");
exit();
?>
