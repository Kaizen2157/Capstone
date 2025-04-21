<?php
// file_put_contents('debug.log', print_r($_POST, true));

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_system";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Handle POST request for booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure data exists
    if (!isset($_POST['selected_slot'], $_POST['start_time'], $_POST['end_time'], $_POST['total_cost'])) {
        echo json_encode(['success' => false, 'message' => 'Missing booking data.']);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $selected_slots = $_POST['selected_slot']; // comma-separated
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $price = $_POST['total_cost'];
    $receipt_code = 'RCPT-' . strtoupper(uniqid());
    $car_plate = $_POST['car_plate'];


    // Insert reservation for each selected slot
    foreach (explode(',', $selected_slots) as $slot_number) {
        $slot_number = trim($slot_number); // Clean up the slot number
        $stmt = $conn->prepare("INSERT INTO reservations (user_id, car_plate, slot_number, start_time, end_time, price, receipt_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssds", $user_id, $car_plate, $slot_number, $start_time, $end_time, $price, $receipt_code);


        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
            exit();
        }
    }

    echo json_encode(['success' => true, 'message' => 'Reservation saved.']);
    $stmt->close();
    $conn->close();
}

?>
<!-- <script>
  sessionStorage.setItem('username', '<?php echo $_SESSION["username"]; ?>');
  // Optionally, store in localStorage too
  localStorage.setItem('username', '<?php echo $_SESSION["username"]; ?>');
  window.location.href = "home-dashboard.html";
</script> -->