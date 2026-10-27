<?php
/**
 * ==========================================================
 * StudentHub Portal - Authentication & Login Handler
 * Practical 10: Secure Login, password_verify(), RBAC & Cookies
 * ==========================================================
 */

// Step 1: Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Step 2: Include database connection
require_once __DIR__ . '/db_connect.php';

/**
 * Step 3: Helper function to set up the session after successful login.
 * Stores user info in session, logs the action, and redirects by role.
 */
function finalize_login($conn, $user) {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    // Step 3a: Store user details in session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['mobile'] = $user['mobile'];
    $_SESSION['last_activity'] = time();

    // Step 3b: Log the successful login to audit_logs table
    $audit_action = 'USER_LOGIN';
    $audit_details = "User {$user['email']} logged in successfully as {$user['role']}.";
    $audit_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($audit_stmt, "sss", $user['email'], $audit_action, $audit_details);
    mysqli_stmt_execute($audit_stmt);
    mysqli_stmt_close($audit_stmt);

    // Step 3c: Redirect based on Role-Based Access Control (RBAC)
    if ($user['role'] === 'admin') {
        header("Location: /studenthub/php/admin_dashboard.php");
    } else {
        header("Location: /studenthub/index.html");
    }
    exit();
}

// ----------------------------------------------------------
// Step 4: Handle POST Form Submission (Email + Password Login)
// ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Step 4a: Get and sanitize form inputs
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Step 4b: Check for empty fields

    if (empty($email) || empty($password)) {
        header("Location: /studenthub/login.html?error=empty_fields");
        exit();
    }

    // Step 4c: Query user record by email using prepared statement
    $stmt = mysqli_prepare($conn, "SELECT id, full_name, email, mobile, password_hash, role FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        // Step 4d: Verify bcrypt password hash
        if (password_verify($password, $user['password_hash'])) {
            mysqli_stmt_close($stmt);

            // Handle Remember Me cookie (7 days)
            if ($remember) {
                $token = bin2hex(random_bytes(16));
                setcookie('studenthub_remember', $user['email'] . ':' . $token, time() + (7 * 24 * 60 * 60), '/');
                $update_token = mysqli_prepare($conn, "UPDATE users SET remember_token = ? WHERE id = ?");
                mysqli_stmt_bind_param($update_token, "si", $token, $user['id']);
                mysqli_stmt_execute($update_token);
                mysqli_stmt_close($update_token);
            }

            finalize_login($conn, $user);
        } else {
            mysqli_stmt_close($stmt);
            header("Location: /studenthub/login.html?error=invalid_credentials");
            exit();
        }
    } else {
        mysqli_stmt_close($stmt);
        header("Location: /studenthub/login.html?error=invalid_credentials");
        exit();
    }
} else {
    header("Location: /studenthub/login.html");
    exit();
}
?>
