<?php
session_start();

$_SESSION = array();

session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Log Out | CSJP E-Library Portal</title>
    <link rel="icon" href="css/images/logo.png">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        .logout-container {
            background: rgba(10, 12, 140, 0.85);
            padding: 40px;
            border-radius: 5px;
            color: white;
            width: 600px;
            text-align: center;
            position: relative;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
            margin-top: 80px;
        }

        .logout-container::before {
            content: "";
            position: absolute;
            top: -10px;
            left: 0;
            width: 100%;
            height: 20px;
            background: #f5d900;
            }
        
        .logout-container h1 {
            color: #f5d900;
            margin-bottom: 15px;
        }
        
        .logout-container p {
            margin-bottom: 25px;
            font-size: 18px;
        }
        
        .redirect-message {
            font-style: italic;
        }

        @media (max-width: 768px) {
            .logout-container {
                width: 80%;
                padding: 25px;
            }
        }
        
        @media (max-width: 480px) {
        .header {
            font-size: 14px;
            flex-direction: column;
            text-align: center;
        }
        .background {
            top: 320px;
            width: 100%;
        }
        }

    </style>
</head>
<body>
    <div class="header">
        <p><b>CSJP E-Library Portal</b></p>
        <span>Having issues?<a href="having_issues.php"> Click here</a></span> 
    </div>
<div class="background"></div>
    <div class="logout-container">
        <h1>Thank You!</h1>
        <p>You have been successfully logged out of the CSJP E-Library Portal.</p>
        <p class="redirect-message">Redirecting to login page in 3 seconds...</p>
    </div>

    <script>
        setTimeout(function() {
            window.location.href = "index.php";
        }, 3000);
    </script>
</body>
</html>



<?php

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Unauthorized access!";
    header("Location: index.php");
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'librarian') {
    $_SESSION['error'] = "Unauthorized access!";
    header("Location: index.php");
    exit;
}

?>