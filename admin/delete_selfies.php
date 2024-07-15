<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../assest/connection/config.php");

$twoMinutesAgo = date('Y-m-d H:i:s', strtotime('-2 minutes'));

// Update selfie_in and selfie_out fields to NULL where in_time is older than 2 minutes
$sql = "UPDATE attendance SET selfie_in = NULL, selfie_out = NULL WHERE (selfie_in IS NOT NULL OR selfie_out IS NOT NULL) AND in_time < ?";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die('Error preparing statement: ' . $conn->error);
}

$stmt->bind_param('s', $twoMinutesAgo);
$stmt->execute();
if ($stmt->error) {
    die('Error executing query: ' . $stmt->error);
}

// Get number of affected rows
$affected_rows = $stmt->affected_rows;
echo "Affected rows: " . $affected_rows; // Output the number of affected rows for debugging

$stmt->close();
$conn->close();

// Redirect back to attendance_report.php to avoid form resubmission on refresh

exit();
?>
