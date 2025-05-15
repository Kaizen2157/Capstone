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
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    header('Location: ../frontend/backups/login/login.html?session_expired=1');
    exit;
}

// Set timezone and get current datetime
date_default_timezone_set('Asia/Manila');
$current_datetime = date('Y-m-d H:i:s');

// First, mark expired reservations as done
$update_query = "UPDATE reservations 
                SET status = 'done' 
                WHERE status = 'reserved' 
                AND CONCAT(start_date, ' ', end_time) < ?";

// When reservation is completed (e.g., in a separate script like complete-reservation.php)
$query = "UPDATE reservations SET status = 'done' WHERE id = ? AND status = 'reserved'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();

// Record the transaction ONLY now
if ($stmt->affected_rows > 0) {
    $transaction_stmt = $conn->prepare("INSERT INTO transactions 
        (user_id, amount, type, reference_id, description, balance_after) 
        VALUES (?, ?, 'reservation', ?, ?, ?)");
    
    $description = "Parking completed for reservation #$reservation_id";
    $transaction_stmt->bind_param("idssd", 
        $user_id, 
        -$total_cost, // Deduct now
        $reservation_id,
        $description,
        $new_balance);
    
    $transaction_stmt->execute();
}
                
$stmt = $conn->prepare($update_query);
$stmt->bind_param('s', $current_datetime);
$stmt->execute();

$response = ['hasReservation' => false];

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT id, slot_number, start_date, start_time, end_time, total_cost, status, start_button_clicked 
            FROM reservations 
            WHERE user_id = ? AND status = 'reserved'
            AND CONCAT(start_date, ' ', end_time) > ?
            ORDER BY created_at DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $current_datetime);
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