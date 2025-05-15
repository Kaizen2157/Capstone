<?php
session_start();
date_default_timezone_set('Asia/Manila');

       try {
       $pdo = new PDO("mysql:host=localhost;dbname=parking_system", "root", "");
       $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
       $pdo->exec("SET time_zone = '" . date('P') . "';");  // Use date('P') to get offset in +/-HH:mm format
   } catch (PDOException $e) {
       echo "Connection failed: " . $e->getMessage();
   }

require_once '../db_connect.php'; // Include your database connection file

if (!isset($_SESSION['user_id'])) {
    // Clear any remaining session data
    session_unset();
    session_destroy();
    
    // Redirect to login
    header('Location: ../frontend/backups/login/login.html?session_expired=1');
    exit;
}

// Fetch cost if requested
if (isset($_GET['get_cost']) && $_GET['get_cost'] == 'true') {
    $query = "SELECT parking_cost FROM settings WHERE id = 1";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $parkingCost = $row['parking_cost'];

    header('Content-Type: application/json');
    echo json_encode(['cost' => $parkingCost]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Booking form data
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

    // ✅ Validate duration (1 to 12 hours)
    if ($duration_hours < 1 || $duration_hours > 12) {
        echo "Invalid duration. Please select between 1 and 12 hours.";
        $conn->close();
        exit;
    }

// Validate booking date (today to 2 days ahead)
$today = new DateTime('today', new DateTimeZone('Asia/Manila'));
$maxDate = (new DateTime('today', new DateTimeZone('Asia/Manila')))->modify('+2 days')->setTime(23, 59, 59);
$inputDate = DateTime::createFromFormat('Y-m-d', $start_date, new DateTimeZone('Asia/Manila'));

if (!$inputDate) {
    echo json_encode([
        'success' => false,
        'message' => "Invalid date format."
    ]);
    exit;
}

// Set input date to start of day for comparison
$inputDate->setTime(0, 0, 0);

if ($inputDate < $today || $inputDate > $maxDate) {
    echo json_encode([
        'success' => false,
        'message' => "Invalid booking date. You can only book from today up to 2 days ahead (until 11:59 PM)."
    ]);
    exit;
}

    // ✅ Calculate end time
    $start_timestamp = strtotime("$start_date $start_time");
    $end_timestamp = $start_timestamp + ($duration_hours * 3600);
    $end_time = date('H:i:s', $end_timestamp);

    // FIRST: Fetch user's current balance
    $balance_stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $balance_stmt->bind_param("i", $user_id);
    $balance_stmt->execute();
    $balance_result = $balance_stmt->get_result();
    $user = $balance_result->fetch_assoc();
    $balance_stmt->close();

    if (!$user) {
        die("User not found.");
    }

    // SECOND: Check if balance is enough
    if ($user['balance'] < $total_cost) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance.']);
        $conn->close();
        exit;
    }
    

    // THIRD: Deduct balance
    $new_balance = $user['balance'] - $total_cost;
    $update_balance_stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $update_balance_stmt->bind_param("di", $new_balance, $user_id);
    $update_balance_stmt->execute();
    $update_balance_stmt->close();



    // FOURTH: Proceed to save booking
$status = 'reserved'; // Set status explicitly

$stmt = $conn->prepare("INSERT INTO reservations 
    (user_id, first_name, last_name, contact_number, car_plate, slot_number, start_date, start_time, end_time, duration_hours, total_cost, status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
    $reservation_id = $stmt->insert_id; // Get the ID of the new reservation
    
    echo "Booking saved successfully.";
    
    // Create datetime strings for slot reservation
    $reservation_start = "$start_date $start_time";
    $reservation_end = date('Y-m-d H:i:s', $end_timestamp);
    
    // Update the slot status
    $slot_update_stmt = $conn->prepare("UPDATE slots SET status = 'reserved', reserved_by = ?, reservation_start = ?, reservation_end = ? WHERE slot_number = ?");
    $slot_update_stmt->bind_param("isss", $user_id, $reservation_start, $reservation_end, $slot_number);
    
    if ($slot_update_stmt->execute()) {
        echo "Slot updated successfully.";
        
        // ✅ NEW: Record the transaction
        // After successful reservation creation, record the transaction
$transaction_stmt = $conn->prepare("INSERT INTO transactions 
    (user_id, amount, type, reference_id, description, balance_after) 
    VALUES (?, ?, ?, ?, ?, ?)");
$description = "Parking reservation for slot $slot_number";
$transaction_amount = -$total_cost; // Negative amount for deduction

$transaction_stmt->bind_param("idssd", 
    $user_id, 
    $transaction_amount,
    'reservation', // type
    $reservation_id, // reference_id
    $description,
    $new_balance);
    
if (!$transaction_stmt->execute()) {
    error_log("Transaction recording failed: " . $transaction_stmt->error);
    // Don't exit, just log the error
}
$transaction_stmt->close();
    } else {
        echo "Error updating slot: " . $slot_update_stmt->error;
    }

    $slot_update_stmt->close();
    
} else {
    echo "Error: " . $stmt->error;
}

} else {
    echo "Invalid request.";
}

// Fetch user balance to display
$user_id = $_SESSION['user_id'];
$balance = 0.00;
$sql = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($balance);
$stmt->fetch();
$stmt->close();
$conn->close();
?>
