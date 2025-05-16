<?php
session_start();
header('Content-Type: application/json'); //added
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// if (!isset($_SESSION['user_id'])) {
//     // Clear any remaining session data
//     session_unset();
//     session_destroy();
    
//     // Redirect to login
//     header('Location: ../frontend/backups/login/login.html?session_expired=1');
//     exit;
// }

// Only fetch slots that are reserved today
$query = "SELECT slot_number, status FROM reservations WHERE DATE(start_time) = CURDATE() AND status = 'reserved'";
$result = $conn->query($query);

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = [
        'id' => $row['slot_number'],
        'status' => $row['status']
    ];
}

echo json_encode(['slots' => $slots]);
?>
