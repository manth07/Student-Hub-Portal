<?php
/**
 * ==========================================================
 * StudentHub Portal - Administrator Dashboard & Analytics
 * Practical 14: Admin Analytics, RBAC & System Integration
 * ==========================================================
 */

require_once __DIR__ . '/auth_check.php';
check_admin(); // Strict Admin Only Guard
require_once __DIR__ . '/db_connect.php';

// ----------------------------------------------------------
// 1. Fetch Dynamic System Statistics (COUNT Queries)
// ----------------------------------------------------------
$total_users_q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$total_users = mysqli_fetch_assoc($total_users_q)['total'] ?? 0;

$total_students_q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");
$total_students = mysqli_fetch_assoc($total_students_q)['total'] ?? 0;

$total_events_q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM events");
$total_events = mysqli_fetch_assoc($total_events_q)['total'] ?? 0;

$total_regs_q = mysqli_query($conn, "SELECT COUNT(*) AS total FROM registrations");
$total_regs = mysqli_fetch_assoc($total_regs_q)['total'] ?? 0;

// ----------------------------------------------------------
// 2. Fetch Latest Registrations with Student & Event Details
// ----------------------------------------------------------
$recent_regs_query = "
    SELECT r.id, r.registered_at, s.name AS student_name, s.enrollment_no, s.course, e.title AS event_title, e.category AS event_category
    FROM registrations r
    JOIN students s ON r.student_id = s.id
    JOIN events e ON r.event_id = e.id
    ORDER BY r.id DESC
    LIMIT 8
";
$recent_regs = mysqli_query($conn, $recent_regs_query);

// ----------------------------------------------------------
// 3. Fetch Recent Audit Logs
// ----------------------------------------------------------
$audit_query = "SELECT * FROM audit_logs ORDER BY id DESC LIMIT 10";
$audit_logs = mysqli_query($conn, $audit_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard & Analytics | StudentHub</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/responsive.css">
</head>
<body>
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <header>
    <nav class="navbar" aria-label="Main Navigation">
      <a href="/studenthub/index.html" class="brand">
        <span>StudentHub <strong>Admin</strong></span>
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
        <li class="nav-item admin-only-link"><a href="/studenthub/php/events_crud.php">Event CRUD</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/ajax_crud.html">AJAX API</a></li>
        <li class="nav-item admin-only-link"><a href="/studenthub/php/admin_dashboard.php" class="active">Admin Panel</a></li>
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
      <li class="breadcrumb-item active">Admin Executive Console</li>
    </ul>
  </div>

  <main id="main-content">
    <!-- Welcome Header & Quick Actions -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px; margin-bottom:25px;">
      <div>
        <h1>System Administrator Console</h1>
        <p class="text-muted">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong> (<code><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></code>) | Role: <span class="badge badge-success">Admin</span></p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="/studenthub/php/students_crud.php" class="btn btn-primary btn-sm">Manage Students</a>
        <a href="/studenthub/php/events_crud.php" class="btn btn-secondary btn-sm">Publish Event</a>
        <a href="/studenthub/ajax_crud.html" class="btn btn-outline btn-sm">Live AJAX API</a>
      </div>
    </div>

    <!-- 4 Dynamic Metric Statistic Cards -->
    <div class="grid-4">
      <div class="card stat-card" style="border-top-color: #1e40af;">
        <div class="stat-number"><?php echo $total_users; ?></div>
        <div class="stat-label">Total Registered Users</div>
        <small class="text-muted">Students & Staff</small>
      </div>

      <div class="card stat-card" style="border-top-color: #0d9488;">
        <div class="stat-number"><?php echo $total_students; ?></div>
        <div class="stat-label">Enrolled Students</div>
        <small class="text-muted">CE, IT, CSE, BCA</small>
      </div>

      <div class="card stat-card" style="border-top-color: #f59e0b;">
        <div class="stat-number"><?php echo $total_events; ?></div>
        <div class="stat-label">Active Campus Events</div>
        <small class="text-muted">Hackathons & Fests</small>
      </div>

      <div class="card stat-card" style="border-top-color: #10b981;">
        <div class="stat-number"><?php echo $total_regs; ?></div>
        <div class="stat-label">Total Registrations</div>
        <small class="text-muted">Across all events</small>
      </div>
    </div>

    <!-- TWO COLUMN DATA TABLES -->
    <div class="grid-2">
      <!-- Recent Registrations -->
      <div class="card">
        <h2 style="font-size: 1.25rem; margin-bottom: 15px; color: var(--primary-color);">
          Recent Event Registrations
        </h2>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Student</th>
                <th>Event & Track</th>
                <th>Registered At</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($recent_regs) === 0): ?>
                <tr><td colspan="3" class="text-center text-muted">No event registrations found.</td></tr>
              <?php else: ?>
                <?php while ($r = mysqli_fetch_assoc($recent_regs)): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($r['student_name']); ?></strong>
                      <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($r['enrollment_no']); ?> (<?php echo htmlspecialchars($r['course']); ?>)</div>
                    </td>
                    <td>
                      <div><?php echo htmlspecialchars($r['event_title']); ?></div>
                      <span class="badge badge-<?php echo strtolower($r['event_category']); ?>"><?php echo htmlspecialchars($r['event_category']); ?></span>
                    </td>
                    <td style="font-size:0.82rem; color:var(--text-muted);">
                      <?php echo htmlspecialchars($r['registered_at']); ?>
                    </td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Security Audit Logs -->
      <div class="card">
        <h2 style="font-size: 1.25rem; margin-bottom: 15px; color: var(--primary-color);">
          System Security & Audit Trail (audit_logs)
        </h2>
        <div class="table-responsive">
          <table>
            <thead>
              <tr>
                <th>Action</th>
                <th>User / Initiator</th>
                <th>Details</th>
                <th>Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($audit_logs) === 0): ?>
                <tr><td colspan="4" class="text-center text-muted">No audit logs recorded.</td></tr>
              <?php else: ?>
                <?php while ($log = mysqli_fetch_assoc($audit_logs)): ?>
                  <tr>
                    <td>
                      <span class="badge" style="background:#e0f2fe; color:#0369a1;">
                        <?php echo htmlspecialchars($log['action']); ?>
                      </span>
                    </td>
                    <td style="font-size:0.85rem;"><?php echo htmlspecialchars($log['user_email']); ?></td>
                    <td style="font-size:0.85rem;"><?php echo htmlspecialchars($log['details']); ?></td>
                    <td style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($log['created_at']); ?></td>
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
      <p>&copy; <?php echo date('Y'); ?> StudentHub Portal | ITUE203 Practical 14 (Admin Console)</p>
    </div>
  </footer>

  <script src="/studenthub/js/script.js"></script>
</body>
</html>
