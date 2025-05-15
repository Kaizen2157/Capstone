<?php
session_start();
require_once '../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Session expired or not logged in');
    }

    date_default_timezone_set('Asia/Manila');

    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No input data received');
    }

    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }

    if (!isset($data['reservationId'])) {
        throw new Exception('Missing reservation ID');
    }

    $reservationId = $data['reservationId'];

    // Get reservation details including duration
    $stmt = $conn->prepare("SELECT duration_hours FROM reservations WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('ii', $reservationId, $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Reservation not found or not owned by user');
    }

    $reservation = $result->fetch_assoc();
    $duration = $reservation['duration_hours'];

    // Calculate new times
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s');
    
    // Calculate new end time
    $startDateTime = new DateTime("$currentDate $currentTime");
    $endDateTime = clone $startDateTime;
    $endDateTime->add(new DateInterval("PT{$duration}H"));
    
    $newEndTime = $endDateTime->format('H:i:s');

    // Update reservation with new times
    $updateStmt = $conn->prepare("UPDATE reservations SET 
        start_date = ?, 
        start_time = ?, 
        end_time = ?,
        start_button_clicked = 1 
        WHERE id = ?");
    
    if (!$updateStmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $updateStmt->bind_param('sssi', $currentDate, $currentTime, $newEndTime, $reservationId);
    if (!$updateStmt->execute()) {
        throw new Exception('Update failed: ' . $updateStmt->error);
    }

    // NEW: Get the slot number for this reservation
    $slotQuery = $conn->prepare("SELECT slot_number FROM reservations WHERE id = ?");
    $slotQuery->bind_param("i", $reservationId);
    $slotQuery->execute();
    $slotResult = $slotQuery->get_result();
    
    if ($slotResult->num_rows === 0) {
        throw new Exception('Could not find slot for this reservation');
    }
    
    $slotData = $slotResult->fetch_assoc();
    $slotNumber = $slotData['slot_number'];
    $slotQuery->close();

    // NEW: Update the slot's reservation times
    $newStartDateTime = "$currentDate $currentTime";
    $newEndDateTime = $endDateTime->format('Y-m-d H:i:s');
    
    $updateSlotStmt = $conn->prepare("UPDATE slots SET 
        reservation_start = ?,
        reservation_end = ?
        WHERE slot_number = ?");
    
    if (!$updateSlotStmt) {
        throw new Exception('Slot update preparation failed: ' . $conn->error);
    }

    $updateSlotStmt->bind_param('sss', $newStartDateTime, $newEndDateTime, $slotNumber);
    if (!$updateSlotStmt->execute()) {
        throw new Exception('Slot update failed: ' . $updateSlotStmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reservation started successfully',
        'newStart' => $newStartDateTime,
        'newEnd' => $newEndDateTime
    ]);

} catch (Exception $e) {
    error_log('Error in start-reservation-now.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>