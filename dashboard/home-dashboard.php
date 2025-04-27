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

if (!isset($_SESSION['user_id'])) {
    die('User not logged in.');
}

// Check if we are fetching the cost
if (isset($_GET['get_cost']) && $_GET['get_cost'] == 'true') {
    // Fetch the parking cost from the database (from the settings table or wherever you store it)
    $query = "SELECT parking_cost FROM settings WHERE id = 1"; // Adjust your query as needed
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $parkingCost = $row['parking_cost']; // e.g. 40

    // Return the cost as JSON
    header('Content-Type: application/json');
    echo json_encode(['cost' => $parkingCost]);

    exit; // Exit after returning the cost
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') { 
    // Your existing booking form data handling logic
    $user_id = $_SESSION['user_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $contact_number = $_POST['contact_number'];
    $car_plate = $_POST['car_plate'];
    $slot_number = $_POST['slot_number']; // Should be properly set by JS
    $start_date = $_POST['start_date'];
    $start_time = $_POST['start_time'];
    $duration_hours = $_POST['duration'];
    $total_cost = $_POST['total_cost'];

    // Calculate end time
    $start_timestamp = strtotime("$start_date $start_time");
    $end_timestamp = $start_timestamp + ($duration_hours * 3600);
    $end_time = date('H:i:s', $end_timestamp);

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
