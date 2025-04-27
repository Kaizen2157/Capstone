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
    header('Location: dashboard-admin.html');  // Redirect to admin dashboard
    exit();
}


// Handle the login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare a SQL statement to prevent SQL injection (search by username only)
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);  // "s" means the parameter is a string

    // Execute the statement
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();  // Fetch the admin data

    // Check if admin exists and verify password (without hashing for now)
    if ($admin && $password == $admin['password']) {  // Compare the password directly (no hash)
        // If password is correct, start a session and redirect
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $admin['id']; // Store admin ID
        $_SESSION['admin_username'] = $admin['username'];

        header('Location: dashboard-admin.html');
        exit();
    } else {
        // Invalid login
        $error = "Invalid username or password!";
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>
