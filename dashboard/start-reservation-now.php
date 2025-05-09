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

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Check if the data exists
if (isset($data['reservationId'])) {
    $reservationId = $data['reservationId'];

    // Update the reservation start time to the current time
    $currentTime = date('Y-m-d H:i:s');
    $query = "UPDATE reservations SET start_time = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $currentTime, $reservationId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update reservation start time']);
    }
} else {
    // If no reservationId is provided, return an error
    echo json_encode(['success' => false, 'message' => 'Reservation ID not provided']);
}
?>
