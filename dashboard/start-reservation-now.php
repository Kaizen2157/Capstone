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

$data = json_decode(file_get_contents('php://input'), true);
$reservationId = $data['reservationId'];

// Update the reservation start time to the current time
$currentTime = date('Y-m-d H:i:s');
$query = "UPDATE reservations SET start_time = ? WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('si', $currentTime, $reservationId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
