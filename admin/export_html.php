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

// Get filter and pagination parameters
$filterDepartment = isset($_POST['department']) ? $_POST['department'] : '';
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$filterMode = isset($_POST['mode']) ? $_POST['mode'] : '';
$filterWhere = isset($_POST['where']) ? $_POST['where'] : '';

// Prepare SQL query for filtered results
$sql = "SELECT 
            users.employer_id,
            users.username,
            users.full_name,
            users.department,
            attendance.mode,
            attendance.in_time,
            attendance.out_time,
            attendance.selfie_in,
            attendance.selfie_out,
            attendance.data
        FROM attendance
        JOIN users ON attendance.user_id = users.id
        WHERE users.role <> 'admin'";

// Prepare the query dynamically based on filters
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

// Execute the query
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Start HTML export
$htmlContent = "
<html>
<head>
    <title>Attendance Report</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        img {
            max-width: 100px;
            max-height: 100px;
        }
    </style>
</head>
<body>
    <h1>Attendance Report</h1>
    <table>
        <tr>
            <th>Employer ID</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Department</th>
            <th>Mode</th>
            <th>In Time</th>
            <th>Out Time</th>
            <th>Selfie In</th>
            <th>Selfie Out</th>
            <th>Location</th>
        </tr>";

// Loop through the results and populate the HTML table
while ($row = $result->fetch_assoc()) {
    $htmlContent .= "
        <tr>
            <td>{$row['employer_id']}</td>
            <td>{$row['username']}</td>
            <td>{$row['full_name']}</td>
            <td>{$row['department']}</td>
            <td>{$row['mode']}</td>
            <td>{$row['in_time']}</td>
            <td>{$row['out_time']}</td>
            <td><img src='../uploads/Selfies_in&out/{$row['selfie_in']}' alt='Selfie In'></td>
            <td><img src='../uploads/Selfies_in&out/{$row['selfie_out']}' alt='Selfie Out'></td>
            <td>{$row['data']}</td>
        </tr>";
}

$htmlContent .= "
    </table>
</body>
</html>";

// Output the HTML content for download
header("Content-Type: text/html");
header("Content-Disposition: attachment; filename=attendance_report.html");
echo $htmlContent;
exit();
?>
