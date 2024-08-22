<?php
session_start();
include("assest/connection/config.php");
date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d');

$sql = "SELECT * FROM attendance WHERE user_id = ? AND DATE(in_time) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$attendance = [
    'office_in' => false,
    'outdoor_in' => false,
    'office_out' => false,
    'outdoor_out' => false
];

while ($row = $result->fetch_assoc()) {
    $mode = strtolower($row['mode']); // Convert mode to lowercase
    if (!is_null($row['in_time'])) {
        $attendance[$mode . '_in'] = true;
    }
    if (!is_null($row['out_time'])) {
        $attendance[$mode . '_out'] = true;
    }
}
header('Content-Type: application/json');
echo json_encode($attendance);

$stmt->close();
$conn->close();
?>