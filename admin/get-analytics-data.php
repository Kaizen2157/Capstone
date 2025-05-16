<?php
require_once 'admin-functions.php';
require_once '../db_connect.php';

header('Content-Type: application/json');

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

$range = $_GET['range'] ?? 'today';

$data = [
    'chart' => getEarningsChartData($conn, $range),
    'earnings' => getEarningsByRange($conn, $range),
    'totalEarnings' => getTotalEarningsByRange($conn, $range),
    'totalBookings' => array_sum(array_column(getEarningsByRange($conn, $range), 'bookings'))
];

echo json_encode($data);
$conn->close();
?>