<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// admin-functions.php

function getActiveUsersCount($conn) {
    // Temporary solution until last_login is added
    // Count all users for now (replace this later)
    $query = "SELECT COUNT(*) FROM users";
    $result = $conn->query($query);
    return $result ? $result->fetch_row()[0] : 0;
    
    /* 
    // This will be the final version after adding last_login:
    $query = "SELECT COUNT(*) FROM users WHERE last_login >= NOW() - INTERVAL 30 MINUTE";
    $result = $conn->query($query);
    return $result ? $result->fetch_row()[0] : 0;
    */
}

function getCurrentReservations($conn) {
    $query = "SELECT COUNT(*) FROM reservations WHERE 
              CONCAT(start_date, ' ', start_time) <= NOW() AND 
              CONCAT(start_date, ' ', end_time) >= NOW() AND 
              status = 'reserved'";
    $result = $conn->query($query);
    return $result ? $result->fetch_row()[0] : 0;
}

function getTodaysEarnings($conn) {
    $query = "SELECT SUM(total_cost) FROM reservations WHERE 
              DATE(start_date) = CURDATE() AND 
              status IN ('reserved', 'done')";
    $result = $conn->query($query);
    return $result ? ($result->fetch_row()[0] ?? 0) : 0;
}

function getTodaysReservations($conn) {
    $query = "SELECT COUNT(*) FROM reservations WHERE DATE(start_date) = CURDATE()";
    $result = $conn->query($query);
    return $result ? $result->fetch_row()[0] : 0;
}

function getAvailableSlots($conn) {
    $query = "SELECT COUNT(*) FROM slots WHERE status = 'available'";
    $result = $conn->query($query);
    return $result ? $result->fetch_row()[0] : 0;
}

function getTotalSlots($conn) {
    $query = "SELECT COUNT(*) FROM slots";
    $result = $conn->query($query);
    return $result ? $result->fetch_row()[0] : 0;
}

function getReservedSlots($conn) {
    $query = "SELECT COUNT(*) FROM slots WHERE status = 'reserved'";
    $result = $conn->query($query);
    return $result ? $result->fetch_row()[0] : 0;
}

// Add these to your existing admin-functions.php

function getPeakHours($conn) {
    $query = "SELECT HOUR(start_time) as hour, COUNT(*) as count 
              FROM reservations 
              GROUP BY HOUR(start_time) 
              ORDER BY count DESC LIMIT 1";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        if ($data && isset($data['hour'])) { // Added check
            $hour = (int)$data['hour'];
            
            // Convert to 12-hour format with AM/PM
            $period = $hour >= 12 ? 'PM' : 'AM';
            $displayHour = $hour % 12;
            $displayHour = $displayHour ? $displayHour : 12;
            
            $nextHour = ($hour + 1) % 24;
            $nextPeriod = $nextHour >= 12 ? 'PM' : 'AM';
            $nextDisplayHour = $nextHour % 12;
            $nextDisplayHour = $nextDisplayHour ? $nextDisplayHour : 12;
            
            return sprintf(
                "%02d:00 %s - %02d:00 %s", 
                $displayHour, 
                $period, 
                $nextDisplayHour, 
                $nextPeriod
            );
        }
    }
    
    return "No data available";
}

function getAverageDuration($conn) {
    $query = "SELECT AVG(duration_hours) FROM reservations";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_row();
        return number_format($row[0] ?? 0, 1);
    }
    
    return number_format(0, 1);
}

function getUtilizationRate($conn) {
    $query = "SELECT 
                (COUNT(*) / (SELECT COUNT(*) FROM slots) * 100) as rate
              FROM reservations 
              WHERE DATE(start_date) = CURDATE()";
    $result = $conn->query($query);
    
    if ($result) {
        $row = $result->fetch_row();
        return number_format($row[0] ?? 0, 1);
    }
    
    return number_format(0, 1);
}

function isSuperAdmin($conn, $admin_id) {
    $stmt = $conn->prepare("SELECT is_superadmin FROM admins WHERE id = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $admin_id);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        return false;
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        error_log("Get result failed: " . $stmt->error);
        return false;
    }
    
    $row = $result->fetch_assoc();
    return ($row && isset($row['is_superadmin'])) ? $row['is_superadmin'] == 1 : false;
}

function validateAdminSession($conn) {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    $stmt = $conn->prepare("SELECT id FROM admins WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows > 0;
}

function canCreateAdmins($conn, $admin_id) {
    // Only superadmins can create other admins in this implementation
    return isSuperAdmin($conn, $admin_id);
}

function getEarningsChartData($conn, $range = 'today') {
    switch($range) {
        case 'week':
            $query = "SELECT DAYNAME(start_date) as day, COUNT(*) as count 
                      FROM reservations 
                      WHERE YEARWEEK(start_date) = YEARWEEK(CURDATE()) 
                      GROUP BY DAYOFWEEK(start_date) 
                      ORDER BY DAYOFWEEK(start_date)";
            break;
        case 'month':
            $query = "SELECT DAY(start_date) as day, COUNT(*) as count 
                      FROM reservations 
                      WHERE MONTH(start_date) = MONTH(CURDATE()) 
                      GROUP BY DAY(start_date) 
                      ORDER BY DAY(start_date)";
            break;
        case 'year':
            $query = "SELECT MONTHNAME(start_date) as month, COUNT(*) as count 
                      FROM reservations 
                      WHERE YEAR(start_date) = YEAR(CURDATE()) 
                      GROUP BY MONTH(start_date) 
                      ORDER BY MONTH(start_date)";
            break;
        case 'all':
            $query = "SELECT YEAR(start_date) as year, COUNT(*) as count 
                      FROM reservations 
                      GROUP BY YEAR(start_date) 
                      ORDER BY YEAR(start_date)";
            break;
        default: // today
            $query = "SELECT HOUR(start_time) as hour, COUNT(*) as count 
                      FROM reservations 
                      WHERE DATE(start_date) = CURDATE() 
                      GROUP BY HOUR(start_time) 
                      ORDER BY HOUR(start_time)";
    }

    $result = $conn->query($query);
    $data = ['labels' => [], 'values' => []];

    while($row = $result->fetch_assoc()) {
        $data['labels'][] = $row[array_key_first($row)];
        $data['values'][] = $row['count'];
    }

    return $data;
}

// Add these functions to your admin-functions.php

function getEarningsByRange($conn, $range = 'today') {
    $query = "";
    
    switch($range) {
        case 'week':
            $query = "SELECT 
                        DATE(start_date) as date,
                        SUM(total_cost) as earnings,
                        COUNT(*) as bookings
                      FROM reservations 
                      WHERE YEARWEEK(start_date) = YEARWEEK(CURDATE()) 
                      AND status = 'done'
                      GROUP BY DATE(start_date) 
                      ORDER BY DATE(start_date)";
            break;
        case 'month':
            $query = "SELECT 
                        DATE(start_date) as date,
                        SUM(total_cost) as earnings,
                        COUNT(*) as bookings
                      FROM reservations 
                      WHERE MONTH(start_date) = MONTH(CURDATE()) 
                      AND status = 'done'
                      GROUP BY DATE(start_date) 
                      ORDER BY DATE(start_date)";
            break;
        case 'year':
            $query = "SELECT 
                        MONTHNAME(start_date) as month,
                        SUM(total_cost) as earnings,
                        COUNT(*) as bookings
                      FROM reservations 
                      WHERE YEAR(start_date) = YEAR(CURDATE()) 
                      AND status = 'done'
                      GROUP BY MONTH(start_date) 
                      ORDER BY MONTH(start_date)";
            break;
        case 'all':
            $query = "SELECT 
                        YEAR(start_date) as year,
                        SUM(total_cost) as earnings,
                        COUNT(*) as bookings
                      FROM reservations 
                      WHERE status = 'done'
                      GROUP BY YEAR(start_date) 
                      ORDER BY YEAR(start_date)";
            break;
        default: // today
            $query = "SELECT 
                        HOUR(start_time) as hour,
                        SUM(total_cost) as earnings,
                        COUNT(*) as bookings
                      FROM reservations 
                      WHERE DATE(start_date) = CURDATE() 
                      AND status = 'done'
                      GROUP BY HOUR(start_time) 
                      ORDER BY HOUR(start_time)";
    }

    $result = $conn->query($query);
    $data = [];

    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

function getTotalEarningsByRange($conn, $range = 'today') {
    $query = "";
    
    switch($range) {
        case 'week':
            $query = "SELECT SUM(total_cost) as total 
                      FROM reservations 
                      WHERE YEARWEEK(start_date) = YEARWEEK(CURDATE()) 
                      AND status = 'done'";
            break;
        case 'month':
            $query = "SELECT SUM(total_cost) as total 
                      FROM reservations 
                      WHERE MONTH(start_date) = MONTH(CURDATE()) 
                      AND status = 'done'";
            break;
        case 'year':
            $query = "SELECT SUM(total_cost) as total 
                      FROM reservations 
                      WHERE YEAR(start_date) = YEAR(CURDATE()) 
                      AND status = 'done'";
            break;
        case 'all':
            $query = "SELECT SUM(total_cost) as total 
                      FROM reservations 
                      WHERE status = 'done'";
            break;
        default: // today
            $query = "SELECT SUM(total_cost) as total 
                      FROM reservations 
                      WHERE DATE(start_date) = CURDATE() 
                      AND status = 'done'";
    }

    $result = $conn->query($query);
    return $result->fetch_row()[0] ?? 0;
}

?>