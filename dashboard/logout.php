<?php
session_start(); // Start the session to access session variables

// Destroy the session to log out the admin
session_destroy();

// Redirect to login page after logout
header('Location: ../frontend/backups/login/login.html?logout=success');
exit;
?>
