<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Get user input first
$first_name = $_POST['first_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$plain_password = $_POST['password'];
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT); //secure password.

// 2. Check if email already exists before inserting
$check = $conn->prepare("SELECT * FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "<script>alert('Email already registered. Please log in or use a different email.'); window.history.back();</script>";
    exit();
}

// 3. If not existing, insert the new user
$sql = "INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);

if ($stmt->execute()) {
    header("Location: ../login/login.html?registered=true");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$_SESSION['user_id'] = $row['id'];
$_SESSION['user_email'] = $row['email']; 

// echo "<script>
//     sessionStorage.setItem('username', '" . $row['first_name'] . "');
//     window.location.href = 'home-dashboard.html';
// </script>";



?>
