<?php
session_start();

// Database connection
include '../../../db_connect.php';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$_SESSION['user_id'] = $user_id_from_database;
$_SESSION['first_name'] = $fetched_first_name;

// Get user input
$email = $_POST['email'];
$password = $_POST['password'];

$_SESSION['user_id'] = $user['id'];

// Prepare statement to fetch user by email
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Now verify the entered password with the hashed one
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'];
        // echo "<script>window.location.href='/./capstone/dashboard/home-dashboard.html';</script>";
        header("Location: /capstone/dashboard/dashboard.html");
        // After successful login validation:
        $update = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update);
        $stmt->bind_param("i", $user_id); // $user_id should be the logged-in user's ID
        $stmt->execute();
    exit();

    } else {
        header("Location: login.html?error=Incorrect+password");
        exit();
    }
} else {
        header("Location: login.html?error=No+account+found+with+that+email");
        exit();
}

$conn->close();
?>