<?php
require_once 'SimpleXLS.php'; // Include the SimpleXLS library

$inputFileName = '24.6.24.xls'; // Path to your uploaded Excel file

if ( $xls = SimpleXLS::parse($inputFileName) ) {
    $data = $xls->rows();
} else {
    echo SimpleXLS::parseError();
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'attendance_system');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mode VARCHAR(50),
    data VARCHAR(255),
    latitude FLOAT,
    longitude FLOAT,
    timestamp DATETIME,
    type VARCHAR(50)
)";
$conn->query($sql);

// Skip the header row and insert data into MySQL
foreach ($data as $key => $row) {
    if ($key == 0) continue; // Skip header row
    $mode = $row[0];
    $dataField = $row[1];
    $latitude = $row[2];
    $longitude = $row[3];
    $timestamp = date('Y-m-d H:i:s', strtotime($row[4])); // Convert to MySQL datetime format
    $type = $row[5];

    $stmt = $conn->prepare("INSERT INTO attendance (mode, data, latitude, longitude, timestamp, type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddss", $mode, $dataField, $latitude, $longitude, $timestamp, $type);
    $stmt->execute();
}

$conn->close();
?>
