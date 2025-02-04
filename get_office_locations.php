<?php
header('Content-Type: application/json');
include("assest/connection/config.php");

$result = $conn->query("SELECT name, latitude as lat, longitude as lon, radius, qr_code as qrCode FROM office_locations");
$officeLocations = $result->fetch_all(MYSQLI_ASSOC);

echo json_encode($officeLocations);
