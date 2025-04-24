<?php
session_start();
header('Content-Type: application/json');

// 1️⃣ Authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'User not logged in.']);
    exit;
}

// 2️⃣ DB connection
$conn = new mysqli('localhost','root','','parking_system');
if ($conn->connect_error) {
    echo json_encode(['success'=>false,'message'=>'DB connection failed.']);
    exit;
}

// 3️⃣ Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id    = $_SESSION['user_id'];
    $car_plate  = $_POST['car_plate']       ?? '';
    $slots      = $_POST['selected_slot']   ?? '';
    $start_time = $_POST['start_time']      ?? '';
    $end_time   = $_POST['end_time']        ?? '';
    $price      = floatval($_POST['total_cost'] ?? 0);

    // Basic validation
    if (!$car_plate || !$slots || !$start_time || !$end_time) {
        echo json_encode(['success'=>false,'message'=>'Missing booking data.']);
        exit;
    }

    $receipt_code = 'RCPT-'.strtoupper(uniqid());
    $status       = 'active';

    // Insert one row per slot
    $stmt = $conn->prepare(
      "INSERT INTO reservations
       (user_id, car_plate, slot_number, start_time, end_time, price, status, receipt_code)
       VALUES (?,       ?,         ?,           ?,          ?,        ?,     ?,      ?)"
    );
    foreach (explode(',', $slots) as $slot) {
        $slot = trim($slot);
        $stmt->bind_param(
          "issssdss",
          $user_id,
          $car_plate,
          $slot,
          $start_time,
          $end_time,
          $price,
          $status,
          $receipt_code
        );
        if (!$stmt->execute()) {
            echo json_encode(['success'=>false,'message'=>'DB error: '.$stmt->error]);
            exit;
        }
    }

    echo json_encode(['success'=>true,'message'=>'Reservation saved.','receipt_code'=>$receipt_code]);
    $stmt->close();
    exit;
}
