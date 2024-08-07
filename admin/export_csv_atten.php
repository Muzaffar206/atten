<?php
session_start();
session_regenerate_id(true);

date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

// Redirect if user is not logged in or is not an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}

include("../assest/connection/config.php");

// Fetch filter parameters from GET request
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare SQL query for fetching data based on filters
$sql = "SELECT 
            attendance.id AS attendance_id,
            users.employer_id,
            users.username,
            users.full_name,
            users.department,
            attendance.mode,
            attendance.latitude,
            attendance.longitude,
            attendance.in_time,
            attendance.out_time,
            attendance.selfie_in,
            attendance.selfie_out,
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

$sql .= " ORDER BY attendance.id DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set headers to download file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=attendance.csv');
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('ID', 'Employer ID', 'Username', 'Full Name', 'Department', 'Mode', 'Where', 'Latitude', 'Longitude', 'In Time', 'Out Time', 'Selfie In', 'Selfie Out', 'Map'));

// Fetch and output each row of data
while ($row = $result->fetch_assoc()) {
    $latitude = !empty($row['latitude']) ? $row['latitude'] : 'NA';
    $longitude = !empty($row['longitude']) ? $row['longitude'] : 'NA';
    $selfieInPath = $row['selfie_in'];
    $relativeSelfieInPath = str_replace('C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/', '', $selfieInPath);
    $imageInSrc = 'Selfies_in&out/' . htmlspecialchars($relativeSelfieInPath);
    $selfieOutPath = $row['selfie_out'];
    $relativeSelfieOutPath = str_replace('C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/', '', $selfieOutPath);
    $imageOutSrc = 'Selfies_in&out/' . htmlspecialchars($relativeSelfieOutPath);

    $data = [
        $row['attendance_id'],
        $row['employer_id'],
        $row['username'],
        $row['full_name'],
        $row['department'],
        $row['mode'],
        $row['data'],
        $latitude,
        $longitude,
        $row['in_time'],
        $row['out_time'],
        file_exists($imageInSrc) ? $imageInSrc : 'N/A',
        file_exists($imageOutSrc) ? $imageOutSrc : 'N/A',
        (!empty($row['latitude']) && !empty($row['longitude'])) ? "https://maps.google.com/?q={$row['latitude']},{$row['longitude']}" : 'N/A'
    ];

    fputcsv($output, $data);
}

fclose($output);
exit();
?>
