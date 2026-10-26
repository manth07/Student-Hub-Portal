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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$timeout_duration = 900; // 15 minutes

if (isset($_SESSION['user_id'])) {
    // Check inactivity timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_duration)) {
        session_unset();
        session_destroy();
        echo json_encode([
            'logged_in' => false
        ]);
        exit();
    }

    // Refresh last activity
    $_SESSION['last_activity'] = time();

    echo json_encode([
        'logged_in' => true,
        'user_id' => intval($_SESSION['user_id']),
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'student',
        'user' => [
            'id' => intval($_SESSION['user_id']),
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 'student'
        ]
    ]);
} else {
    echo json_encode([
        'logged_in' => false
    ]);
}
?>
