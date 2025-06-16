<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

$response = ['success' => false, 'admins' => []];

try {
    $query = "SELECT a.id, a.username, a.created_at, a.is_superadmin, 
                     b.username as created_by_name
              FROM admins a
              LEFT JOIN admins b ON a.created_by = b.id
              ORDER BY a.created_at DESC";
    
    $result = $conn->query($query);
    
    if ($result) {
        $response['success'] = true;
        while ($row = $result->fetch_assoc()) {
            $response['admins'][] = $row;
        }
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>