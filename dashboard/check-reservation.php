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

// Mark expired reservations as done
date_default_timezone_set('Asia/Manila');
$now = date('H:i:s');
$today = date('Y-m-d');

$conn->query("UPDATE reservations 
              SET status = 'done' 
              WHERE status = 'reserved' 
              AND start_date <= '$today' 
              AND end_time <= '$now'");

$response = ['hasReservation' => false];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT id, slot_number, start_date, start_time, end_time, total_cost, status, start_button_clicked 
        FROM reservations 
        WHERE user_id = ? AND status IN ('active', 'reserved') 
        ORDER BY created_at DESC 
        LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $response['hasReservation'] = true;
        $response['reservation'] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
