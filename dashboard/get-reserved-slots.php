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

$date = $_GET['date'] ?? date('Y-m-d');

// 💡 Get slot numbers where the start_time is on the same date and the status is active
$sql = "SELECT slot_number FROM reservations 
        WHERE DATE(start_time) = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$reservedSlots = [];
while ($row = $result->fetch_assoc()) {
    $reservedSlots[] = $row['slot_number'];
}

echo json_encode($reservedSlots);
?>
