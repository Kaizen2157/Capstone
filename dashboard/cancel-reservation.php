<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['reservationId'])) {
    echo json_encode(['success' => false, 'message' => 'Missing reservation ID']);
    exit;
}

$reservationId = $data['reservationId'];

// Step 1: Get reservation details for refund
$stmt = $conn->prepare("SELECT user_id, total_cost FROM reservations WHERE id = ? AND status = 'reserved'");
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Reservation not found or already cancelled.']);
    exit;
}

$reservation = $result->fetch_assoc();
$userId = $reservation['user_id'];
$refundAmount = $reservation['total_cost']; // decimal(10,2)

// Step 2: Begin transaction
$conn->begin_transaction();

try {
    // Step 3: Update reservation to cancelled
    $updateStmt = $conn->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
    $updateStmt->bind_param("i", $reservationId);
    $updateStmt->execute();

    // Step 4: Refund amount to user's balance
    $refundStmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $refundStmt->bind_param("di", $refundAmount, $userId);
    $refundStmt->execute();

    // Step 5: Commit transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Reservation cancelled and amount refunded.']);
} catch (Exception $e) {
    $conn->rollback();
    error_log("Transaction failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Cancellation failed.']);
}
?>
