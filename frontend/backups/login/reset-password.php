<?php
header('Content-Type: application/json');
require_once '../../../db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? ''; // Changed from username to email
$newPassword = $data['newPassword'] ?? '';
$token = $data['token'] ?? '';

// First verify the token is valid
$stmt = $conn->prepare("SELECT reset_expires FROM users WHERE email = ? AND reset_token = ?");
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
    exit;
}

$user = $result->fetch_assoc();
if (strtotime($user['reset_expires']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Token has expired']);
    exit;
}

// Validate password strength
if (strlen($newPassword) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
    exit;
}

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
$stmt->bind_param("ss", $hashedPassword, $email);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Password reset failed']);
}
?>