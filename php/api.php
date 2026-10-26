<?php
/**
 * ==========================================================
 * StudentHub Portal - REST-like JSON API Endpoint
 * Practical 13: Asynchronous AJAX Event Registration & JSON API
 * ==========================================================
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 15-Minute Inactivity Timeout Check
$timeout_seconds = 900;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout_seconds)) {
    session_unset();
    session_destroy();
}

// Strict Admin-Only Guard for Practical 13 API
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Admin authentication required to access Practical 13 API.'
    ]);
    exit();
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/db_connect.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Procedural input sanitizer
function clean($val) {
    return htmlspecialchars(stripslashes(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

$user_email = $_SESSION['email'] ?? 'admin@charusat.edu.in';

switch ($action) {
    // ------------------------------------------------------
    // 1. LIVE KPI STATS (GET)
    // ------------------------------------------------------
    case 'stats':
        $res_reg = mysqli_query($conn, "SELECT COUNT(*) AS total FROM registrations");
        $res_evt = mysqli_query($conn, "SELECT COUNT(*) AS total FROM events");
        $res_std = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");

        $total_reg = mysqli_fetch_assoc($res_reg)['total'] ?? 0;
        $total_evt = mysqli_fetch_assoc($res_evt)['total'] ?? 0;
        $total_std = mysqli_fetch_assoc($res_std)['total'] ?? 0;

        echo json_encode([
            'status' => 'success',
            'api_status' => 'HTTP 200 OK - Connected to /studenthub/php/api.php',
            'data' => [
                'total_registrations' => intval($total_reg),
                'open_events' => intval($total_evt),
                'total_students' => intval($total_std),
                'server_time' => date('Y-m-d H:i:s')
            ]
        ], JSON_PRETTY_PRINT);
        break;

    // ------------------------------------------------------
    // 2. LIST STUDENTS FOR DROPDOWN (GET)
    // ------------------------------------------------------
    case 'list_students':
        $query = "SELECT id, enrollment_no, name, course, email FROM students ORDER BY name ASC";
        $result = mysqli_query($conn, $query);
        $students = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $students[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'count' => count($students),
            'data' => $students
        ], JSON_PRETTY_PRINT);
        break;

    // ------------------------------------------------------
    // 3. LIST OPEN EVENTS FOR DROPDOWN (GET)
    // ------------------------------------------------------
    case 'list_events':
        $query = "SELECT id, title, category, event_date, venue FROM events ORDER BY event_date ASC";
        $result = mysqli_query($conn, $query);
        $events = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'count' => count($events),
            'data' => $events
        ], JSON_PRETTY_PRINT);
        break;

    // ------------------------------------------------------
    // 4. LIST EVENT REGISTRATIONS (GET)
    // ------------------------------------------------------
    case 'list_registrations':
    case 'list':
        $query = "SELECT r.id, r.student_id, r.event_id, COALESCE(r.role, 'Participant') AS role, COALESCE(r.notes, '') AS notes, r.registered_at,
                         s.enrollment_no, s.name AS student_name, s.course, s.email AS student_email,
                         e.title AS event_title, e.category AS event_category, e.event_date, e.venue
                  FROM registrations r
                  JOIN students s ON r.student_id = s.id
                  JOIN events e ON r.event_id = e.id
                  ORDER BY r.id DESC";

        $result = mysqli_query($conn, $query);
        if (!$result) {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
            exit();
        }

        $registrations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $registrations[] = $row;
        }

        echo json_encode([
            'status' => 'success',
            'count' => count($registrations),
            'data' => $registrations
        ], JSON_PRETTY_PRINT);
        break;

    // ------------------------------------------------------
    // 5. ENROLL STUDENT INTO CAMPUS EVENT (AJAX POST)
    // ------------------------------------------------------
    case 'add_registration':
    case 'add':
        $student_id = intval($_POST['student_id'] ?? 0);
        $event_id = intval($_POST['event_id'] ?? 0);
        $role = clean($_POST['role'] ?? 'Participant');
        $notes = clean($_POST['notes'] ?? '');

        if ($student_id <= 0 || $event_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Please select both a valid Student and a Campus Event.']);
            exit();
        }

        // Allowed role values
        $valid_roles = ['Participant', 'Volunteer', 'Coordinator', 'Speaker'];
        if (!in_array($role, $valid_roles)) {
            $role = 'Participant';
        }

        // Duplicate registration check
        $dup_stmt = mysqli_prepare($conn, "SELECT id FROM registrations WHERE student_id = ? AND event_id = ?");
        mysqli_stmt_bind_param($dup_stmt, "ii", $student_id, $event_id);
        mysqli_stmt_execute($dup_stmt);
        mysqli_stmt_store_result($dup_stmt);

        if (mysqli_stmt_num_rows($dup_stmt) > 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Duplicate Entry: This student is already registered for this campus event.'
            ]);
            mysqli_stmt_close($dup_stmt);
            exit();
        }
        mysqli_stmt_close($dup_stmt);

        // Insert new registration
        $ins_stmt = mysqli_prepare($conn, "INSERT INTO registrations (student_id, event_id, role, notes) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins_stmt, "iiss", $student_id, $event_id, $role, $notes);

        if (mysqli_stmt_execute($ins_stmt)) {
            $new_reg_id = mysqli_insert_id($conn);
            mysqli_stmt_close($ins_stmt);

            // Fetch info for audit log
            $info_q = mysqli_query($conn, "SELECT s.name AS sname, s.enrollment_no, e.title AS etitle FROM students s, events e WHERE s.id = {$student_id} AND e.id = {$event_id}");
            $info = mysqli_fetch_assoc($info_q);
            $sname = $info['sname'] ?? "Student #{$student_id}";
            $senroll = $info['enrollment_no'] ?? '';
            $etitle = $info['etitle'] ?? "Event #{$event_id}";

            // Audit log
            $log_details = "AJAX Registration: {$sname} ({$senroll}) enrolled in '{$etitle}' as {$role}.";
            $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'AJAX_REGISTRATION_ADD', ?)");
            mysqli_stmt_bind_param($log_stmt, "ss", $user_email, $log_details);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            echo json_encode([
                'status' => 'success',
                'message' => "Successfully registered {$sname} for '{$etitle}' as {$role}!",
                'id' => $new_reg_id,
                'registration_id' => $new_reg_id
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)]);
            mysqli_stmt_close($ins_stmt);
        }
        break;

    // ------------------------------------------------------
    // 6. UPDATE PARTICIPANT ROLE (AJAX POST)
    // ------------------------------------------------------
    case 'update_role':
        $reg_id = intval($_POST['id'] ?? $_POST['registration_id'] ?? 0);
        $role = clean($_POST['role'] ?? 'Participant');

        if ($reg_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid registration ID for role update.']);
            exit();
        }

        $valid_roles = ['Participant', 'Volunteer', 'Coordinator', 'Speaker'];
        if (!in_array($role, $valid_roles)) {
            $role = 'Participant';
        }

        $upd_stmt = mysqli_prepare($conn, "UPDATE registrations SET role = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd_stmt, "si", $role, $reg_id);

        if (mysqli_stmt_execute($upd_stmt)) {
            mysqli_stmt_close($upd_stmt);

            // Audit log
            $log_details = "Updated registration #{$reg_id} role to {$role}.";
            $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'AJAX_REGISTRATION_ROLE_UPDATE', ?)");
            mysqli_stmt_bind_param($log_stmt, "ss", $user_email, $log_details);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            echo json_encode([
                'status' => 'success',
                'message' => "Registration #{$reg_id} role updated to '{$role}' successfully!"
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . mysqli_error($conn)]);
            mysqli_stmt_close($upd_stmt);
        }
        break;

    // ------------------------------------------------------
    // 7. REMOVE EVENT REGISTRATION (AJAX POST)
    // ------------------------------------------------------
    case 'delete_registration':
    case 'delete':
        $reg_id = intval($_POST['id'] ?? 0);

        if ($reg_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid registration ID for deletion.']);
            exit();
        }

        // Get details before delete for audit log
        $reg_info_q = mysqli_query($conn, "SELECT s.name AS sname, e.title AS etitle FROM registrations r JOIN students s ON r.student_id = s.id JOIN events e ON r.event_id = e.id WHERE r.id = {$reg_id}");
        $reg_info = mysqli_fetch_assoc($reg_info_q);

        $del_stmt = mysqli_prepare($conn, "DELETE FROM registrations WHERE id = ?");
        mysqli_stmt_bind_param($del_stmt, "i", $reg_id);

        if (mysqli_stmt_execute($del_stmt)) {
            mysqli_stmt_close($del_stmt);

            $sname = $reg_info['sname'] ?? "Registration #{$reg_id}";
            $etitle = $reg_info['etitle'] ?? "Event";
            $log_details = "Removed registration #{$reg_id}: {$sname} from '{$etitle}'.";
            $log_stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (user_email, action, details) VALUES (?, 'AJAX_REGISTRATION_DELETE', ?)");
            mysqli_stmt_bind_param($log_stmt, "ss", $user_email, $log_details);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            echo json_encode([
                'status' => 'success',
                'message' => "Registration #{$reg_id} removed successfully via AJAX."
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Delete failed: ' . mysqli_error($conn)]);
            mysqli_stmt_close($del_stmt);
        }
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Unknown action. Supported actions: stats, list_students, list_events, list_registrations, add_registration, update_role, delete_registration.'
        ], JSON_PRETTY_PRINT);
        break;
}
