<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'valid' => isset($_SESSION['user_id'])
]);
exit;
?>