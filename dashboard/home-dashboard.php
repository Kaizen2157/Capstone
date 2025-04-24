<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed']));
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $user_id = $_SESSION['user_id'];
    $car_plate = trim($_POST['car_plate']);
    $slot = trim($_POST['selected_slot']);
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $end_date = $_POST['end_date'];
    $end_time = $_POST['end_time'];
    $price = floatval($_POST['total_cost']);

    // Combine date + time
    $start_datetime = $start_date . ' ' . $start_time;
    $end_datetime = $end_date . ' ' . $end_time;

    // Generate receipt code
    $receipt_code = strtoupper(uniqid("RCPT"));

    // Optional: Set default status
    $status = 'reserved';

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO reservations 
        (user_id, car_plate, slot_number, start_time, end_time, price, status, receipt_code, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param("issssdss", $user_id, $car_plate, $slot, $start_datetime, $end_datetime, $price, $status, $receipt_code);

    if ($stmt->execute()) {
        // Success!
        echo json_encode(['success' => true, 'message' => 'Booking successful', 'receipt_code' => $receipt_code]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to book', 'error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

?>
