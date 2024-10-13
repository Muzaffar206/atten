<?php
header('Content-Type: application/json');
include("office_locations.php");
echo json_encode($officeLocations);