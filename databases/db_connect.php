<?php
$servername = "localhost"; // depende kung local or server
$username = "root";         // user ng database mo
$password = "";             // password ng database mo (default blank sa XAMPP)
$dbname = "parking_system"; // database name mo

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
