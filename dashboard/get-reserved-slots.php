<?php
session_start();
require_once '../db_connect.php';

date_default_timezone_set('Asia/Manila');
$now = date('Y-m-d H:i:s');

// First, run cleanup of expired reservations
$cleanupQuery = "UPDATE reservations r
                JOIN slots s ON r.slot_number = s.slot_number
                SET r.status = 'done',
                    s.status = 'available',
                    s.reserved_by = NULL,
                    s.reservation_start = NULL,
                    s.reservation_end = NULL
                WHERE r.status = 'reserved' 
                AND CONCAT(r.end_date, ' ', r.end_time) <= ?";
                
$cleanupStmt = $conn->prepare($cleanupQuery);
$cleanupStmt->bind_param("s", $now);
$cleanupStmt->execute();
$affectedRows = $cleanupStmt->affected_rows;
$cleanupStmt->close();

// Log cleanup activity
error_log("Cleaned up $affectedRows expired reservations at $now");

// Now get currently reserved slots
$sql = "SELECT s.slot_number 
        FROM slots s
        JOIN reservations r ON s.slot_number = r.slot_number
        WHERE r.status = 'reserved' 
        AND CONCAT(r.end_date, ' ', r.end_time) > ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $now);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row['slot_number'];
}

echo json_encode([
    'reservedSlots' => $slots,
    'cleaned' => $affectedRows
]);
?>