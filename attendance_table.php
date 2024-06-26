<?php
session_start();
include("assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all users
$userSql = "SELECT id, username FROM users";
$userResult = $conn->query($userSql);

$users = [];
if ($userResult->num_rows > 0) {
    while ($userRow = $userResult->fetch_assoc()) {
        $users[] = $userRow;
    }
}

// Initialize arrays to hold attendance counts
$dailyAttendance = [];
$monthlyAttendance = [];
$yearlyAttendance = [];

foreach ($users as $user) {
    $userId = $user['id'];

    // Fetch daily attendance
    $dailySql = "SELECT DATE(scan_time) as date, COUNT(*) as count
                 FROM attendance
                 WHERE user_id = $userId
                 GROUP BY DATE(scan_time)";
    $dailyResult = $conn->query($dailySql);
    while ($row = $dailyResult->fetch_assoc()) {
        $dailyAttendance[$userId][$row['date']] = $row['count'];
    }

    // Fetch monthly attendance
    $monthlySql = "SELECT DATE_FORMAT(scan_time, '%Y-%m') as date, COUNT(*) as count
                   FROM attendance
                   WHERE user_id = $userId
                   GROUP BY DATE_FORMAT(scan_time, '%Y-%m')";
    $monthlyResult = $conn->query($monthlySql);
    while ($row = $monthlyResult->fetch_assoc()) {
        $monthlyAttendance[$userId][$row['date']] = $row['count'];
    }

    // Fetch yearly attendance
    $yearlySql = "SELECT DATE_FORMAT(scan_time, '%Y') as date, COUNT(*) as count
                  FROM attendance
                  WHERE user_id = $userId
                  GROUP BY DATE_FORMAT(scan_time, '%Y')";
    $yearlyResult = $conn->query($yearlySql);
    while ($row = $yearlyResult->fetch_assoc()) {
        $yearlyAttendance[$userId][$row['date']] = $row['count'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>All Users Attendance</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>All Users Attendance</h1>
    <h2>Daily Attendance</h2>
    <table>
        <tr>
            <th>Date</th>
            <?php foreach ($users as $user) { echo "<th>{$user['username']}</th>"; } ?>
        </tr>
        <?php
        // Collect all dates
        $dates = [];
        foreach ($dailyAttendance as $userId => $attendance) {
            foreach ($attendance as $date => $count) {
                $dates[$date] = $date;
            }
        }
        ksort($dates);

        foreach ($dates as $date) {
            echo "<tr><td>$date</td>";
            foreach ($users as $user) {
                $userId = $user['id'];
                echo "<td>" . ($dailyAttendance[$userId][$date] ?? 0) . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Monthly Attendance</h2>
    <table>
        <tr>
            <th>Month</th>
            <?php foreach ($users as $user) { echo "<th>{$user['username']}</th>"; } ?>
        </tr>
        <?php
        // Collect all months
        $months = [];
        foreach ($monthlyAttendance as $userId => $attendance) {
            foreach ($attendance as $date => $count) {
                $months[$date] = $date;
            }
        }
        ksort($months);

        foreach ($months as $date) {
            echo "<tr><td>$date</td>";
            foreach ($users as $user) {
                $userId = $user['id'];
                echo "<td>" . ($monthlyAttendance[$userId][$date] ?? 0) . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>

    <h2>Yearly Attendance</h2>
    <table>
        <tr>
            <th>Year</th>
            <?php foreach ($users as $user) { echo "<th>{$user['username']}</th>"; } ?>
        </tr>
        <?php
        // Collect all years
        $years = [];
        foreach ($yearlyAttendance as $userId => $attendance) {
            foreach ($attendance as $date => $count) {
                $years[$date] = $date;
            }
        }
        ksort($years);

        foreach ($years as $date) {
            echo "<tr><td>$date</td>";
            foreach ($users as $user) {
                $userId = $user['id'];
                echo "<td>" . ($yearlyAttendance[$userId][$date] ?? 0) . "</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
    <button onclick="document.location='logout.php'">Logout</button>
</body>
</html>
