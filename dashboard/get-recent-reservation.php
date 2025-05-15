<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Ensures correct timezone

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

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT slot_number, start_time, end_time, status 
        FROM reservations 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    $reservations[] = [
        'slot' => $row['slot_number'],
        'start_time' => date("M d, Y h:i A", strtotime($row['start_time'])),
        'end_time' => date("M d, Y h:i A", strtotime($row['end_time'])),
        'status' => ucfirst($row['status']),
    ];
}

echo date_default_timezone_get(); // Should return Asia/Manila
echo date("Y-m-d H:i:s"); // Show the current PHP time

echo json_encode($reservations);


$stmt->close();
$conn->close();
?>
