<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection error']);
    exit();
}

if (isset($_POST['cost'])) {
    $cost = $_POST['cost'];
    $stmt = $conn->prepare("UPDATE settings SET parking_cost  = ?"); // make sure this table exists
    $stmt->bind_param("d", $cost);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Parking slot cost updated!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
?>
