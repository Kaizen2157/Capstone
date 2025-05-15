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
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

// Get security questions if provided
$question1 = isset($_POST['question1']) ? $_POST['question1'] : null;
$answer1 = isset($_POST['answer1']) ? strtolower(trim($_POST['answer1'])) : null;
$answer1_hash = $answer1 ? password_hash($answer1, PASSWORD_DEFAULT) : null;

$question2 = isset($_POST['question2']) ? $_POST['question2'] : null;
$answer2 = isset($_POST['answer2']) ? strtolower(trim($_POST['answer2'])) : null;
$answer2_hash = $answer2 ? password_hash($answer2, PASSWORD_DEFAULT) : null;

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
$sql = "INSERT INTO users (first_name, last_name, email, password, question1, answer1_hash, question2, answer2_hash) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssss", $first_name, $last_name, $email, $hashed_password, $question1, $answer1_hash, $question2, $answer2_hash);

if ($stmt->execute()) {
    header("Location: ../login/login.html?registered=true");
    exit();
} else {
    echo "Error: " . $stmt->error;
}
?>