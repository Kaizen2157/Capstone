<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
$now = date('Y-m-d H:i:s');

// First clean up any expired reservations
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
$cleanupStmt->close();

// Get all slots with their reservation status
$query = "SELECT 
            s.slot_number as id, 
            CASE 
                WHEN r.id IS NOT NULL 
                AND r.status = 'reserved'
                AND CONCAT(r.start_date, ' ', r.start_time) <= ?
                AND CONCAT(r.end_date, ' ', r.end_time) > ? THEN 'reserved'
                ELSE 'available'
            END as status
          FROM slots s
          LEFT JOIN reservations r ON s.slot_number = r.slot_number 
              AND r.status = 'reserved'
              AND CONCAT(r.start_date, ' ', r.start_time) <= ?
              AND CONCAT(r.end_date, ' ', r.end_time) > ?";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $now, $now, $now, $now);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = [
        'id' => $row['id'],
        'status' => $row['status']
    ];
}

echo json_encode([
    'success' => true,
    'slots' => $slots,
    'timestamp' => $now
]);

$stmt->close();
$conn->close();
?>