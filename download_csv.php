<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=attendance_data.csv');

$conn = new mysqli('localhost', 'root', '', 'attendance_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$output = fopen('php://output', 'w');
fputcsv($output, array('ID', 'Mode', 'Data', 'Latitude', 'Longitude', 'Timestamp', 'Type'));

$sql = "SELECT * FROM attendance";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

fclose($output);
$conn->close();
?>
