<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized access');
}

include("../assest/connection/config.php");

// Datatables server-side processing variables
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$orderColumn = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$orderDir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'desc';

// Filters
$filterDepartment = isset($_POST['department']) ? $_POST['department'] : '';
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// Base query
$sql = "SELECT 
            users.id AS user_id,
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
            attendance.selfie_out
        FROM attendance 
        JOIN users ON attendance.user_id = users.id
        WHERE users.role <> 'admin'";

// Apply filters
$params = array();
if (!empty($filterDepartment)) {
    $sql .= " AND users.department = ?";
    $params[] = $filterDepartment;
}
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND DATE(attendance.in_time) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
} elseif (!empty($startDate)) {
    $sql .= " AND DATE(attendance.in_time) = ?";
    $params[] = $startDate;
}

// Apply search
if (!empty($search)) {
    $sql .= " AND (users.username LIKE ? OR users.full_name LIKE ? OR users.employer_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count total records
$countSql = "SELECT COUNT(*) as count FROM ($sql) as counted";
$stmt = $conn->prepare($countSql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$countResult = $stmt->get_result();
$totalRecords = $countResult->fetch_assoc()['count'];

// Apply ordering
$columns = array('attendance_id', 'employer_id', 'username', 'full_name', 'department', 'mode', 'latitude', 'longitude', 'in_time', 'out_time', 'selfie_in', 'selfie_out');
$sql .= " ORDER BY " . $columns[$orderColumn] . " " . $orderDir;

// Apply pagination
$sql .= " LIMIT ?, ?";
$params[] = $start;
$params[] = $length;

// Prepare and execute the final query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$data = array();
while ($row = $result->fetch_assoc()) {
    $selfie_in = !empty($row['selfie_in']) ? str_replace('admin/', '', $row['selfie_in']) : '';
    $selfie_out = !empty($row['selfie_out']) ? str_replace('admin/', '', $row['selfie_out']) : '';

    $data[] = array(
        "attendance_id" => htmlspecialchars($row['attendance_id']),
        "employer_id" => htmlspecialchars($row['employer_id']),
        "username" => htmlspecialchars($row['username']),
        "full_name" => htmlspecialchars($row['full_name']),
        "department" => htmlspecialchars($row['department']),
        "mode" => htmlspecialchars($row['mode']),
        "latitude" => !empty($row['latitude']) ? htmlspecialchars($row['latitude']) : 'N/A',
        "longitude" => !empty($row['longitude']) ? htmlspecialchars($row['longitude']) : 'N/A',
        "in_time" => htmlspecialchars($row['in_time']),
        "out_time" => htmlspecialchars($row['out_time']),
        "selfie_in" => !empty($selfie_in) ? '<img src="get_image.php?path=' . urlencode($selfie_in) . '" alt="Selfie_in" width="150" height="150">' : 'N/A',
        "selfie_out" => !empty($selfie_out) ? '<img src="get_image.php?path=' . urlencode($selfie_out) . '" alt="Selfie_out" width="150" height="150">' : 'N/A',
        "map" => !empty($row['latitude']) && !empty($row['longitude']) ? '<a href="https://www.google.com/maps?q=' . htmlspecialchars($row['latitude']) . ',' . htmlspecialchars($row['longitude']) . '" target="_blank">View on Map</a>' : 'N/A'
    );
}

$response = array(
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecords,
    "data" => $data
);

header('Content-Type: application/json');
echo json_encode($response);
?>
