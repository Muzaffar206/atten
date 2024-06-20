<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata'); // Set default timezone to IST

$conn = new mysqli('localhost', 'root', '', 'attendance_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$mode = $_POST['mode'];
$timestamp = date('Y-m-d H:i:s'); // IST timezone timestamp

if ($mode === 'Office') {
    $data1 = $_POST['data1']; // QR Code message
    $sql = "INSERT INTO attendance (user_id, mode, data, timestamp) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $mode, $data1, $timestamp);
} else if ($mode === 'Outdoor') {
    $latitude = $_POST['data1'];
    $longitude = $_POST['data2'];
    $sql = "INSERT INTO attendance (user_id, mode, latitude, longitude, timestamp) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isdds", $user_id, $mode, $latitude, $longitude, $timestamp);
}

if ($stmt->execute()) {
    echo "Attendance logged successfully.";
} else {
    echo "Error logging attendance: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
