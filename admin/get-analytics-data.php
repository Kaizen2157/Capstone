<?php
require_once 'admin-functions.php';
require_once '../db_connect.php';

header('Content-Type: application/json');

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

$range = $_GET['range'] ?? 'today';
$data = getEarningsChartData($conn, $range);

echo json_encode($data);
$conn->close();
?>