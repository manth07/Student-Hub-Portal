<?php
/**
 * ==========================================================
 * StudentHub Portal - Session & Role Authorization Guard
 * Practical 10: Session Security, Inactivity Timeout & RBAC
 * ==========================================================
 */

// Step 1: Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Step 2: Check if the user is logged in and session has not timed out.
 * If not logged in or session expired, redirect to login.html.
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
 * Step 3: Enforce Admin-only access (RBAC).
 * Students are redirected to dashboard.html with an error message.
 */
function check_admin() {
    check_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: /studenthub/dashboard.html?error=access_denied");
        exit();
    }
}

?>
