<?php
session_start();
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    // Clear any remaining session data
    session_unset();
    session_destroy();
    
    // Redirect to login
    header('Location: ../frontend/backups/login/login.html?session_expired=1');
    exit;
}

// Mark expired reservations as done
date_default_timezone_set('Asia/Manila');
$now = date('H:i:s');
$today = date('Y-m-d');

$conn->query("UPDATE reservations 
              SET status = 'reserved' 
              WHERE status = 'reserved' 
              AND start_date <= '$today' 
              AND end_time <= '$now'");


$sql = "SELECT slot_number FROM reservations 
        WHERE status = 'reserved' AND end_time >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row['slot_number'];
}

echo json_encode(['reservedSlots' => $slots]);
?>
