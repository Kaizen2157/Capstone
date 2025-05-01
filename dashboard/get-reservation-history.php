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

$query = "SELECT r.slot_number, r.start_date, r.start_time, r.duration_hours, r.total_cost, r.created_at 
          FROM reservations r 
          WHERE r.user_id = ? 
          ORDER BY r.created_at DESC 
          LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$reservations = [];
while ($row = $result->fetch_assoc()) {
    date_default_timezone_set('Asia/Manila'); // GMT+8

    $start_datetime_str = $row['start_date'] . ' ' . $row['start_time'];
    $start_datetime = new DateTime($start_datetime_str);

    // Add duration_hours to get end datetime
    $end_datetime = clone $start_datetime;
    $end_datetime->modify("+{$row['duration_hours']} hours");

    $row['start_time'] = $start_datetime->format(DATE_ATOM); // ISO 8601
    $row['end_time'] = $end_datetime->format(DATE_ATOM);     // ISO 8601

    // Adding price to the reservation data
    $row['tota_cost'] = number_format($row['total_cost'], 2); // Formatting price to two decimals

    $reservations[] = $row;
}

echo json_encode($reservations);
$stmt->close();
$conn->close();
?>
