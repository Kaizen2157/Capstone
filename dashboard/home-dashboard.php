<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['submit'])) {
    // Make sure user is logged in
    if (!isset($_SESSION['user_id'])) {
        die('User not logged in.');
    }

    $user_id = $_SESSION['user_id'];

    // Collect booking form data
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_number = $_POST['contact_number'];
    $car_plate = $_POST['car_plate'];
    $slot_number = $_POST['slot_number'];
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $duration_hours = $_POST['duration'];
    $total_cost = $_POST['total_cost'];

    // Calculate end time
    $start_timestamp = strtotime("$start_date $start_time");
    $end_timestamp = $start_timestamp + ($duration_hours * 3600);
    $end_time = date('H:i:s', $end_timestamp);

    // Insert booking into reservations table
    $stmt = $conn->prepare("INSERT INTO reservations 
        (user_id, first_name, last_name, contact_number, car_plate, slot_number, start_date, start_time, end_time, duration_hours, total_cost) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "isssssssssd",
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
        $total_cost
    );

    if ($stmt->execute()) {
        // Success — you can return a success message if needed
        echo "Booking saved successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
