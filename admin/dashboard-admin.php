<?php

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: adminlog.php'); // Redirect to login page if not logged in
    exit();
}


?>