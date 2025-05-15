<?php
header('Content-Type: application/json');
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];
$limit = 50; // Increased limit to ensure we see all transactions

// Modified query to explicitly check for all transaction types
$query = "SELECT 
            id,
            amount,
            type,
            reference_id,
            description,
            balance_after,
            created_at,
            CASE 
                WHEN amount < 0 THEN ABS(amount)
                ELSE amount
            END AS display_amount
          FROM transactions 
          WHERE user_id = ? 
          ORDER BY created_at DESC 
          LIMIT ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die(json_encode(['error' => 'Database preparation failed: ' . $conn->error]));
}

$stmt->bind_param("ii", $user_id, $limit);
if (!$stmt->execute()) {
    die(json_encode(['error' => 'Execution failed: ' . $stmt->error]));
}

$result = $stmt->get_result();
$transactions = [];

while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'id' => $row['id'],
        'date' => date('M j, Y g:i A', strtotime($row['created_at'])),
        'type' => $row['type'],
        'amount' => $row['amount'],
        'display_amount' => number_format($row['display_amount'], 2),
        'is_credit' => $row['amount'] > 0,
        'description' => $row['description'],
        'balance_after' => number_format($row['balance_after'], 2),
        'reference_id' => $row['reference_id']
    ];
}

echo json_encode($transactions);
$stmt->close();
$conn->close();
?>