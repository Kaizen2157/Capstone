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

// Fetch the current parking cost from the database
$sql = "SELECT * FROM settings WHERE id = 1";
$result = $conn->query($sql);
$currentCost = $result->fetch_assoc()['parking_cost'];

// Update the cost if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newCost = $_POST['cost'];

    // Update the cost in the database
    $stmt = $conn->prepare("UPDATE settings SET parking_cost = ? WHERE id = 1");
    $stmt->bind_param("d", $newCost);
    if ($stmt->execute()) {
        echo "Cost updated successfully!";
    } else {
        echo "Error updating cost.";
    }
}

$conn->close();
?>