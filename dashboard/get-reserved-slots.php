<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Manila');
$now = date('Y-m-d H:i:s');

$sql = "SELECT slot_number FROM reservations 
        WHERE status = 'reserved' AND end_time >= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row['slot_number'];
}

echo json_encode(['reservedSlots' => $slots]);
?>
