<?php
session_start();
require_once '../db_connect.php';
$conn->query("SET time_zone = '+08:00'"); // For Asia/Manila

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
$current_time = date('Y-m-d H:i:s');

// 1. Clean up expired reservations
$cleanupQuery = "UPDATE reservations r
                JOIN slots s ON r.slot_number = s.slot_number
                SET r.status = 'done',
                    s.status = 'available'
                WHERE r.status = 'reserved' 
                AND CONCAT(r.start_date, ' ', r.end_time) < ?";
$cleanupStmt = $conn->prepare($cleanupQuery);
$cleanupStmt->bind_param("s", $current_time);
$cleanupStmt->execute();
$cleanupAffected = $cleanupStmt->affected_rows;

// 2. Get all active and future reservations
$reservationsQuery = "SELECT 
                        r.id,
                        r.slot_number,
                        r.status,
                        CONCAT(r.start_date, ' ', r.start_time) as start_datetime,
                        CONCAT(r.start_date, ' ', r.end_time) as end_datetime
                      FROM reservations r
                      WHERE r.status = 'reserved'
                      AND CONCAT(r.start_date, ' ', r.end_time) > ?
                      ORDER BY r.start_date, r.start_time";
$reservationsStmt = $conn->prepare($reservationsQuery);
$reservationsStmt->bind_param("s", $current_time);
$reservationsStmt->execute();
$reservationsResult = $reservationsStmt->get_result();
$allReservations = $reservationsResult->fetch_all(MYSQLI_ASSOC);

// 3. Get currently active reservations
$activeQuery = "SELECT 
                  r.slot_number
                FROM reservations r
                WHERE r.status = 'reserved'
                AND ? BETWEEN 
                    TIMESTAMP(r.start_date, r.start_time) AND 
                    TIMESTAMP(r.start_date, r.end_time)";
$activeStmt = $conn->prepare($activeQuery);
$activeStmt->bind_param("s", $current_time);
$activeStmt->execute();
$activeResult = $activeStmt->get_result();
$activeSlotNumbers = array_column($activeResult->fetch_all(MYSQLI_ASSOC), 'slot_number');

// 4. Prepare slots array with reservation details
$slotsQuery = "SELECT slot_number as id FROM slots ORDER BY slot_number";
$slotsResult = $conn->query($slotsQuery);
$slots = [];

while ($slot = $slotsResult->fetch_assoc()) {
    $slotId = $slot['id'];
    $slotData = ['slot' => $slotId];
    
    // Check if slot has active or future reservations
    foreach ($allReservations as $reservation) {
        if ($reservation['slot_number'] == $slotId) {
            $slotData['status'] = 'reserved';
            $slotData['reservation_id'] = $reservation['id'];
            $slotData['start_datetime'] = $reservation['start_datetime'];
            $slotData['end_datetime'] = $reservation['end_datetime'];
            break;
        }
    }
    
    // Default to available if no reservation found
    if (!isset($slotData['status'])) {
        $slotData['status'] = 'available';
    }
    
    $slots[] = $slotData;
}

// 5. Return the response (only 'success' and 'slots')
echo json_encode([
    'success' => true,
    'slots' => $slots
]);

// Close statements and connection
$cleanupStmt->close();
$reservationsStmt->close();
$activeStmt->close();
$conn->close();
?>