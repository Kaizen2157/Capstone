<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cost'])) {
    $new_cost = floatval($_POST['cost']);  // Use float instead of int for decimal cost.
    
    if ($new_cost > 0) {
        $stmt = $conn->prepare("UPDATE settings SET parking_cost = ? WHERE id = 1");
        $stmt->bind_param("d", $new_cost);  // "d" for double (decimal)
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cost updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update cost.']);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid cost value.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
?>
