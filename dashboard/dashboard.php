<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['id']) || $_SESSION['id'] !== true) {
    // Clear session data and cookies
    session_unset();
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    header("Location: ../frontend/backups/login/login.html");
    exit();
}

$user_id = $_SESSION['user_id']; // Make sure this is set on login

$balance = 0.00;
$sql = "SELECT balance FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($balance);
$stmt->fetch();
$stmt->close();

?>