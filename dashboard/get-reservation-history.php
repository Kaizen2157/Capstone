<?php
header('Content-Type: application/json');

session_start();
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed"]));
}

$user_id = $_SESSION['user_id']; 

$query = "SELECT * FROM reservations WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    // Convert datetime to ISO 8601
    $row['start_time'] = date('c', strtotime($row['start_time'])); // e.g. 2025-04-30T14:00:00+00:00
    $row['end_time'] = date('c', strtotime($row['end_time']));
    $reservations[] = $row;
}

echo json_encode($reservations);
$stmt->close();
$conn->close();
?>