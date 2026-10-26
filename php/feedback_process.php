<?php
/**
 * ==========================================================
 * StudentHub Portal - Feedback Form Processor
 * Practical 7: Form Processing & CSV File Storage
 * ==========================================================
 */

require_once __DIR__ . '/auth_check.php';
check_login(); // Protected Route - Login Required

$message_status = '';
$status_type = 'info';

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

$feedback_file = __DIR__ . '/../data/feedback.csv';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['student_name']) ? sanitize_input($_POST['student_name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $category = isset($_POST['category']) ? sanitize_input($_POST['category']) : 'General';
    $comments = isset($_POST['comments']) ? sanitize_input($_POST['comments']) : '';

    if (empty($name) || empty($email) || empty($comments)) {
        $message_status = 'Please fill in all required feedback fields.';
        $status_type = 'danger';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $message_status = 'Please enter a valid email address.';
        $status_type = 'danger';
    } else {
        // Determine line count for incremental ID
        $line_count = 1;
        if (file_exists($feedback_file)) {
            $lines = file($feedback_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $line_count = count($lines); // line 0 is header
        }

        $new_id = $line_count; // sequential ID
        $submitted_at = date('Y-m-d H:i:s');

        // Open CSV file in append mode ('a')
        $handle = fopen($feedback_file, 'a');
        if ($handle !== false) {
            $row = [$new_id, $name, $email, $rating, $category, $comments, $submitted_at];
            fputcsv($handle, $row);
            fclose($handle);

            $message_status = "Thank you, {$name}! Your rating and review have been saved to data/feedback.csv.";
            $status_type = 'success';
        } else {
            $message_status = 'Could not open feedback CSV file for writing.';
            $status_type = 'danger';
        }
    }
}

// Read all feedback entries from CSV
$all_feedbacks = [];
if (file_exists($feedback_file)) {
    if (($handle = fopen($feedback_file, 'r')) !== false) {
        $header = fgetcsv($handle); // read header row
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) >= 7) {
                $all_feedbacks[] = [
                    'id' => $row[0],
                    'name' => $row[1],
                    'email' => $row[2],
                    'rating' => $row[3],
                    'category' => $row[4],
                    'comments' => $row[5],
                    'submitted_at' => $row[6]
                ];
            }
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Feedback Log | StudentHub</title>
  <link rel="stylesheet" href="/studenthub/css/style.css">
  <link rel="stylesheet" href="/studenthub/css/responsive.css">
</head>
<body>
  <header>
    <div class="navbar">
      <a href="/studenthub/index.html" class="brand">StudentHub</a>
      <ul class="nav-menu">
        <li class="nav-item"><a href="/studenthub/index.html">Home</a></li>
        <li class="nav-item"><a href="/studenthub/about.html">About</a></li>
        <li class="nav-item"><a href="/studenthub/events.html">Events</a></li>
        <li class="nav-item"><a href="/studenthub/faq.html">FAQ</a></li>
        <li class="nav-item"><a href="/studenthub/contact.html">Contact</a></li>
        <li class="nav-item"><a href="/studenthub/feedback.html" class="active">Feedback</a></li>
        <li class="nav-item"><a href="/studenthub/php/students_crud.php">Students (P11)</a></li>
        <li class="nav-item"><a href="/studenthub/ajax_crud.html">Live API (P13)</a></li>
      </ul>
      <div class="nav-actions">
        <div id="nav-user-controls" class="nav-user-slot"></div>
        <button id="theme-toggle-btn" class="theme-toggle-btn btn btn-outline btn-sm" type="button" aria-label="Toggle theme">Dark Mode</button>
      </div>
    </div>
  </header>

  <main>
    <div style="max-width: 950px; margin: 30px auto;">
      <?php if (!empty($message_status)): ?>
        <div class="alert alert-<?php echo $status_type; ?>">
          <div><?php echo $message_status; ?></div>
        </div>
      <?php endif; ?>

      <div class="card" style="margin-bottom: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <div>
            <h2>Saved Student Feedback (data/feedback.csv)</h2>
            <p class="text-muted">Parsed live from the CSV file using procedural PHP fgetcsv().</p>
          </div>
          <div>
            <a href="/studenthub/feedback.html" class="btn btn-outline btn-sm">Submit New Feedback</a>
            <a href="/studenthub/index.html" class="btn btn-primary btn-sm">Return to Home</a>
          </div>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Student Name</th>
                <th>Email</th>
                <th>Rating</th>
                <th>Category</th>
                <th>Feedback Comments</th>
                <th>Submitted On</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($all_feedbacks)): ?>
                <tr><td colspan="7" class="text-center text-muted">No feedback recorded yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_reverse($all_feedbacks) as $f): ?>
                  <tr>
                    <td><strong>#<?php echo htmlspecialchars($f['id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($f['name']); ?></td>
                    <td><a href="mailto:<?php echo htmlspecialchars($f['email']); ?>" style="color:var(--primary-color);"><?php echo htmlspecialchars($f['email']); ?></a></td>
                    <td>
                      <span style="font-weight:700;">
                        <?php echo htmlspecialchars($f['rating']) . ' / 5'; ?>
                      </span>
                    </td>
                    <td><span class="badge badge-tech"><?php echo htmlspecialchars($f['category']); ?></span></td>
                    <td><?php echo htmlspecialchars($f['comments']); ?></td>
                    <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($f['submitted_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <footer>
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> StudentHub Portal | ITUE203 Practical 7</p>
    </div>
  </footer>

  <script src="/studenthub/js/script.js"></script>
</body>
</html>
