<?php
/**
 * ==========================================================
 * StudentHub Portal - Registration Processor
 * Practical 9: User Registration, Hashing & Database Insert
 * ==========================================================
 */

require_once __DIR__ . '/db_connect.php';

$success = false;
$message = '';

function clean_input($val) {
    return htmlspecialchars(stripslashes(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = clean_input($_POST['full_name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $mobile = clean_input($_POST['mobile'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $course = clean_input($_POST['course'] ?? '');
    $year = intval($_POST['year'] ?? 1);
    $gender = clean_input($_POST['gender'] ?? 'Other');

    // Server-side validation
    if (empty($full_name) || empty($email) || empty($mobile) || empty($password)) {
        $message = 'All required registration fields must be completed.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email address format.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $message = 'Mobile number must be a valid 10-digit Indian number starting with 6-9.';
    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $message = 'Password and confirmation password do not match.';
    } else {
        // Step 1: Check for duplicate email in users table
        $check_stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $message = "The email '{$email}' is already registered. Please login instead.";
            mysqli_stmt_close($check_stmt);
        } else {
            mysqli_stmt_close($check_stmt);

            // Step 2: Hash the password securely with bcrypt
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'student';

            // Step 3: Insert into users table
            $user_stmt = mysqli_prepare($conn, "INSERT INTO users (full_name, email, mobile, password_hash, role) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($user_stmt, "sssss", $full_name, $email, $mobile, $password_hash, $role);

            if (mysqli_stmt_execute($user_stmt)) {
                $new_user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($user_stmt);

                // Step 4: Generate a unique enrollment number for CHARUSAT
                $course_prefix = '24CE';
                if (strpos($course, 'IT') !== false) $course_prefix = '24IT';
                elseif (strpos($course, 'CSE') !== false) $course_prefix = '24CSE';
                elseif (strpos($course, 'BCA') !== false) $course_prefix = '24BCA';

                $enrollment_no = $course_prefix . str_pad($new_user_id + 50, 3, '0', STR_PAD_LEFT);

                // Step 5: Insert into students directory table
                $stud_stmt = mysqli_prepare($conn, "INSERT INTO students (enrollment_no, name, email, mobile, course, year, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stud_stmt, "sssssis", $enrollment_no, $full_name, $email, $mobile, $course, $year, $gender);
                mysqli_stmt_execute($stud_stmt);
                mysqli_stmt_close($stud_stmt);

                // Step 6: Log action in audit_logs
                $audit_action = 'USER_REGISTER';
                $audit_details = "New student registered: {$full_name} ({$enrollment_no}) with email {$email}";
                $audit_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($audit_stmt, "sss", $email, $audit_action, $audit_details);
                mysqli_stmt_execute($audit_stmt);
                mysqli_stmt_close($audit_stmt);

                $success = true;
                $message = "Registration successful! Your student profile ({$enrollment_no}) is created. You can now login.";
            } else {
                $message = "Database error inserting user: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration Status | StudentHub</title>
  <link rel="stylesheet" href="/studenthub/css/style.css">
  <link rel="stylesheet" href="/studenthub/css/responsive.css">
</head>
<body>
  <div style="position: absolute; top: 16px; right: 20px; z-index: 100;">
    <button id="theme-toggle-btn" class="theme-toggle-btn btn btn-outline btn-sm" type="button" aria-label="Toggle theme">Dark Mode</button>
  </div>

  <main style="padding-top: 40px;">
    <div style="max-width: 600px; margin: 0 auto; text-align: center;">
      <div style="margin-bottom: 20px;">
        <a href="/studenthub/login.html" style="font-size: 2.2rem; font-weight: 800; color: var(--primary-color); text-decoration: none;">StudentHub</a>
      </div>
      <div class="card" style="padding: 40px 30px;">
        <?php if ($success): ?>
          <h2 style="color: var(--success-color); margin-bottom: 12px;">Welcome to StudentHub</h2>
          <div class="alert alert-success" style="text-align: left; margin: 20px 0;">
            <div><?php echo $message; ?></div>
          </div>
          <p class="text-muted" style="margin-bottom: 25px;">
            Your account credentials are now active in the university MySQL database.
          </p>
          <div style="display:flex; justify-content:center; gap:12px;">
            <a href="/studenthub/login.html" class="btn btn-primary">Proceed to Login</a>
          </div>
        <?php else: ?>
          <h2 style="color: var(--danger-color); margin-bottom: 12px;">Registration Incomplete</h2>
          <div class="alert alert-danger" style="text-align: left; margin: 20px 0;">
            <div><?php echo htmlspecialchars($message); ?></div>
          </div>
          <div style="display:flex; justify-content:center; gap:12px;">
            <a href="/studenthub/register.html" class="btn btn-primary">Try Again</a>
            <a href="/studenthub/login.html" class="btn btn-outline">Already have an account? Login</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <footer>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> StudentHub Portal | ITUE203 Practical 9</p>
    </div>
  </footer>
</body>
</html>
