<?php
session_start();
include("assest/connection/config.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=attendance_data.csv');


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
