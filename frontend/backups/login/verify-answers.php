<?php
// Set headers FIRST to ensure proper content type
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't output errors to response

try {
    // Include database connection
    require_once '../../../db_connect.php';
    
    // Get and validate input
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception('No input data received');
    }
    
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg());
    }
    
    $email = $data['email'] ?? '';
    $answers = $data['answers'] ?? [];
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (count($answers) !== 2) {
        throw new Exception('Two answers are required');
    }

    // Get user data
    $stmt = $conn->prepare("SELECT question1, answer1_hash, question2, answer2_hash FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception('Database preparation failed: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    $allCorrect = true;
    
    // Verify answers against stored hashes
    foreach ($answers as $answerData) {
        $question = $answerData['question'] ?? '';
        $userAnswer = strtolower(trim($answerData['answer'] ?? ''));
        
        // Determine which answer to check
        if (strpos($user['question1'], $question) !== false || strpos($question, $user['question1']) !== false) {
            $correctHash = $user['answer1_hash'];
        } elseif (strpos($user['question2'], $question) !== false || strpos($question, $user['question2']) !== false) {
            $correctHash = $user['answer2_hash'];
        } else {
            $allCorrect = false;
            continue;
        }
        
        if (!password_verify($userAnswer, $correctHash)) {
            $allCorrect = false;
        }
    }
    
    if ($allCorrect) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        
        $updateStmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token, $expires, $email);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to generate reset token');
        }
        
        echo json_encode([
            'success' => true,
            'token' => $token
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'One or more answers are incorrect'
        ]);
    }
    
} catch (Exception $e) {
    // Ensure we only output JSON, even for errors
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>