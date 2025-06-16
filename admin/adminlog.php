<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if admin is already logged in, redirect to admin dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
    header('Location: dashboard-admin.php');
    exit();
}

// Handle the login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Run this once to update your existing admin passwords
$update_stmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = 'testadmin1'");
$hashed_password = password_hash('testadmin1', PASSWORD_DEFAULT);
$update_stmt->bind_param("s", $hashed_password);
$update_stmt->execute();

$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);

    // Execute the statement
    if (!$stmt->execute()) {
        // Log error and redirect
        error_log("Login query failed: " . $stmt->error);
        header('Location: adminlog.html?error=database');
        exit();
    }

    // Get the result
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No admin found with this username
        header('Location: adminlog.html?error=invalid');
        exit();
    }

    $admin = $result->fetch_assoc();

    // Verify password against the hashed version in database
    if (password_verify($password, $admin['password'])) {
        // Login successful - set session variables
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        
        header('Location: dashboard-admin.php');
        exit();
    } else {
        // Password doesn't match
        header('Location: adminlog.html?error=invalid');
        exit();
    }
}

// Close the connection
$conn->close();

// If not POST request or other cases, redirect to login
header('Location: adminlog.html');
exit();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

    <div class="error-message">
    <?php 
    if (isset($_GET['error'])) {
        switch($_GET['error']) {
            case 'invalid': echo "Invalid username or password"; break;
            case 'database': echo "Database error occurred"; break;
            default: echo "Login required";
        }
    }
    ?>
</div>
    
</body>
</html>