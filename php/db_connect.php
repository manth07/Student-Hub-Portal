<?php
/**
 * ==========================================================
 * StudentHub Portal - Database Connection
 * Practical 8: Database Connectivity using Procedural MySQLi
 * ==========================================================
 */

// Step 1: Database configuration parameters
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'studenthub_db';

// Step 2: Establish connection using procedural mysqli_connect
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Step 3: Check if connection was successful
if (!$conn) {
    die("<div style='font-family:sans-serif; padding:20px; color:#991b1b; background:#fee2e2; border:1px solid #f87171; border-radius:8px; margin:20px;'>
            <h3>Database Connection Failed</h3>
            <p><strong>Error Message:</strong> " . htmlspecialchars(mysqli_connect_error()) . "</p>
            <p>Please ensure MySQL is running in your XAMPP Control Panel and database 'studenthub_db' exists.</p>
         </div>");
}

// Step 4: Set character set to utf8mb4 for unicode support
mysqli_set_charset($conn, 'utf8mb4');
?>
