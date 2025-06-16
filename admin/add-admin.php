<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

require_once 'admin-functions.php';

if (!canCreateAdmins($conn, $_SESSION['admin_id'])) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'You are not authorized to create admins']);
    exit;
}

$response = ['success' => false];

$username = trim($_POST['username']);
$password = $_POST['password'];
$is_superadmin = isset($_POST['is_superadmin']) ? 1 : 0;

// Validate input
if (empty($username) || empty($password)) {
    $response['message'] = 'Username and password are required';
    echo json_encode($response);
    exit;
}

// Check if username exists
$stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $response['message'] = 'Username already exists';
    echo json_encode($response);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new admin
$stmt = $conn->prepare("INSERT INTO admins (username, password, created_by, is_superadmin) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssii", $username, $hashed_password, $_SESSION['admin_id'], $is_superadmin);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Admin created successfully';
} else {
    $response['message'] = 'Error creating admin: ' . $conn->error;
}

header('Content-Type: application/json');
echo json_encode($response);
?>