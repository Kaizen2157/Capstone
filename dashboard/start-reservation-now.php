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

date_default_timezone_set('Asia/Manila'); // Set this to your local timezone
$currentTime = date('Y-m-d H:i:s');

$data = json_decode(file_get_contents('php://input'), true);
$reservationId = $data['reservationId'];

if (isset($reservationId)) {
    // Update the reservation start time to the current time
    $currentTime = date('Y-m-d H:i:s');
    
    // Update the 'start_button_clicked' field to indicate the button was clicked
    $query = "UPDATE reservations SET start_time = ?, start_button_clicked = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $currentTime, $reservationId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update reservation']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Reservation ID not provided']);
}
?>
