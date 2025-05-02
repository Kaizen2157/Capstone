<?php
session_start();
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "SELECT slot_number, status FROM reservations WHERE DATE(start_time) = CURDATE()";
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
