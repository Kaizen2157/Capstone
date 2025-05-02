<?php
// Start session
session_start();

// Set response content type
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    error_log("Database connection failed: " . $conn->connect_error); // Log the error
    exit;
}

// Check session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['reservationId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
    exit;
}

$reservationId = $data['reservationId'];

// Prepare and execute cancellation query
$stmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $reservationId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
    error_log("Failed to update reservation status: " . $stmt->error); // Log the error
}
?>
