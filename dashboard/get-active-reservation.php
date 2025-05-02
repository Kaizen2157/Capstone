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

$query = "SELECT id, slot_number, start_time, end_time FROM reservations WHERE user_id = ? AND status = 'reserved' AND end_time > NOW()";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['user_id']); // Assuming user ID is stored in the session
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $reservation = $result->fetch_assoc();
    echo json_encode(['reservation' => $reservation]);
} else {
    echo json_encode(['reservation' => null]);
}
?>
