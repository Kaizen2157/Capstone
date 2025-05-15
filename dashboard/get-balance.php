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


// if (!isset($_SESSION['user_id'])) {
//     // Clear any remaining session data
//     session_unset();
//     session_destroy();
    
//     // Redirect to login
//     header('Location: ../frontend/backups/login/login.html?session_expired=1');
//     exit;
// }

$user_id = $_SESSION['user_id']; 

// Get balance from the users table
$query = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$balanceData = $result->fetch_assoc();
$balance = $balanceData['balance']; // Get the balance directly from the users table

// Get recent reservations (for transaction history)
$query = "SELECT * FROM reservations WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reservationResult = $stmt->get_result();

$transactions = [];
while ($row = $reservationResult->fetch_assoc()) {
    // Create a transaction history based on reservations
    $transaction = [
        'amount' => $row['total_cost'], 
        'description' => 'Reservation payment for slot ' . $row['slot_number'], 
        'created_at' => $row['created_at']
    ];
    $transactions[] = $transaction;
}

// If there were any deposits, you can handle them by adding additional logic here

echo json_encode(['balance' => $balance, 'transactions' => $transactions]);

$stmt->close();
$conn->close();
?>