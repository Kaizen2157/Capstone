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

// Mark expired reservations as done
date_default_timezone_set('Asia/Manila');
$now = date('H:i:s');
$today = date('Y-m-d');

$conn->query("UPDATE reservations 
              SET status = 'reserved' 
              WHERE status = 'reserved' 
              AND start_date <= '$today' 
              AND end_time <= '$now'");


$query = "SELECT id, slot_number, start_time, end_time FROM reservations WHERE user_id = ? AND status = 'reserved' AND end_time > NOW()";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['user_id']); // Assuming user ID is stored in the session
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['hasActiveReservation' => true]);
} else {
    echo json_encode(['hasActiveReservation' => false]);
}

?>
