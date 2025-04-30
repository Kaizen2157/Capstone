<?php
session_start(); // Start the session to access session variables

// Destroy the session to log out the admin
session_destroy();

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page after logout
header('Location: ../frontend/backups/login/login.html');
exit;
?>