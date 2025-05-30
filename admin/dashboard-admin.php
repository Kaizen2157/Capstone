<?php
session_start();

// Include required files
require_once __DIR__ . '/admin-functions.php';  // Path to your functions file
require_once __DIR__ . '/../db_connect.php';    // Path to your database connection

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
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
            <li><a href="#dashboard-overview"><i class='bx bx-book-content'></i>Dashboard Overview</a></li>
            <li><a href="#manage-users"><i class='bx bx-user-pin'></i>Manage Users</a></li>
            <li><a href="#system-analytics"><i class='bx bx-bar-chart-alt-2'></i>System Analytics</a></li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="logout-admin.php" id="logout-btn"><i class='bx bx-log-out'></i>Logout</a>
    </div>
</aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <div class="dashboard-overview" id="dashboard-overview">
                <h1>Welcome, Admin</h1>
                <p>Overview of your parking system</p>
                <div class="quick-stats">
                    <div class="stat-box">
                        <h3>Registered Users</h3>
                        <p id="active-users"><?php echo getActiveUsersCount($conn); ?></p>
                        <small>Total system users</small>
                    </div>
                    <div class="stat-box">
                        <h3>Current Reservations</h3>
                        <p id="current-reservations"><?php echo getCurrentReservations($conn); ?></p>
                        <small>Active right now</small>
                    </div>
                    <div class="stat-box">
                        <h3>Today's Earnings</h3>
                        <p id="today-earnings">₱<?php echo number_format(getTodaysEarnings($conn), 2); ?></p>
                        <small>From successful bookings</small> <?php //echo getTodaysReservations($conn); ?>
                    </div>
                    <div class="stat-box">
                        <h3>Available Slots</h3>
                        <p id="available-slots"><?php echo getAvailableSlots($conn); ?>/<?php echo getTotalSlots($conn); ?></p>
                        <small><?php echo getReservedSlots($conn); ?> reserved</small>
                    </div>
                </div>
            </div>

            <span>
                <h1>Manage Users</h1>
                <p class="smol">Manage user accounts and parking slot costs</p>
            </span>

            <div class="align" id="manage-users">

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

            </div>

        <div class="system-analytics" id="system-analytics">
    <h2>System Analytics</h2>

    <div class="time-filters">
        <button class="time-filter active" data-range="today">Today</button>
        <button class="time-filter" data-range="week">This Week</button>
        <button class="time-filter" data-range="month">This Month</button>
        <button class="time-filter" data-range="year">This Year</button>
        <button class="time-filter" data-range="all">All Time</button>
    </div>

    <div class="analytics-stats">
        <div class="stat-box">
            <h3>Total Earnings</h3>
            <p id="total-earnings">₱0.00</p>
            <small id="earnings-range">Today</small>
        </div>
        <div class="stat-box">
            <h3>Completed Bookings</h3>
            <p id="total-bookings">0</p>
            <small id="bookings-range">Today</small>
        </div>
        <div class="stat-box">
            <h3>Avg. Earnings</h3>
            <p id="avg-earnings">₱0.00</p>
            <small>Per booking</small>
        </div>
    </div>

    <div class="chart-container">
        <canvas id="analyticsChart"></canvas>
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


    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="dashboard-script.js"></script>

    <script>
        // Add this to your dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const timeFilters = document.querySelectorAll('.time-filter');
    let currentRange = 'today';

    // Load initial data
    loadAnalyticsData(currentRange);

    // Add click handlers for time filters
    timeFilters.forEach(filter => {
        filter.addEventListener('click', function() {
            timeFilters.forEach(f => f.classList.remove('active'));
            this.classList.add('active');
            currentRange = this.dataset.range;
            loadAnalyticsData(currentRange);
        });
    });

    function loadAnalyticsData(range) {
        fetch(`get-analytics.php?range=${range}`)
            .then(response => response.json())
            .then(data => {
                // Update earnings display
                document.getElementById('total-earnings').textContent = 
                    `₱${data.totalEarnings.toFixed(2)}`;
                document.getElementById('total-bookings').textContent = 
                    data.totalBookings;
                
                const avgEarnings = data.totalBookings > 0 ? 
                    (data.totalEarnings / data.totalBookings).toFixed(2) : 0;
                document.getElementById('avg-earnings').textContent = 
                    `₱${avgEarnings}`;
                
                // Update range labels
                const rangeText = {
                    'today': 'Today',
                    'week': 'This Week',
                    'month': 'This Month',
                    'year': 'This Year',
                    'all': 'All Time'
                };
                document.getElementById('earnings-range').textContent = rangeText[range];
                document.getElementById('bookings-range').textContent = rangeText[range];
                
                // Update chart (you'll need to implement this based on your chart library)
                updateAnalyticsChart(data.chart);
            })
            .catch(error => {
                console.error('Error loading analytics:', error);
            });
    }

    function updateAnalyticsChart(chartData) {
        // Implement your chart update logic here
        // This depends on which chart library you're using (Chart.js, etc.)
        console.log('Chart data:', chartData);
    }
});
    </script>

    <script>
        // Initialize Chart
const ctx = document.getElementById('analyticsChart').getContext('2d');
let analyticsChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(getEarningsChartData($conn)['labels']); ?>,
        datasets: [{
            label: 'Reservations',
            data: <?php echo json_encode(getEarningsChartData($conn)['values']); ?>,
            backgroundColor: '#19384a',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Reservations'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Time Period'
                }
            }
        }
    }
});

// Time filter functionality
document.querySelectorAll('.time-filter').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.time-filter').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        
        const timeRange = this.dataset.range;
        updateChartData(timeRange);
    });
});

function updateChartData(range = 'today') {
    fetch(`get-analytics-data.php?range=${range}`)
        .then(response => response.json())
        .then(data => {
            analyticsChart.data.labels = data.labels;
            analyticsChart.data.datasets[0].data = data.values;
            analyticsChart.update();
        })
        .catch(error => console.error('Error:', error));
}
    </script>

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
            document.getElementById("user-info").innerHTML = `
            <strong>${data.user.name}</strong> (${data.user.email})<br>
            Current Balance: <strong>₱${parseFloat(data.user.balance).toFixed(2)}</strong>`;

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
    if (data.success) {
        // Hide modal
        document.getElementById("balance-modal").style.display = "none";

        // Update and show toast message
        const toast = document.getElementById("toast");
        toast.textContent = "Balance successfully added!";
        toast.style.opacity = 1;
        toast.style.pointerEvents = "auto";

        setTimeout(() => {
            toast.style.opacity = 0;
            toast.style.pointerEvents = "none";
        }, 3000); // Hide after 3 seconds
    } else {
        alert(data.message); // fallback alert on failure
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