<?php
include 'db_connect.php'; // Include the database connection file

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error); // Debugging: Show if there is a connection issue
}

$result = $conn->query("SELECT * FROM slots"); // Query to fetch all data from 'slots' table

// Check if the query returned any results
if ($result === false) {
    die("Error in query: " . $conn->error); // Debugging: Show SQL query error
}

$slots = [];

while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

// Check if any slots were fetched
if (empty($slots)) {
    die("No data found in the 'slots' table."); // Debugging: Show if no data is returned
}

echo json_encode($slots); // Sends data as JSON for JavaScript to use

$conn->close();




?>
