<?php
session_start();
include("assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata'); // Set default timezone to IST

$user_id = $_SESSION['user_id'];
$mode = $_POST['mode'];
$scanType = $_POST['scanType'];
$timestamp = date('Y-m-d H:i:s'); // IST timezone timestamp
$selfie = null;

if (isset($_POST['selfie']) && !empty($_POST['selfie'])) {
    $selfie = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['selfie']));
}

// Check if both "In" and "Out" attendance already logged for today in outdoor mode
$sqlCheckInAttendance = "SELECT COUNT(*) AS count FROM attendance WHERE user_id = ? AND DATE(in_time) = CURDATE() AND mode = ? AND in_time IS NOT NULL";
$stmtCheckInAttendance = $conn->prepare($sqlCheckInAttendance);
$stmtCheckInAttendance->bind_param("is", $user_id, $mode);
$stmtCheckInAttendance->execute();
$stmtCheckInAttendance->bind_result($inAttendanceCount);
$stmtCheckInAttendance->fetch();
$stmtCheckInAttendance->close();

$sqlCheckOutAttendance = "SELECT COUNT(*) AS count FROM attendance WHERE user_id = ? AND DATE(out_time) = CURDATE() AND mode = ? AND out_time IS NOT NULL";
$stmtCheckOutAttendance = $conn->prepare($sqlCheckOutAttendance);
$stmtCheckOutAttendance->bind_param("is", $user_id, $mode);
$stmtCheckOutAttendance->execute();
$stmtCheckOutAttendance->bind_result($outAttendanceCount);
$stmtCheckOutAttendance->fetch();
$stmtCheckOutAttendance->close();

if ($scanType === 'In' && $inAttendanceCount > 0) {
    echo "You have already logged 'In' attendance for today in $mode mode.";
    exit();
}

if ($scanType === 'Out' && $outAttendanceCount > 0) {
    echo "You have already logged 'Out' attendance for today in $mode mode.";
    exit();
}

// Proceed to log attendance
if ($mode === 'Office') {
    $data1 = $_POST['data1']; // QR Code message
    if ($scanType === "In") {
        $sql = "INSERT INTO attendance (user_id, mode, data, in_time, selfie_in) VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE in_time = VALUES(in_time), selfie_in = VALUES(selfie_in)";
    } else if ($scanType === "Out") {
        $sql = "UPDATE attendance SET out_time = ?, selfie_out = ? WHERE user_id = ? AND data = ? AND mode = ? AND in_time IS NOT NULL";
    } else {
        echo "Invalid scan type.";
        $conn->close();
        exit();
    }
    $stmt = $conn->prepare($sql);
    if ($scanType === "In") {
        $stmt->bind_param("issss", $user_id, $mode, $data1, $timestamp, $selfie);
    } else {
        $stmt->bind_param("ssiss", $timestamp, $selfie, $user_id, $data1, $mode);
    }
} else if ($mode === 'Outdoor') {
    $coords = explode(',', $_POST['data1']);
    $latitude = $coords[0];
    $longitude = $coords[1];
    if ($scanType === "In") {
        $sql = "INSERT INTO attendance (user_id, mode, latitude, longitude, in_time, selfie_in) VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE in_time = VALUES(in_time), selfie_in = VALUES(selfie_in)";
    } else if ($scanType === "Out") {
        $sql = "UPDATE attendance SET out_time = ?, selfie_out = ? WHERE user_id = ? AND latitude = ? AND longitude = ? AND mode = ? AND in_time IS NOT NULL";
    } else {
        echo "Invalid scan type.";
        $conn->close();
        exit();
    }
    $stmt = $conn->prepare($sql);
    if ($scanType === "In") {
        $stmt->bind_param("isddss", $user_id, $mode, $latitude, $longitude, $timestamp, $selfie);
    } else {
        $stmt->bind_param("ssidds", $timestamp, $selfie, $user_id, $latitude, $longitude, $mode);
    }
} else {
    echo "Invalid attendance mode.";
    $conn->close();
    exit();
}

if ($stmt->execute()) {
    $attendance_id = $stmt->insert_id;

    $is_present = ($scanType === 'In') ? 1 : 0;
    $updateUserSql = "UPDATE attendance SET is_present = ? WHERE id = ?";
    $updateUserStmt = $conn->prepare($updateUserSql);
    $updateUserStmt->bind_param("ii", $is_present, $attendance_id);
    $updateUserStmt->execute();

    echo "Attendance logged successfully.";
} else {
    echo "Error logging attendance: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
