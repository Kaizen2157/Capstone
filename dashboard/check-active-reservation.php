<?php
session_start();

// DB Connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

$query = "SELECT COUNT(*) AS count FROM reservations WHERE user_id = ? AND status = 'reserved' AND end_time > NOW()";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

// Return the result
echo json_encode(['hasActiveReservation' => $result['count'] > 0]);
?>
