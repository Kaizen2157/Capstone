<?php
session_start();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Session expired");
    }

    $current_user_id = $_SESSION['user_id'];

    // Set timezone for MySQL
    $conn->query("SET time_zone = '+08:00'");

    $current_time = date('Y-m-d H:i:s');

    // Get ALL active reservations (current and future) with user info
    $query = "SELECT 
                r.slot_number, 
                r.user_id,
                r.start_date,
                r.start_time,
                r.end_time,
                CASE 
                    WHEN ? BETWEEN TIMESTAMP(r.start_date, r.start_time) AND TIMESTAMP(r.start_date, r.end_time) THEN 'active'
                    ELSE 'reserved'
                END AS status,
                CASE
                    WHEN r.user_id = ? THEN 1
                    ELSE 0
                END AS is_current_user
              FROM reservations r
              WHERE r.status = 'reserved'
              AND TIMESTAMP(r.start_date, r.end_time) > ?
              ORDER BY r.start_date, r.start_time, r.slot_number";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if (!$stmt->bind_param("sis", $current_time, $current_user_id, $current_time)) {
        throw new Exception("Binding parameters failed");
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $reservations = [];

    while ($row = $result->fetch_assoc()) {
        $reservations[] = [
            'slot' => $row['slot_number'],
            'date' => $row['start_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'status' => $row['status'],
            'is_current_user' => $row['is_current_user']
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'current_time' => $current_time,
        'count' => count($reservations),
        'current_user_id' => $current_user_id
    ]);

} catch (Exception $e) {
    // Clean up any open connections
    if (isset($conn)) $conn->close();
    if (isset($stmt)) $stmt->close();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ]);
}
?>