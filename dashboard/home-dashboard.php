<?php
session_start();
date_default_timezone_set('Asia/Manila');

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
    die('User not logged in.');
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

    // ✅ Validate booking date (today to 2 days ahead)
    $today = new DateTime('today');
    $maxDate = (new DateTime('today'))->modify('+2 days');
    $inputDate = DateTime::createFromFormat('Y-m-d', $start_date);

    if (!$inputDate || $inputDate < $today || $inputDate > $maxDate) {
        echo "Invalid booking date. You can only book from today up to 2 days ahead.";
        $conn->close();
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
        echo "Insufficient balance.";
        $conn->close();
        exit;
    }

    // THIRD: Deduct balance
    $new_balance = $user['balance'] - $total_cost;
    $update_balance_stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $update_balance_stmt->bind_param("di", $new_balance, $user_id);
    $update_balance_stmt->execute();
    $update_balance_stmt->close();

    // Check for overlapping reservations on the same slot
    $start_datetime = "$start_date $start_time";
    $end_datetime = date('Y-m-d H:i:s', $end_timestamp);

    $overlap_query = "SELECT * FROM reservations 
                      WHERE slot_number = ? 
                      AND status = 'reserved' 
                      AND (
                          (start_date = ? AND start_time < ? AND end_time > ?) OR
                          (start_date = ? AND start_time < ? AND end_time > ?)
                      )";

    $check_stmt = $conn->prepare($overlap_query);
    $check_stmt->bind_param("sssssss", 
        $slot_number,
        $start_date, $end_time, $start_time,
        $start_date, $end_time, $start_time
    );
    $check_stmt->execute();
    $overlap_result = $check_stmt->get_result();

    if ($overlap_result->num_rows > 0) {
        echo "Slot is already reserved for the selected time.";
        $conn->close();
        exit;
    }
    $check_stmt->close();


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
    echo "Booking saved successfully.";
    
    // AFTER INSERTING THE BOOKING, UPDATE THE SLOT STATUS IN THE 'slots' TABLE
    $slot_update_stmt = $conn->prepare("UPDATE slots SET status = 'reserved', reserved_by = ?, reservation_start = ?, reservation_end = ? WHERE slot_number = ?");
    $slot_update_stmt->bind_param("ssss", $user_id, $start_datetime, $end_datetime, $slot_number);
    
    if ($slot_update_stmt->execute()) {
        echo "Slot updated successfully.";
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
