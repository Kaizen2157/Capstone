<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_system";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Handle POST request for booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $car_plate = $_POST['car_plate'];
    $selected_slots = $_POST['selected_slot'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $price = $_POST['total_cost'];
    $receipt_code = 'RCPT-' . strtoupper(uniqid());

    foreach (explode(',', $selected_slots) as $slot_number) {
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, car_plate, slot_number, start_time, end_time, price, receipt_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssds", $user_id, $car_plate, $slot_number, $start_time, $end_time, $price, $receipt_code);

        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
            exit();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Reservation saved.']);
    $stmt->close();
    $conn->close();
}
?>
