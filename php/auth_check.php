<?php
/**
 * ==========================================================
 * StudentHub Portal - Session & Role Authorization Guard
 * Practical 10: Session Security, Inactivity Timeout & RBAC
 * ==========================================================
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is authenticated with a valid session and not timed out
 */
function check_login() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $timeout_seconds = 900; // 15-minute inactivity timeout

    // Not logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: /studenthub/login.html");
        exit();
    }

    // Inactivity timeout exceeded
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_seconds)) {
        session_unset();
        session_destroy();
        setcookie('studenthub_remember', '', time() - 3600, '/');
        header("Location: /studenthub/login.html?error=timeout");
        exit();
    }

    // Refresh last activity timestamp
    $_SESSION['last_activity'] = time();
}

/**
 * Enforce Admin-only access. Students will be redirected to dashboard with an error.
 */
function check_admin() {
    check_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: /studenthub/dashboard.html?error=access_denied");
        exit();
    }
}

/**
 * Legacy compatibility helper
 */
function require_role($required_roles) {
    check_login();
    $roles = is_array($required_roles) ? $required_roles : [$required_roles];
    if (!in_array($_SESSION['role'], $roles)) {
        header("Location: ../dashboard.html?error=access_denied");
        exit();
    }
}
?>
