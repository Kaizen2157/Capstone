<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed']));
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['hasActiveReservation' => false]);
    exit;
}

$userId = $_SESSION['user_id'];
$now = date('Y-m-d H:i:s');

// Check for active reservations (either not started or currently active)
$query = "SELECT COUNT(*) AS count 
          FROM reservations 
          WHERE user_id = ? 
          AND status = 'reserved'
          AND (
              (start_date = CURDATE() AND end_time > CURTIME()) OR
              (start_date > CURDATE()) OR
              (start_date = CURDATE() AND start_time > CURTIME())
          )";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'hasActiveReservation' => $result['count'] > 0
]);

$stmt->close();
$conn->close();
?>