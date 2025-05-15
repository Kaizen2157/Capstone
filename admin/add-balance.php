<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['balance_amount'])) {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['balance_amount']);

    if ($amount > 0) {
        // First get current balance
        $balance_query = $conn->prepare("SELECT balance FROM users WHERE id = ?");
        $balance_query->bind_param("i", $user_id);
        $balance_query->execute();
        $balance_result = $balance_query->get_result();
        $current_balance = $balance_result->fetch_assoc()['balance'];
        $balance_query->close();
        
        $new_balance = $current_balance + $amount;

        // Update user balance
        $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $stmt->bind_param("di", $new_balance, $user_id);

        if ($stmt->execute()) {
            // ✅ NEW: Record the transaction
            $transaction_stmt = $conn->prepare("INSERT INTO transactions 
                (user_id, amount, type, description, balance_after) 
                VALUES (?, ?, 'topup', ?, ?)");
            $description = "Balance top-up";
            $transaction_stmt->bind_param("idsd", 
                $user_id, 
                $amount,
                $description,
                $new_balance);
                
            if ($transaction_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Balance added successfully.']);
            } else {
                error_log("Failed to record transaction: " . $transaction_stmt->error);
                echo json_encode(['success' => true, 'message' => 'Balance added (transaction not recorded).']);
            }
            $transaction_stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add balance.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid amount.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
