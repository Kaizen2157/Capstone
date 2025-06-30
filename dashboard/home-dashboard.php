<?php
session_start();
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

// Turn off HTML errors
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=parking_system", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET time_zone = '" . date('P') . "';");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Database connection failed"]);
    exit;
}

require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => "Session expired"]);
    exit;
}

// Fetch cost if requested
if (isset($_GET['get_cost']) && $_GET['get_cost'] == 'true') {
    $query = "SELECT parking_cost FROM settings WHERE id = 1";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    echo json_encode(['cost' => $row['parking_cost']]);
    exit;
}

function isTimeInFuture($date, $time) {
    $reservationDateTime = new DateTime("$date $time", new DateTimeZone('Asia/Manila'));
    $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
    return $reservationDateTime > $currentDateTime;
}

function hasTimeConflict($conn, $slot_number, $start_date, $start_time, $end_time) {
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
        $start_date,
        $end_time, $start_time,
        $end_time, $start_time,
        $start_time, $end_time
    );
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_number = $_POST['contact_number'];
    $car_plate = $_POST['car_plate'];
    $slot_number = $_POST['slot_number'];
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $duration_hours = intval($_POST['duration']);
    $total_cost = floatval($_POST['total_cost']);

    // Validate time is not in past
    if (strtotime("$start_date $start_time") < time()) {
        echo json_encode(['success' => false, 'message' => "Cannot create reservation in the past"]);
        exit;
    }

    // Validate duration
    if ($duration_hours < 1 || $duration_hours > 12) {
        echo json_encode(['success' => false, 'message' => "Invalid duration. Please select between 1 and 12 hours."]);
        exit;
    }

    // Validate date format and range
    $today = new DateTime('today', new DateTimeZone('Asia/Manila'));
    $maxDate = (clone $today)->modify('+2 days')->setTime(23, 59, 59);
    $inputDate = DateTime::createFromFormat('Y-m-d', $start_date, new DateTimeZone('Asia/Manila'));
    
    if (!$inputDate) {
        echo json_encode(['success' => false, 'message' => "Invalid date format."]);
        exit;
    }

    $inputDate->setTime(0, 0, 0);
    if ($inputDate < $today || $inputDate > $maxDate) {
        echo json_encode(['success' => false, 'message' => "Invalid booking date. You can only book from today up to 2 days ahead."]);
        exit;
    }

    // Calculate end time
    $start_timestamp = strtotime("$start_date $start_time");
    $end_timestamp = $start_timestamp + ($duration_hours * 3600);
    $end_time = date('H:i:s', $end_timestamp);

    // Check for time conflicts
    if (hasTimeConflict($conn, $slot_number, $start_date, $start_time, $end_time)) {
        echo json_encode(['success' => false, 'message' => "This slot is already reserved for the selected time period."]);
        exit;
    }

    // Check balance
    $balance_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $balance_stmt->bind_param("i", $user_id);
    $balance_stmt->execute();
    $user = $balance_stmt->get_result()->fetch_assoc();
    $balance_stmt->close();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => "User not found."]);
        exit;
    }

    $required_deposit = $total_cost * 0.5;
    if ($user['balance'] < $required_deposit) {
        echo json_encode([
            'success' => false,
            'message' => "Insufficient balance. You need at least 50% of the total cost (₱" . number_format($required_deposit, 2) . ") to make a reservation."
        ]);
        exit;
    }

    if ($user['balance'] < $total_cost) {
        echo json_encode([
            'success' => false,
            'message' => "Insufficient balance for full payment. You need ₱" . number_format($total_cost, 2) . " but only have ₱" . number_format($user['balance'], 2)
        ]);
        exit;
    }

    // Deduct balance
    $new_balance = $user['balance'] - $total_cost;
    $update_balance_stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $update_balance_stmt->bind_param("di", $new_balance, $user_id);
    $update_balance_stmt->execute();
    $update_balance_stmt->close();

    // Create reservation
    $stmt = $conn->prepare("INSERT INTO reservations 
        (user_id, first_name, last_name, contact_number, car_plate, slot_number, 
         start_date, start_time, end_time, duration_hours, total_cost, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $status = 'reserved';
    $stmt->bind_param(
        "issssssssdds",
        $user_id,
        $first_name,
        $last_name,
        $contact_number,
        $car_plate,
        $slot_number,
        $start_date,
        $start_time,
        $end_time,
        $duration_hours,
        $total_cost,
        $status
    );

    if ($stmt->execute()) {
        $reservation_id = $stmt->insert_id;
        $reservation_start = "$start_date $start_time";
        $reservation_end = date('Y-m-d H:i:s', $end_timestamp);
        
        // Update slot status
        $slot_update_stmt = $conn->prepare("UPDATE slots SET status = 'reserved', reserved_by = ?, reservation_start = ?, reservation_end = ? WHERE slot_number = ?");
        $slot_update_stmt->bind_param("isss", $user_id, $reservation_start, $reservation_end, $slot_number);
        
        if ($slot_update_stmt->execute()) {
            // Record transaction
            $transaction_stmt = $conn->prepare("INSERT INTO transactions 
                (user_id, amount, type, reference_id, description, balance_after) 
                VALUES (?, ?, ?, ?, ?, ?)");
            $transaction_stmt->bind_param("idssd", 
                $user_id, 
                -$total_cost,
                'reservation',
                $reservation_id,
                "Parking reservation for slot $slot_number",
                $new_balance);
                
            $transaction_stmt->execute();
            $transaction_stmt->close();
            
            echo json_encode([
                'success' => true,
                'message' => 'Reservation created successfully',
                'reservation_id' => $reservation_id,
                'receipt_data' => [
                    'name' => "$first_name $last_name",
                    'contact' => $contact_number,
                    'plate' => $car_plate,
                    'slot' => $slot_number,
                    'start' => "$start_date $start_time",
                    'end' => $reservation_end,
                    'total' => $total_cost
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating slot status']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating reservation']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>