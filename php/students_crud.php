<?php
/**
 * ==========================================================
 * StudentHub Portal - Student Record Management CRUD
 * Practical 11: MySQL Database CRUD Operations in Procedural PHP
 * ==========================================================
 */

require_once __DIR__ . '/auth_check.php';
check_admin(); // Strict Admin Only Guard

require_once __DIR__ . '/db_connect.php';

// ----------------------------------------------------------
// 0. Handle CSV Export Action (Practical 11 Extension)
// ----------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csv_res = mysqli_query($conn, "SELECT enrollment_no, name, email, mobile, course, year, gender, created_at FROM students ORDER BY id DESC");
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=charusat_students_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Enrollment No', 'Full Name', 'Email Address', 'Mobile Number', 'Course', 'Year', 'Gender', 'Created At']);
    while ($row = mysqli_fetch_assoc($csv_res)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

$alert_msg = '';
$alert_type = 'info';

// Helper input cleaner
function clean($data) {
    return htmlspecialchars(stripslashes(trim($data ?? '')), ENT_QUOTES, 'UTF-8');
}

// ----------------------------------------------------------
// 1. Handle DELETE Action
// ----------------------------------------------------------
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    // Fetch details before delete for audit logging
    $stud_q = mysqli_query($conn, "SELECT name, enrollment_no FROM students WHERE id = {$delete_id}");
    $stud_info = mysqli_fetch_assoc($stud_q);

    $del_stmt = mysqli_prepare($conn, "DELETE FROM students WHERE id = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $delete_id);

    if (mysqli_stmt_execute($del_stmt)) {
        // Audit log
        $actor = $_SESSION['email'] ?? 'admin@charusat.edu.in';
        $details = "Deleted student record: " . ($stud_info['name'] ?? "ID {$delete_id}") . " ({$stud_info['enrollment_no']})";
        $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'STUDENT_DELETE', ?)");
        mysqli_stmt_bind_param($log_stmt, "ss", $actor, $details);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);

        $alert_msg = "Student record deleted successfully.";
        $alert_type = "success";
    } else {
        $alert_msg = "Error deleting student: " . mysqli_error($conn);
        $alert_type = "danger";
    }
    mysqli_stmt_close($del_stmt);
}

// ----------------------------------------------------------
// 2. Handle ADD or UPDATE Form Submission
// ----------------------------------------------------------
$edit_mode = false;
$edit_id = 0;
$form_data = [
    'enrollment_no' => '',
    'name' => '',
    'email' => '',
    'mobile' => '',
    'course' => 'B.Tech CE',
    'year' => 2,
    'gender' => 'Male'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id'] ?? 0);
    $enrollment_no = clean($_POST['enrollment_no']);
    $name = clean($_POST['name']);
    $email = clean($_POST['email']);
    $mobile = clean($_POST['mobile']);
    $course = clean($_POST['course']);
    $year = intval($_POST['year']);
    $gender = clean($_POST['gender'] ?? 'Male');

    if (empty($enrollment_no) || empty($name) || empty($email) || empty($mobile)) {
        $alert_msg = "Please fill in all required fields.";
        $alert_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alert_msg = "Please enter a valid email address.";
        $alert_type = "danger";
    } else {
        if ($student_id > 0) {
            // UPDATE existing student
            $upd_stmt = mysqli_prepare($conn, "UPDATE students SET enrollment_no = ?, name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd_stmt, "sssssisi", $enrollment_no, $name, $email, $mobile, $course, $year, $gender, $student_id);

            if (mysqli_stmt_execute($upd_stmt)) {
                $actor = $_SESSION['email'] ?? 'admin@charusat.edu.in';
                $details = "Updated student: {$name} ({$enrollment_no})";
                $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'STUDENT_UPDATE', ?)");
                mysqli_stmt_bind_param($log_stmt, "ss", $actor, $details);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);

                $alert_msg = "Student record updated successfully!";
                $alert_type = "success";
            } else {
                $alert_msg = "Update failed: " . mysqli_error($conn);
                $alert_type = "danger";
            }
            mysqli_stmt_close($upd_stmt);
        } else {
            // INSERT new student
            $ins_stmt = mysqli_prepare($conn, "INSERT INTO students (enrollment_no, name, email, mobile, course, year, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins_stmt, "sssssis", $enrollment_no, $name, $email, $mobile, $course, $year, $gender);

            if (mysqli_stmt_execute($ins_stmt)) {
                $actor = $_SESSION['email'] ?? 'admin@charusat.edu.in';
                $details = "Added new student: {$name} ({$enrollment_no})";
                $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'STUDENT_ADD', ?)");
                mysqli_stmt_bind_param($log_stmt, "ss", $actor, $details);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);

                $alert_msg = "Student '{$name}' added successfully to the database!";
                $alert_type = "success";
            } else {
                $alert_msg = "Insert failed (enrollment or email may already exist): " . mysqli_error($conn);
                $alert_type = "danger";
            }
            mysqli_stmt_close($ins_stmt);
        }
    }
}

// ----------------------------------------------------------
// 3. Check for EDIT query parameter
// ----------------------------------------------------------
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
    mysqli_stmt_bind_param($edit_stmt, "i", $edit_id);
    mysqli_stmt_execute($edit_stmt);
    $res = mysqli_stmt_get_result($edit_stmt);

    if ($row = mysqli_fetch_assoc($res)) {
        $edit_mode = true;
        $form_data = $row;
    }
    mysqli_stmt_close($edit_stmt);
}

// ----------------------------------------------------------
// 4. Query Student List with Search and Filter
// ----------------------------------------------------------
$search_term = clean($_GET['search'] ?? '');
$course_filter = clean($_GET['course_filter'] ?? 'All');

$query = "SELECT * FROM students WHERE 1=1";
$params = [];
$types = "";

if (!empty($search_term)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $like = "%" . $search_term . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

if ($course_filter !== 'All' && !empty($course_filter)) {
    $query .= " AND course = ?";
    $params[] = $course_filter;
    $types .= "s";
}

$query .= " ORDER BY id DESC";

$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$students_result = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Records Management | StudentHub CRUD</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <header>
    <nav class="navbar" aria-label="Main Navigation">
      <a href="/studenthub/index.html" class="brand">
        <span>StudentHub</span>
      </a>
      <ul class="nav-menu" id="nav-menu">
        <li class="nav-item"><a href="/studenthub/index.html">Home</a></li>
        <li class="nav-item"><a href="/studenthub/about.html">About</a></li>
        <li class="nav-item"><a href="/studenthub/events.html">Events</a></li>
        <li class="nav-item"><a href="/studenthub/profile.html">Directory</a></li>
        <li class="nav-item"><a href="/studenthub/faq.html">FAQ</a></li>
        <li class="nav-item"><a href="/studenthub/contact.html">Contact</a></li>
        <li class="nav-item"><a href="/studenthub/feedback.html">Feedback</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/php/students_crud.php" class="active">Student CRUD</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/php/events_crud.php">Event CRUD</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/ajax_crud.html">AJAX API</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/php/admin_dashboard.php">Admin Panel</a></li>
      </ul>
      <div class="nav-actions">
        <div id="nav-user-controls" style="display:flex; align-items:center; gap:6px;"></div>
        <button id="theme-toggle-btn" class="theme-toggle-btn" type="button" aria-label="Toggle theme">Dark Mode</button>
        <button id="hamburger-btn" class="hamburger-btn" type="button" aria-label="Menu">Menu</button>
      </div>
    </nav>
  </header>

  <div class="breadcrumb-container">
    <ul class="breadcrumb">
      <li class="breadcrumb-item"><a href="/studenthub/index.html">Home</a></li>
      <li class="breadcrumb-item"><a href="/studenthub/php/admin_dashboard.php">Admin</a></li>
      <li class="breadcrumb-item active">Student CRUD Management</li>
    </ul>
  </div>

  <main id="main-content">
    <div style="margin-bottom: 25px;">
      <h1>Student Records Management (Practical 11)</h1>
      <p class="text-muted">Perform complete MySQL Create, Read, Update, and Delete operations using procedural PHP.</p>
    </div>

    <?php if (!empty($alert_msg)): ?>
      <div class="alert alert-<?php echo $alert_type; ?>">
        <div><?php echo $alert_msg; ?></div>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start;">
      <!-- FORM CARD: ADD OR EDIT -->
      <div class="card">
        <h2 style="font-size: 1.3rem; margin-bottom: 15px; color: var(--primary-color);">
          <?php echo $edit_mode ? 'Edit Student Record' : 'Add New Student'; ?>
        </h2>

        <form method="POST" action="students_crud.php">
          <input type="hidden" name="student_id" value="<?php echo $edit_mode ? $form_data['id'] : '0'; ?>">

          <div class="form-group">
            <label class="form-label" for="enrollment_no">Enrollment No *</label>
            <input type="text" id="enrollment_no" name="enrollment_no" class="form-control" required
                   placeholder="e.g. 24CE025" value="<?php echo htmlspecialchars($form_data['enrollment_no']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="name">Full Name *</label>
            <input type="text" id="name" name="name" class="form-control" required
                   placeholder="e.g. Hardik Shah" value="<?php echo htmlspecialchars($form_data['name']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Email Address *</label>
            <input type="email" id="email" name="email" class="form-control" required
                   placeholder="e.g. hardik.s@charusat.edu.in" value="<?php echo htmlspecialchars($form_data['email']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="mobile">Mobile (10 digits) *</label>
            <input type="tel" id="mobile" name="mobile" class="form-control" required
                   placeholder="e.g. 9825123456" value="<?php echo htmlspecialchars($form_data['mobile']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="course">Course *</label>
            <select id="course" name="course" class="form-control" required>
              <option value="B.Tech CE" <?php echo ($form_data['course'] === 'B.Tech CE') ? 'selected' : ''; ?>>B.Tech CE</option>
              <option value="B.Tech IT" <?php echo ($form_data['course'] === 'B.Tech IT') ? 'selected' : ''; ?>>B.Tech IT</option>
              <option value="B.Tech CSE" <?php echo ($form_data['course'] === 'B.Tech CSE') ? 'selected' : ''; ?>>B.Tech CSE</option>
              <option value="BCA" <?php echo ($form_data['course'] === 'BCA') ? 'selected' : ''; ?>>BCA</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="year">Academic Year *</label>
            <select id="year" name="year" class="form-control" required>
              <option value="1" <?php echo ($form_data['year'] == 1) ? 'selected' : ''; ?>>1st Year</option>
              <option value="2" <?php echo ($form_data['year'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
              <option value="3" <?php echo ($form_data['year'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
              <option value="4" <?php echo ($form_data['year'] == 4) ? 'selected' : ''; ?>>4th Year</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Gender</label>
            <div class="radio-group">
              <label class="radio-item">
                <input type="radio" name="gender" value="Male" <?php echo ($form_data['gender'] === 'Male') ? 'checked' : ''; ?>> Male
              </label>
              <label class="radio-item">
                <input type="radio" name="gender" value="Female" <?php echo ($form_data['gender'] === 'Female') ? 'checked' : ''; ?>> Female
              </label>
            </div>
          </div>

          <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit" class="btn btn-primary btn-block">
              <?php echo $edit_mode ? 'Update Student' : 'Save Student'; ?>
            </button>
            <?php if ($edit_mode): ?>
              <a href="students_crud.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- TABLE CARD: SEARCH, FILTER & LIST -->
      <div class="card">
        <!-- Search and Filter Form -->
        <form method="GET" action="students_crud.php" class="filter-bar" style="margin-bottom: 20px; display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
          <input type="text" name="search" class="filter-input" placeholder="Search by name, enrollment or email..."
                 value="<?php echo htmlspecialchars($search_term); ?>" style="flex:1; min-width:200px;">
          <select name="course_filter" class="filter-select">
            <option value="All" <?php echo ($course_filter === 'All') ? 'selected' : ''; ?>>All Courses</option>
            <option value="B.Tech CE" <?php echo ($course_filter === 'B.Tech CE') ? 'selected' : ''; ?>>B.Tech CE</option>
            <option value="B.Tech IT" <?php echo ($course_filter === 'B.Tech IT') ? 'selected' : ''; ?>>B.Tech IT</option>
            <option value="B.Tech CSE" <?php echo ($course_filter === 'B.Tech CSE') ? 'selected' : ''; ?>>B.Tech CSE</option>
            <option value="BCA" <?php echo ($course_filter === 'BCA') ? 'selected' : ''; ?>>BCA</option>
          </select>
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
          <?php if (!empty($search_term) || $course_filter !== 'All'): ?>
            <a href="students_crud.php" class="btn btn-outline btn-sm">Reset</a>
          <?php endif; ?>
          <a href="/studenthub/php/students_crud.php?export=csv" class="btn btn-secondary btn-sm" style="white-space:nowrap; margin-left:auto;">Download Students CSV</a>
        </form>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Enrollment</th>
                <th>Name</th>
                <th>Email / Mobile</th>
                <th>Course & Year</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($students_result) === 0): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">No student records matching your query.</td>
                </tr>
              <?php else: ?>
                <?php $idx = 1; while ($s = mysqli_fetch_assoc($students_result)): ?>
                  <tr>
                    <td><?php echo $idx++; ?></td>
                    <td><strong><?php echo htmlspecialchars($s['enrollment_no']); ?></strong></td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td>
                      <div><a href="mailto:<?php echo htmlspecialchars($s['email']); ?>" style="color:var(--primary-color);"><?php echo htmlspecialchars($s['email']); ?></a></div>
                      <small class="text-muted">Mobile: <?php echo htmlspecialchars($s['mobile']); ?></small>
                    </td>
                    <td>
                      <span class="badge badge-tech"><?php echo htmlspecialchars($s['course']); ?></span>
                      <small class="text-muted">Yr <?php echo htmlspecialchars($s['year']); ?></small>
                    </td>
                    <td>
                      <div style="display:flex; gap:6px;">
                        <a href="students_crud.php?edit=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        <a href="students_crud.php?delete=<?php echo $s['id']; ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Are you sure you want to delete student: <?php echo addslashes($s['name']); ?>?');">
                           Delete
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> StudentHub Portal | ITUE203 Practical 11 (MySQL CRUD)</p>
    </div>
  </footer>

  <script src="/studenthub/js/script.js"></script>
</body>
</html>
