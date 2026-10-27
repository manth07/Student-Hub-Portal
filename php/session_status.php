<?php
/**
 * ==========================================================
 * StudentHub Portal - Session Status JSON Endpoint
 * Practical 10: Asynchronous Session Status Check for HTML pages
 * ==========================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Step 1: Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Step 2: Define inactivity timeout (15 minutes = 900 seconds)
$timeout_duration = 900;

// Step 3: Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // Step 3a: Check if session has timed out due to inactivity
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
        session_unset();
        session_destroy();
        echo json_encode(['logged_in' => false]);
        exit();
    }

    // Step 3b: Refresh last activity timestamp
    $_SESSION['last_activity'] = time();

    // Step 3c: Return logged-in user details as JSON
    echo json_encode([
        'logged_in' => true,
        'user_id'   => intval($_SESSION['user_id']),
        'name'      => $_SESSION['user_name'] ?? 'User',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'student'
    ]);
} else {
    // Step 4: User is not logged in
    echo json_encode(['logged_in' => false]);
}
?>
