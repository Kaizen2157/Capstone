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

?>