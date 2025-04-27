<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: adminlog.php'); // Redirect to login if not logged in
    exit();
}

// Handle balance addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user-search']) && isset($_POST['balance-amount'])) {
    $user_search = $_POST['user-search'];
    $balance_amount = $_POST['balance-amount'];

    // Search for the user by username or email
    $stmt = $conn->prepare("SELECT id, balance FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $user_search, $user_search);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        // User found, update balance
        $new_balance = $user['balance'] + $balance_amount;
        $update_stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_balance, $user['id']);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            echo "Balance added successfully!";
        } else {
            echo "Error adding balance!";
        }
        $update_stmt->close();
    } else {
        echo "User not found!";
    }

    $stmt->close();
}

$conn->close();
?>
