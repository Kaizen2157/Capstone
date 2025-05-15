<?php
header('Content-Type: application/json');

try {
    require_once '../../../db_connect.php';
    
    // Get raw POST data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        throw new Exception('No input data received');
    }
    
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    $email = $data['email'] ?? '';
    if (empty($email)) {
        throw new Exception('Email is required');
    }

    $stmt = $conn->prepare("SELECT question1, question2 FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception('Database query failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'questions' => [
                ['question' => $user['question1'], 'field' => 'answer1'],
                ['question' => $user['question2'], 'field' => 'answer2']
            ],
            'email' => $email
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Email not found in our system'
        ]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>