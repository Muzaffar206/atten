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
$selfie = null;

if (isset($_POST['selfie']) && !empty($_POST['selfie'])) {
    $selfie = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['selfie']));
}


if ($mode === 'Office') {
    $data1 = $_POST['data1']; // QR Code message
    $sql = "INSERT INTO attendance (user_id, mode, data, timestamp, selfie) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $mode, $data1, $timestamp, $selfie);
} else if ($mode === 'Outdoor') {
    $latitude = $_POST['data1'];
    $longitude = $_POST['data2'];
    $sql = "INSERT INTO attendance (user_id, mode, latitude, longitude, timestamp, selfie) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isddss", $user_id, $mode, $latitude, $longitude, $timestamp, $selfie);
}else {
    echo "Invalid attendance mode.";
    $conn->close();
    exit();
}

if ($stmt->execute()) {
    echo "Attendance logged successfully.";
} else {
    echo "Error logging attendance: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
