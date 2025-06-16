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
    echo json_encode(['success' => false, 'message' => 'You are not authorized to delete admins']);
    exit;
}

$response = ['success' => false];

$admin_id = intval($_POST['admin_id']);

// Prevent deleting yourself
if ($admin_id == $_SESSION['admin_id']) {
    $response['message'] = 'You cannot delete yourself';
    echo json_encode($response);
    exit;
}

// Check if admin exists
$stmt = $conn->prepare("SELECT id FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows == 0) {
    $response['message'] = 'Admin not found';
    echo json_encode($response);
    exit;
}

// Delete admin
$stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
$stmt->bind_param("i", $admin_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['message'] = 'Admin deleted successfully';
} else {
    $response['message'] = 'Error deleting admin: ' . $conn->error;
}

header('Content-Type: application/json');
echo json_encode($response);
?>