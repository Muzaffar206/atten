<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'attendance_system');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT 
            users.id AS user_id, 
            users.employer_id,
            users.username, 
            users.full_name,
            attendance.mode, 
            attendance.latitude, 
            attendance.longitude,
            attendance.in_time,
            attendance.out_time, 
            attendance.selfie 
        FROM attendance 
        JOIN users ON attendance.user_id = users.id
        ORDER BY attendance.id DESC";
        

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Report</title>
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
        img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <h1>Attendance Report</h1>
    <table class="table table-success table-striped-columns">
        <tr>
            <th>User ID</th>
            <th>Emp id</th>
            <th>Username</th>
            <th>Fullname</th>
            <th>Mode</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>In time</th>
            <th>Out time</th>
            <th>Selfie</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['user_id'] . "</td>
                        <td>" . $row['employer_id'] . "</td>
                        <td>" . $row['username'] . "</td>
                        <td>" . $row['full_name'] . "</td>
                        <td>" . $row['mode'] . "</td>
                        <td>" . ($row['latitude'] ?? 'N/A') . "</td>
                        <td>" . ($row['longitude'] ?? 'N/A') . "</td>
                        <td>" . $row['in_time'] . "</td>
                        <td>" . $row['out_time'] . "</td>
                        <td>" . (!empty($row['selfie']) ? '<img src="data:image/jpeg;base64,' . base64_encode($row['selfie']) . '" alt="Selfie">' : 'N/A') . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No records found</td></tr>";
        }
        
