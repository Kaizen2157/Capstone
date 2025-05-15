<?php
// include 'config.php';
// session_start();
// if (!isset($_SESSION['admin_id'])) {
//     header("Location: admin-login.php");
//     exit();
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Parking System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
        }
        .sidebar .nav-link.active {
            background-color: #495057;
        }
        .content-section {
            display: none;
        }
        .content-section.active {
            display: block;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-3">
            <h4 class="text-white mb-4">Admin Panel</h4>
            <nav class="nav flex-column">
                <a class="nav-link active" href="#" data-section="slots">Manage Slots</a>
                <a class="nav-link" href="#" data-section="cost">Parking Slot Cost</a>
                <a class="nav-link" href="#" data-section="users">Manage Users</a>
                <a class="nav-link" href="#" data-section="analytics">System Analytics</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <!-- Manage Slots -->
            <div id="section-slots" class="content-section active">
                <h3>Manage Parking Slots</h3>
                <div id="slotTable" class="mt-3"></div>
            </div>

            <!-- Parking Cost -->
            <div id="section-cost" class="content-section">
                <h3>Update Parking Slot Cost</h3>
                <form action="update-cost.php" method="POST" class="mt-3">
                    <div class="mb-3">
                        <label for="newCost" class="form-label">New Cost (PHP)</label>
                        <input type="number" class="form-control" id="newCost" name="newCost" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Cost</button>
                </form>
            </div>

            <!-- Manage Users -->
            <div id="section-users" class="content-section">
                <h3>Manage Users</h3>
                <input type="text" id="searchUser" placeholder="Search by Car Plate" class="form-control my-3">
                <div id="userTable"></div>
            </div>

            <!-- System Analytics -->
            <div id="section-analytics" class="content-section">
                <h3>System Analytics</h3>
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <canvas id="earningsChart"></canvas>
                    </div>
                    <div class="col-md-6 mb-4">
                        <canvas id="userChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Balance Modal -->
<div class="modal fade" id="addBalanceModal" tabindex="-1" aria-labelledby="addBalanceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addBalanceForm" action="add-balance.php" method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addBalanceModalLabel">Add Balance</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="userId" id="userIdInput">
        <div class="mb-3">
          <label for="amount" class="form-label">Amount (PHP)</label>
          <input type="number" class="form-control" name="amount" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Add</button>
      </div>
    </form>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        const section = link.getAttribute('data-section');
        document.querySelectorAll('.content-section').forEach(sec => {
            sec.classList.remove('active');
        });
        document.getElementById('section-' + section).classList.add('active');
    });
});

// Load slots (simulate AJAX)
document.addEventListener('DOMContentLoaded', () => {
    fetch('../dashboard/get-slot-status.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById('slotTable').innerHTML = data;
        });

    fetch('../dashboard/get-user.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById('userTable').innerHTML = data;
        });

    // Dummy chart data for earnings
    const ctxEarnings = document.getElementById('earningsChart').getContext('2d');
    new Chart(ctxEarnings, {
        type: 'bar',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            datasets: [{
                label: 'Daily Earnings',
                data: [300, 500, 400, 600, 700],
                backgroundColor: '#0d6efd'
            }]
        }
    });

    // Dummy chart data for users
    const ctxUsers = document.getElementById('userChart').getContext('2d');
    new Chart(ctxUsers, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
            datasets: [{
                label: 'Users Joined',
                data: [5, 12, 8, 15],
                borderColor: '#198754',
                fill: false
            }]
        }
    });
});

// Open add balance modal
function openAddBalanceModal(userId) {
    document.getElementById('userIdInput').value = userId;
    new bootstrap.Modal(document.getElementById('addBalanceModal')).show();
}
</script>
</body>
</html>
