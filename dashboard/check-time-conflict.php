<?php
session_start();
require_once '../db_connect.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid input data');
    }

    $date = $input['date'];
    $start_time = $input['start_time'];
    $end_time = $input['end_time'];
    $slot_number = $input['slot_number'];

    $query = "SELECT id FROM reservations 
              WHERE slot_number = ? 
              AND status = 'reserved'
              AND start_date = ?
              AND (
                  (start_time < ? AND end_time > ?) OR
                  (start_time < ? AND end_time > ?) OR
                  (start_time >= ? AND end_time <= ?)
              )";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssss", 
        $slot_number,
        $date,
        $end_time, $start_time,
        $end_time, $start_time,
        $start_time, $end_time
    );
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode([
        'success' => true,
        'hasConflict' => $result->num_rows > 0
    ]);

    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>