<?php
/**
 * ==========================================================
 * StudentHub Portal - Contact Form Processor
 * Practical 7: Form Processing, Input Sanitization & JSON Storage
 * ==========================================================
 */

require_once __DIR__ . '/auth_check.php';
check_login(); // Protected Route - Login Required

$message_status = '';
$status_type = 'info';

// Helper function to sanitize user inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

$contacts_file = __DIR__ . '/../data/contacts.json';

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? sanitize_input($_POST['subject']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

    // Basic validation
    if (empty($name) || empty($email) || empty($message)) {
        $message_status = 'Please fill in all required fields (Name, Email, Message).';
        $status_type = 'danger';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $message_status = 'Please provide a valid email address.';
        $status_type = 'danger';
    } else {
        // Read existing JSON data
        $contacts = [];
        if (file_exists($contacts_file)) {
            $json_raw = file_get_contents($contacts_file);
            $contacts = json_decode($json_raw, true) ?? [];
        }

        // Generate new ID and append entry
        $new_id = count($contacts) > 0 ? max(array_column($contacts, 'id')) + 1 : 1;
        $new_entry = [
            'id' => $new_id,
            'name' => $name,
            'email' => $email,
            'subject' => $subject,
            'message' => $message,
            'submitted_at' => date('Y-m-d H:i:s')
        ];

        $contacts[] = $new_entry;

        // Save back to JSON file
        if (file_put_contents($contacts_file, json_encode($contacts, JSON_PRETTY_PRINT))) {
            $message_status = "Thank you, {$name}! Your message has been safely saved to data/contacts.json.";
            $status_type = 'success';
        } else {
            $message_status = 'Unable to write message to JSON database file. Please check folder permissions.';
            $status_type = 'danger';
        }
    }
}

// Load current entries to display in HTML table
$saved_contacts = [];
if (file_exists($contacts_file)) {
    $saved_contacts = json_decode(file_get_contents($contacts_file), true) ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Form Submission | StudentHub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
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
        <li class="nav-item"><a href="/studenthub/contact.html" class="active">Contact</a></li>
        <li class="nav-item"><a href="/studenthub/feedback.html">Feedback</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/php/students_crud.php">Student CRUD</a></li>
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

  <main>
    <div style="max-width: 900px; margin: 30px auto;">
      <?php if (!empty($message_status)): ?>
        <div class="alert alert-<?php echo $status_type; ?>">
          <div><?php echo $message_status; ?></div>
        </div>
      <?php endif; ?>

      <div class="card" style="margin-bottom: 25px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
          <div>
            <h2>Stored Contact Messages (data/contacts.json)</h2>
            <p class="text-muted">Below are all sanitized messages processed via PHP file handling.</p>
          </div>
          <div>
            <a href="/studenthub/contact.html" class="btn btn-outline btn-sm">Back to Contact Form</a>
            <a href="/studenthub/index.html" class="btn btn-primary btn-sm">Return to Home</a>
          </div>
        </div>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message Content</th>
                <th>Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($saved_contacts)): ?>
                <tr><td colspan="6" class="text-center text-muted">No messages saved yet.</td></tr>
              <?php else: ?>
                <?php foreach (array_reverse($saved_contacts) as $c): ?>
                  <tr>
                    <td><strong>#<?php echo htmlspecialchars($c['id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                    <td><a href="mailto:<?php echo htmlspecialchars($c['email']); ?>" style="color:var(--primary-color);"><?php echo htmlspecialchars($c['email']); ?></a></td>
                    <td><?php echo htmlspecialchars($c['subject']); ?></td>
                    <td><?php echo htmlspecialchars($c['message']); ?></td>
                    <td style="font-size:0.85rem; color:var(--text-muted);"><?php echo htmlspecialchars($c['submitted_at']); ?></td>
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
      <p>&copy; <?php echo date('Y'); ?> StudentHub Portal | ITUE203 Web Development Frameworks Practical 7</p>
    </div>
  </footer>

  <script src="/studenthub/js/script.js"></script>
</body>
</html>
