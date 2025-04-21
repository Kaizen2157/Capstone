<?php
include 'database.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate token and expiration
        $token = bin2hex(random_bytes(16)); // Token generation
        $expires = date("U") + 1800; // Expires in 30 minutes
        
        // Insert token into database
        $query = "INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssiss", $email, $token, $expires, $token, $expires);
        $stmt->execute();

        // Send email
        $resetLink = "http://yourwebsite.com/reset-password.php?token=$token";
        $subject = "Password Reset Request";
        $message = "Click the link below to reset your password:\n\n$resetLink";
        $headers = "From: noreply@yourwebsite.com";
        mail($email, $subject, $message, $headers);

        echo "An email has been sent to your email address with the reset link.";
    } else {
        echo "No account found with that email address.";
    }
}
?>
