<?php
$servername = "localhost";  // Change this if you're using a different server
$username = "root";         // Default username for XAMPP
$password = "";             // Default password for XAMPP (leave empty)
$dbname = "parking_system"; // Your database name

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: Remove this after testing
echo "Connected successfully";
?>
