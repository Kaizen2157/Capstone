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

$response = ['slots' => []];

// Fetch slot statuses (including "reserved", "cancelled", etc.)
$query = "SELECT slot_number, status FROM reservations";  // Change to 'reservations' if you're storing statuses there
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $response['slots'][] = $row;
}

header('Content-Type: application/json');
echo json_encode($response);
?>
