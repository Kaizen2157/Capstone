<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only run if form is submitted with user_id and balance_amount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['balance_amount'])) {
    $user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['balance_amount']);

    if ($amount > 0) {
        // Update user balance
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Balance added successfully.']);
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
