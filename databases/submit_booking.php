

<?php
include_once 'db_connect.php'; // Connect to DB

// Ensure POST data is set
if(isset($_POST['plate'], $_POST['slots'], $_POST['name'], $_POST['contact'], $_POST['email'])) {
    // Get form data
    $name = $_POST['name'];
    $plate = $_POST['plate'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $slots = explode(',', $_POST['slots']); // comma-separated slot IDs

    // Prepare and insert the reservation data
    foreach ($slots as $slot) {
        $slot = intval(trim($slot)); // ensure slot is an integer

        // Insert reservation details into the database
        $sql = "INSERT INTO reservations (name, car_plate, contact, email, slot_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $plate, $contact, $email, $slot);
        $stmt->execute();
    }

    // Close the connection
    $stmt->close();

    // --- Send Confirmation Email ---
    $subject = "Parking Reservation Confirmation";
    $message = "Hi $name,\n\nYour parking slot(s) have been reserved.\nCar Plate: $plate\nSlot(s): " . implode(', ', $slots) . "\n\nThank you!";
    $headers = "From: regenciajoebert21@gmail.com";

    // Use the PHP mail function to send a confirmation email
    mail($email, $subject, $message, $headers);

    // Redirect to success page
    header("Location: ../frontend/success.html");
    exit;
} else {
    echo "Missing form data. Please check your input.";
}

$conn->close();
?>
