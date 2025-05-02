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
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];

$response = ['success' => false, 'message' => 'Something went wrong.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Prepare the query to cancel the most recent active or pending reservation
    $sql = "UPDATE reservations 
            SET status = 'cancelled' 
            WHERE user_id = ? AND status IN ('reserved', 'pending') 
            ORDER BY created_at DESC 
            LIMIT 1";

    // Prepare the statement
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        // Error preparing the statement
        echo json_encode(['success' => false, 'message' => 'Failed to prepare query.']);
        exit;
    }

    // Bind parameters: 'i' means integer (for user_id)
    $stmt->bind_param("i", $user_id);

    // Execute the statement
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Your reservation has been cancelled.';
    } else {
        $response['message'] = 'Failed to cancel reservation.';
    }

    // Close the prepared statement
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
