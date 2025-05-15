<?php
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
        $hour = (int)$data['hour'];
        
        // Convert to 12-hour format with AM/PM
        $period = $hour >= 12 ? 'PM' : 'AM';
        $displayHour = $hour % 12;
        $displayHour = $displayHour ? $displayHour : 12; // Handle midnight (0 becomes 12 AM)
        
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
    
    return "No data available";
}

function getAverageDuration($conn) {
    $query = "SELECT AVG(duration_hours) FROM reservations";
    $result = $conn->query($query);
    return number_format($result->fetch_row()[0] ?? 0, 1);
}

function getUtilizationRate($conn) {
    $query = "SELECT 
                (COUNT(*) / (SELECT COUNT(*) FROM slots) * 100) as rate
              FROM reservations 
              WHERE DATE(start_date) = CURDATE()";
    $result = $conn->query($query);
    return number_format($result->fetch_row()[0] ?? 0, 1);
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

?>