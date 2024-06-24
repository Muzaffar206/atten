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
$scanType = $_POST['scanType'];
$timestamp = date('Y-m-d H:i:s'); // IST timezone timestamp
$selfie = null;

if (isset($_POST['selfie']) && !empty($_POST['selfie'])) {
    $selfie = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['selfie']));
}

if ($mode === 'Office') {
    $data1 = $_POST['data1']; // QR Code message
    if ($scanType === "In") {
        $sql = "INSERT INTO attendance (user_id, mode, data, in_time, selfie) VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE in_time = VALUES(in_time)";
    } else if ($scanType === "Out") {
        $sql = "UPDATE attendance SET out_time = ? WHERE user_id = ? AND data = ? AND mode = ? AND in_time IS NOT NULL";
    } else {
        echo "Invalid scan type.";
        $conn->close();
        exit();
    }
    $stmt = $conn->prepare($sql);
    if ($scanType === "In") {
        $stmt->bind_param("issss", $user_id, $mode, $data1, $timestamp, $selfie);
    } else {
        $stmt->bind_param("siss", $timestamp, $user_id, $data1, $mode);
    }
} else if ($mode === 'Outdoor') {
    $coords = explode(',', $_POST['data1']);
    $latitude = $coords[0];
    $longitude = $coords[1];
    if ($scanType === "In") {
        $sql = "INSERT INTO attendance (user_id, mode, latitude, longitude, in_time, selfie) VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE in_time = VALUES(in_time)";
    } else if ($scanType === "Out") {
        $sql = "UPDATE attendance SET out_time = ? WHERE user_id = ? AND latitude = ? AND longitude = ? AND mode = ? AND in_time IS NOT NULL";
    } else {
        echo "Invalid scan type.";
        $conn->close();
        exit();
    }
    $stmt = $conn->prepare($sql);
    if ($scanType === "In") {
        $stmt->bind_param("isddss", $user_id, $mode, $latitude, $longitude, $timestamp, $selfie);
    } else {
        $stmt->bind_param("sidds", $timestamp, $user_id, $latitude, $longitude, $mode);
    }
} else {
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
