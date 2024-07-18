<?php
error_reporting(0);

require_once 'index.php';

// Establish database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error internally (optional)
    error_log("Connection failed: " . $conn->connect_error);

    exit();
}

?>
