<?php
header('Content-Type: application/json'); // Ensure JSON response

session_start();

require_once '../db_connect.php';

// if (!isset($_SESSION['user_id'])) {
//     // Clear any remaining session data
//     session_unset();
//     session_destroy();
    
//     // Redirect to login
//     header('Location: ../frontend/backups/login/login.html?session_expired=1');
//     exit;
// }

try {
    // Using prepared statement with MySQLi
    $stmt = $conn->prepare("SELECT first_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode([
            'name' => $user['first_name'] ?? 'User',
            'error' => false
        ]);
    } else {
        echo json_encode([
            'name' => 'User',
            'error' => 'User not found in database'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'name' => 'User',
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>