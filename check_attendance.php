<?php
session_start();
include("assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'User not logged in']);
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d');

$sql = "SELECT * FROM attendance WHERE user_id = ? AND DATE(in_time) = ? AND out_time IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['exists' => true]);
} else {
    echo json_encode(['exists' => false]);
}

$stmt->close();
$conn->close();
?>