<?php
session_start();
include("../assest/connection/config.php");

// Handle filter form submission
$filterUser = isset($_GET['user']) ? intval($_GET['user']) : 0;
$department = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$filterSql = "SELECT users.id AS user_id, users.username, DATE(attendance.in_time) as date, 
                attendance.in_time, attendance.out_time, users.department 
                FROM attendance 
                JOIN users ON attendance.user_id = users.id 
                WHERE 1=1";

if ($filterUser > 0) {
    $filterSql .= " AND users.id = $filterUser";
}

if (!empty($department)) {
    $filterSql .= " AND users.department = '$department'";
}

if (!empty($startDate) && !empty($endDate)) {
    $filterSql .= " AND DATE(attendance.in_time) BETWEEN '$startDate' AND '$endDate'";
} elseif (!empty($startDate)) {
    $filterSql .= " AND DATE(attendance.in_time) = '$startDate'";
}

$filterSql .= " ORDER BY DATE(attendance.in_time) DESC";

$filterResult = $conn->query($filterSql);

// Function to sanitize CSV data
function sanitizeCsvField($value) {
    // Escape double quotes
    $escaped = str_replace('"', '""', $value);
    // Enclose in double quotes
    return '"' . $escaped . '"';
}

// Export CSV functionality
if (isset($_GET['export_csv'])) {
    $filename = "filtered_attendance.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    // Write CSV headers
    fputcsv($output, ['User ID', 'Username', 'Department', 'Date', 'In Time', 'Out Time']);

    // Write CSV rows
    if ($filterResult->num_rows > 0) {
        while ($row = $filterResult->fetch_assoc()) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['department'],
                $row['date'],
                $row['in_time'],
                $row['out_time']
            ]);
        }
    }

    fclose($output);
    exit;
}

$conn->close();
?>
