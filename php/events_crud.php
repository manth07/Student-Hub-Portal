<?php
/**
 * ==========================================================
 * StudentHub Portal - Event Management CRUD with File Upload
 * Practical 12: File Uploads, Image Validation & MySQL Storage
 * ==========================================================
 */

require_once __DIR__ . '/auth_check.php';
check_admin(); // Strict Admin Only Guard

require_once __DIR__ . '/db_connect.php';

$alert_msg = '';
$alert_type = 'info';

function clean($data) {
    return htmlspecialchars(stripslashes(trim($data ?? '')), ENT_QUOTES, 'UTF-8');
}

// ----------------------------------------------------------
// 1. Handle DELETE Event
// ----------------------------------------------------------
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);

    // Check existing poster to delete if not default
    $ev_q = mysqli_query($conn, "SELECT title, poster_image FROM events WHERE id = {$del_id}");
    $ev_info = mysqli_fetch_assoc($ev_q);

    $del_stmt = mysqli_prepare($conn, "DELETE FROM events WHERE id = ?");
    mysqli_stmt_bind_param($del_stmt, "i", $del_id);

    if (mysqli_stmt_execute($del_stmt)) {
        if ($ev_info && $ev_info['poster_image'] !== 'default_poster.svg') {
            $file_path = __DIR__ . '/../uploads/' . $ev_info['poster_image'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        // Audit log
        $actor = $_SESSION['email'] ?? 'admin@charusat.edu.in';
        $details = "Deleted event: " . ($ev_info['title'] ?? "ID {$del_id}");
        $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'EVENT_DELETE', ?)");
        mysqli_stmt_bind_param($log_stmt, "ss", $actor, $details);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);

        $alert_msg = "Event deleted successfully.";
        $alert_type = "success";
    } else {
        $alert_msg = "Error deleting event: " . mysqli_error($conn);
        $alert_type = "danger";
    }
    mysqli_stmt_close($del_stmt);
}

// ----------------------------------------------------------
// 2. Handle ADD or UPDATE with Image Upload
// ----------------------------------------------------------
$edit_mode = false;
$edit_id = 0;
$form_data = [
    'title' => '',
    'category' => 'Technical',
    'event_date' => date('Y-m-d', strtotime('+7 days')),
    'venue' => '',
    'description' => '',
    'poster_image' => 'default_poster.svg',
    'status' => 'Open'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = intval($_POST['event_id'] ?? 0);
    $title = clean($_POST['title']);
    $category = clean($_POST['category']);
    $event_date = clean($_POST['event_date']);
    $venue = clean($_POST['venue']);
    $description = clean($_POST['description']);
    $status = clean($_POST['status'] ?? 'Open');
    $existing_poster = clean($_POST['existing_poster'] ?? 'default_poster.svg');

    $poster_name = $existing_poster;

    // Handle File Upload if provided
    $upload_ok = true;
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['poster']['tmp_name'];
        $file_size = $_FILES['poster']['size'];
        $file_name = $_FILES['poster']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($file_ext, $allowed_exts)) {
            $alert_msg = "Invalid file type. Only JPG, PNG, WEBP, and SVG formats are allowed.";
            $alert_type = "danger";
            $upload_ok = false;
        } elseif ($file_size > $max_size) {
            $alert_msg = "Uploaded file exceeds the maximum limit of 2MB.";
            $alert_type = "danger";
            $upload_ok = false;
        } else {
            // Generate clean unique filename
            $poster_name = 'event_' . time() . '_' . rand(100, 999) . '.' . $file_ext;
            $dest = __DIR__ . '/../uploads/' . $poster_name;

            if (!move_uploaded_file($file_tmp, $dest)) {
                $alert_msg = "Failed to upload image. Please check directory permissions.";
                $alert_type = "danger";
                $upload_ok = false;
            }
        }
    }

    if ($upload_ok) {
        if ($event_id > 0) {
            // UPDATE
            $upd = mysqli_prepare($conn, "UPDATE events SET title = ?, category = ?, event_date = ?, venue = ?, description = ?, poster_image = ?, status = ? WHERE id = ?");
            mysqli_stmt_bind_param($upd, "sssssssi", $title, $category, $event_date, $venue, $description, $poster_name, $status, $event_id);

            if (mysqli_stmt_execute($upd)) {
                $actor = $_SESSION['email'] ?? 'admin@charusat.edu.in';
                $details = "Updated event: {$title} on {$event_date}";
                $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'EVENT_UPDATE', ?)");
                mysqli_stmt_bind_param($log_stmt, "ss", $actor, $details);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);

                $alert_msg = "Event updated successfully!";
                $alert_type = "success";
            } else {
                $alert_msg = "Update failed: " . mysqli_error($conn);
                $alert_type = "danger";
            }
            mysqli_stmt_close($upd);
        } else {
            // INSERT
            $ins = mysqli_prepare($conn, "INSERT INTO events (title, category, event_date, venue, description, poster_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ins, "sssssss", $title, $category, $event_date, $venue, $description, $poster_name, $status);

            if (mysqli_stmt_execute($ins)) {
                $actor = $_SESSION['email'] ?? 'admin@charusat.edu.in';
                $details = "Published new campus event: {$title} ({$category})";
                $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'EVENT_ADD', ?)");
                mysqli_stmt_bind_param($log_stmt, "ss", $actor, $details);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);

                $alert_msg = "Event published successfully!";
                $alert_type = "success";
            } else {
                $alert_msg = "Insert failed: " . mysqli_error($conn);
                $alert_type = "danger";
            }
            mysqli_stmt_close($ins);
        }
    }
}

// ----------------------------------------------------------
// 3. Check for EDIT parameter
// ----------------------------------------------------------
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_q = mysqli_prepare($conn, "SELECT * FROM events WHERE id = ?");
    mysqli_stmt_bind_param($edit_q, "i", $edit_id);
    mysqli_stmt_execute($edit_q);
    $res = mysqli_stmt_get_result($edit_q);

    if ($row = mysqli_fetch_assoc($res)) {
        $edit_mode = true;
        $form_data = $row;
    }
    mysqli_stmt_close($edit_q);
}

// ----------------------------------------------------------
// 4. Query All Events
// ----------------------------------------------------------
$events_list = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Event Management & Poster Uploads | StudentHub CRUD</title>
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
        <li class="nav-item admin-only-link"><a href="/studenthub/php/students_crud.php">Student CRUD</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/php/events_crud.php" class="active">Event CRUD</a></li>
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
      <li class="breadcrumb-item active">Event CRUD & Poster Upload</li>
    </ul>
  </div>

  <main id="main-content">
    <div style="margin-bottom: 25px;">
      <h1>Event Management & File Upload (Practical 12)</h1>
      <p class="text-muted">Upload event promotional banners (max 2MB) and manage events in MySQL.</p>
    </div>

    <?php if (!empty($alert_msg)): ?>
      <div class="alert alert-<?php echo $alert_type; ?>">
        <div><?php echo $alert_msg; ?></div>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start;">
      <!-- FORM CARD -->
      <div class="card">
        <h2 style="font-size: 1.3rem; margin-bottom: 15px; color: var(--primary-color);">
          <?php echo $edit_mode ? 'Edit Event Details' : 'Create New Event'; ?>
        </h2>

        <form method="POST" action="events_crud.php" enctype="multipart/form-data">
          <input type="hidden" name="event_id" value="<?php echo $edit_mode ? $form_data['id'] : '0'; ?>">
          <input type="hidden" name="existing_poster" value="<?php echo htmlspecialchars($form_data['poster_image']); ?>">

          <div class="form-group">
            <label class="form-label" for="title">Event Title *</label>
            <input type="text" id="title" name="title" class="form-control" required
                   placeholder="e.g. HackCHARUSAT 2026" value="<?php echo htmlspecialchars($form_data['title']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="category">Category *</label>
            <select id="category" name="category" class="form-control" required>
              <option value="Technical" <?php echo ($form_data['category'] === 'Technical') ? 'selected' : ''; ?>>Technical</option>
              <option value="Cultural" <?php echo ($form_data['category'] === 'Cultural') ? 'selected' : ''; ?>>Cultural</option>
              <option value="Workshop" <?php echo ($form_data['category'] === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
              <option value="Sports" <?php echo ($form_data['category'] === 'Sports') ? 'selected' : ''; ?>>Sports</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="event_date">Event Date *</label>
            <input type="date" id="event_date" name="event_date" class="form-control" required
                   value="<?php echo htmlspecialchars($form_data['event_date']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="venue">Venue / Location *</label>
            <input type="text" id="venue" name="venue" class="form-control" required
                   placeholder="e.g. CSPIT Seminar Hall 1" value="<?php echo htmlspecialchars($form_data['venue']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="description">Event Description *</label>
            <textarea id="description" name="description" class="form-control" rows="3" required
                      placeholder="Detailed event summary..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label" for="status">Event Status</label>
            <select id="status" name="status" class="form-control">
              <option value="Open" <?php echo ($form_data['status'] === 'Open') ? 'selected' : ''; ?>>Open for Registration</option>
              <option value="Closed" <?php echo ($form_data['status'] === 'Closed') ? 'selected' : ''; ?>>Closed</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="poster">Event Poster Image (Max 2MB, JPG/PNG/WebP/SVG)</label>
            <input type="file" id="poster" name="poster" class="form-control" accept=".jpg,.jpeg,.png,.webp,.svg">
            <?php if (!empty($form_data['poster_image'])): ?>
              <small class="text-muted" style="display:block; margin-top:4px;">
                Current: <code><?php echo htmlspecialchars($form_data['poster_image']); ?></code>
              </small>
            <?php endif; ?>
          </div>

          <div style="display:flex; gap:10px; margin-top:20px;">
            <button type="submit" class="btn btn-primary btn-block">
              <?php echo $edit_mode ? 'Update Event' : 'Publish Event'; ?>
            </button>
            <?php if ($edit_mode): ?>
              <a href="events_crud.php" class="btn btn-outline">Cancel</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- LIST CARD -->
      <div class="card">
        <h2 style="font-size: 1.3rem; margin-bottom: 15px; color: var(--primary-color);">
          Campus Events Directory (<?php echo mysqli_num_rows($events_list); ?> total)
        </h2>

        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Poster</th>
                <th>Title & Category</th>
                <th>Date & Venue</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($events_list) === 0): ?>
                <tr><td colspan="5" class="text-center text-muted">No events listed in database.</td></tr>
              <?php else: ?>
                <?php while ($ev = mysqli_fetch_assoc($events_list)): ?>
                  <tr>
                    <td>
                      <img src="../uploads/<?php echo htmlspecialchars($ev['poster_image']); ?>"
                           alt="Poster" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px; border: 1px solid var(--border-color);">
                    </td>
                    <td>
                      <strong><?php echo htmlspecialchars($ev['title']); ?></strong>
                      <div><span class="badge badge-<?php echo strtolower($ev['category']); ?>"><?php echo htmlspecialchars($ev['category']); ?></span></div>
                    </td>
                    <td>
                      <div>Date: <?php echo htmlspecialchars($ev['event_date']); ?></div>
                      <small class="text-muted">Venue: <?php echo htmlspecialchars($ev['venue']); ?></small>
                    </td>
                    <td>
                      <span class="badge badge-<?php echo strtolower($ev['status']); ?>">
                        <?php echo htmlspecialchars($ev['status']); ?>
                      </span>
                    </td>
                    <td>
                      <div style="display:flex; gap:6px;">
                        <a href="events_crud.php?edit=<?php echo $ev['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                        <a href="events_crud.php?delete=<?php echo $ev['id']; ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete event: <?php echo addslashes($ev['title']); ?>?');">
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
      <p>&copy; <?php echo date('Y'); ?> StudentHub Portal | ITUE203 Practical 12 (File Upload CRUD)</p>
    </div>
  </footer>

  <script src="/studenthub/js/script.js"></script>
</body>
</html>
