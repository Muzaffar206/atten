<?php
session_start();
date_default_timezone_set('Asia/Kolkata');

// Redirect if user is not logged in or is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../assest/connection/config.php");

// Get filter parameters
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$filterMode = isset($_GET['mode']) ? $_GET['mode'] : '';
$filterWhere = isset($_GET['where']) ? $_GET['where'] : '';

// Prepare SQL query for exporting data
$sql = "SELECT 
            users.employer_id,
            users.username,
            users.full_name,
            users.department,
            attendance.mode,
            attendance.in_latitude, 
            attendance.in_longitude, 
            attendance.out_latitude, 
            attendance.out_longitude, 
            attendance.in_time, 
            attendance.out_time,
            attendance.data
        FROM attendance
        JOIN users ON attendance.user_id = users.id
        WHERE users.role <> 'admin'";

$params = [];
$types = '';

if (!empty($filterDepartment)) {
    $sql .= " AND users.department = ?";
    $params[] = $filterDepartment;
    $types .= 's';
}
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND DATE(attendance.in_time) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
} elseif (!empty($startDate)) {
    $sql .= " AND DATE(attendance.in_time) = ?";
    $params[] = $startDate;
    $types .= 's';
}
if (!empty($searchQuery)) {
    $sql .= " AND (users.username LIKE ? OR users.full_name LIKE ? OR attendance.mode LIKE ?)";
    $searchQuery = "%$searchQuery%";
    $params[] = $searchQuery;
    $params[] = $searchQuery;
    $params[] = $searchQuery;
    $types .= 'sss';
}
if (!empty($filterMode)) {
    $sql .= " AND attendance.mode = ?";
    $params[] = $filterMode;
    $types .= 's';
}
if (!empty($filterWhere)) {
    $sql .= " AND attendance.data LIKE ?";
    $params[] = '%' . $filterWhere . '%';
    $types .= 's';
}

$sql .= " ORDER BY attendance.id DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=attendance_export.csv');

// Output CSV column headers
$output = fopen('php://output', 'w');
fputcsv($output, ['Employer ID', 'Username', 'Full Name', 'Department', 'Mode', 'Map In', 'Map Out', 'In Time', 'Out Time', 'Location']);

// Output CSV rows
while ($row = $result->fetch_assoc()) {
    $mapIn = $row['in_latitude'] . ',' . $row['in_longitude'];
    $mapOut = $row['out_latitude'] . ',' . $row['out_longitude'];
    fputcsv($output, [
        $row['employer_id'], 
        $row['username'], 
        $row['full_name'], 
        $row['department'], 
        $row['mode'], 
        $mapIn, 
        $mapOut, 
        $row['in_time'], 
        $row['out_time'], 
        $row['data']
    ]);
}

fclose($output);
$stmt->close();
$conn->close();
?>
