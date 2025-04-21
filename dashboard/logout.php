<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy session cookie too
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time() - 42000, '/');
}

echo "You are now logged out. <a href='/capstone/frontend/backups/login/login.html'>Login again</a>";

session_destroy();
header("Location: /capstone/frontend/backups/login/login.html");
exit;
?>
