<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false]);
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false]);
    exit();
}

if (isset($_POST['user_search'])) {
    $user_search = $_POST['user_search'];

    $stmt = $conn->prepare("SELECT id, first_name, last_name, email, balance FROM users WHERE email = ?");
    $stmt->bind_param("s", $user_search);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        echo json_encode([
            "success" => true,
            "user" => [
                "id" => $user['id'],
                "name" => $user['first_name'] . ' ' . $user['last_name'],
                "email" => $user['email']
            ]
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
}
?>
