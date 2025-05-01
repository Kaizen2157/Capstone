<?php
session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "parking_system";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch current cost per hour from the database
$current_cost = 40; // default
$cost_result = $conn->query("SELECT parking_cost FROM settings LIMIT 1");
if ($cost_result && $cost_result->num_rows > 0) {
    $row = $cost_result->fetch_assoc();
    $current_cost = $row['parking_cost'];
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Clear session data and cookies
    session_unset();
    session_destroy();

    // Clear session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    header("Location: adminlog.html");
    exit();
}

// Prevent caching
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Cache-Control: must-revalidate"); // 👈 important to revalidate
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="shortcut icon" href="logo-removebg-preview.png" type="image/x-icon">
    <link rel="stylesheet" href="dashboard-admin.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#">Dashboard Overview</a></li>
                    <li><a href="#">Manage Users</a></li>
                    <li><a href="#">Manage Reservations</a></li>
                    <li><a href="#">Slot Cost Management</a></li>
                    <li><a href="#">Add Balance</a></li>
                    <li><a href="#">System Analytics</a></li>
                    <li><a href="logout-admin.php" id="logout-btn">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-overview">
                <h1>Welcome, Admin</h1>
                <p>Overview of your parking system</p>
                <div class="quick-stats">
                    <div class="stat-box">
                        <h3>Active Users</h3>
                        <p id="active-users">120</p> <!-- Dynamic content -->
                    </div>
                    <div class="stat-box">
                        <h3>Total Reservations</h3>
                        <p id="total-reservations">350</p> <!-- Dynamic content -->
                    </div>
                    <div class="stat-box">
                        <h3>Total Earnings</h3>
                        <p id="total-earnings">₱14,000</p> <!-- Dynamic content -->
                    </div>
                </div>
            </div>

            <!-- Slot Cost Management -->
            <div class="manage-slot-cost">
                <h2>Change Parking Slot Cost</h2>
                <form id="costForm" method="POST" action="update-cost.php">
                <label for="cost">New Slot Cost (PHP):</label>
                <input type="number" name="cost" id="cost" value="<?php echo $current_cost; ?>" required>
                <button type="submit">Update Cost</button>
            </form>
                <div id="popup-msg" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #4CAF50; color: white; padding: 10px 20px; border-radius: 8px;">
                </div>
            </div>


            <!-- Add Balance to User Account -->
            <div class="add-balance">
                <h2>Add Balance to User Account</h2>
                <form method="POST" id="user-search-form" action="search-user.php">
                    <label for="user-search">Search for User (by username or email):</label>
                    <input type="text" name="user_search" id="user-search" autocomplete="off" placeholder="Search user by email..." required>
                    <button type="submit">Search</button>
                    <p id="user-not-found-message" style="color: red; display: none;">User not found.</p>
                </form>

                <!-- Modal Popup for Add Balance -->
                <div id="balance-modal" class="modal" style="display:none;">
                    <div class="modal-content">
                        <span class="close-btn" style="cursor: pointer; margin: 20px 0;">&times;</span>
                        <h2>Add Balance to User</h2>
                        <form id="add-balance-form">
                            <p id="user-info"></p> <!-- user name and email will be inserted here -->
                            <input type="hidden" name="user_id" id="user-id">
                            <label for="balance-amount">Amount to Add (₱):</label>
                            <input type="number" name="balance_amount" id="balance-amount" step="0.01" required>
                            <button type="submit">Add Balance</button>
                        </form>
                    </div>
                </div>

            </div>

            <!-- System Analytics -->
            <div class="system-analytics">
                <h2>System Analytics</h2>
                <div class="analytics-stats">
                    <div class="stat-box">
                        <h3>Users Logged In Today</h3>
                        <p id="users-logged-in-today">45</p> <!-- Dynamic content -->
                    </div>
                    <div class="stat-box">
                        <h3>Reservations Made Today</h3>
                        <p id="reservations-today">120</p> <!-- Dynamic content -->
                    </div>
                    <div class="stat-box">
                        <h3>Total Earnings Today</h3>
                        <p id="earnings-today">₱4,800</p> <!-- Dynamic content -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    

    <div id="logout-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center; flex-direction:column;">
        <div style="background:white; padding:20px 40px; border-radius:10px; text-align:center;">
            <div class="spinner" style="margin-bottom:10px; display: flex; align-items: center; justify-content: center;">
                <div style="border: 5px solid #f3f3f3; border-top: 5px solid #3498db; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
            </div>
            <p style="font-size:18px;">Logging you out...</p>
        </div>
    </div>


    <canvas id="earningsChart"></canvas>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="dashboard-script.js"></script>

    <script>

// Handle Search Form Submit
document.getElementById("user-search-form").addEventListener("submit", function(e) {
    e.preventDefault();

    const searchInput = document.getElementById("user-search").value.trim();
    const userNotFoundMsg = document.getElementById("user-not-found-message");

    fetch('search-user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ user_search: searchInput })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            userNotFoundMsg.style.display = "none";

            // Fill modal with user info
            document.getElementById("user-info").innerText = `${data.user.name} (${data.user.email})`;
            document.getElementById("user-id").value = data.user.id;

            // Show modal
            document.getElementById("balance-modal").style.display = "block";
        } else {
            userNotFoundMsg.style.display = "block";
        }
    })
    .catch(error => {
        console.error("Error:", error);
    });
});

// Close modal
document.querySelector(".close-btn").addEventListener("click", function() {
    document.getElementById("balance-modal").style.display = "none";
});

// Handle Add Balance Form
document.getElementById("add-balance-form").addEventListener("submit", function(e) {
    e.preventDefault();

    const userId = document.getElementById("user-id").value;
    const amount = document.getElementById("balance-amount").value;

    fetch('add-balance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ user_id: userId, balance_amount: amount })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            document.getElementById("balance-modal").style.display = "none";
        }
    })
    .catch(error => {
        console.error("Error:", error);
    });
});

</script>

<div id="toast" style="
  position: fixed;
  bottom: 30px;
  right: 30px;
  background: #4BB543;
  color: white;
  padding: 15px 20px;
  border-radius: 8px;
  font-size: 16px;
  box-shadow: 0 5px 10px rgba(0,0,0,0.2);
  opacity: 0;
  pointer-events: none;
  transition: all 0.5s ease;
  z-index: 9999;
">
  Balance successfully added!
</div>

<!-- User Not Found Error Message -->
<div id="user-not-found-message" style="
  position: fixed;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #e74c3c;
  color: white;
  padding: 10px 20px;
  border-radius: 5px;
  font-size: 16px;
  display: none;
  z-index: 9999;
">
    User not found. Please try again.
</div>

<script>
document.getElementById('costForm').addEventListener('submit', function(e) {
    e.preventDefault(); // prevent default form submission

    const cost = document.getElementById('cost').value;

    fetch('update-cost.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cost=' + encodeURIComponent(cost)
    })
    .then(response => response.json())
    .then(data => {
        const popup = document.getElementById('popup-msg');
        popup.textContent = data.message;
        popup.style.backgroundColor = data.success ? '#4CAF50' : '#f44336'; // green or red
        popup.style.display = 'block';

        setTimeout(() => {
            popup.style.display = 'none';
        }, 3000);
    })
    .catch(error => {
        console.error('Error:', error);
    });
});
</script>



</body>
</html>