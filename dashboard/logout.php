<?php
session_start();
session_destroy();
header("Location: /capstone/frontend/backups/login/login.html");
exit;
?>