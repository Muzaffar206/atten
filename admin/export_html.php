<?php
session_start();
session_regenerate_id(true);

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

// Get filter parameters
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare SQL query based on filters
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

// Execute the query
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Start HTML output
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance_report.html"');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
    <style>
        /* Add your custom CSS styles for the HTML report here */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
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
    <h2>Attendance Report</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Employer ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Department</th>
                <th>Mode</th>
                <th>Where</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>In Time</th>
                <th>Out Time</th>
                <th>Selfie In</th>
                <th>Selfie Out</th>
                <th>Map</th>
            </tr>
        </thead>
        <tbody>
            <?php
            while ($row = $result->fetch_assoc()) {
                $latitude = !empty($row['latitude']) ? $row['latitude'] : 'NA';
                $longitude = !empty($row['longitude']) ? $row['longitude'] : 'NA';
            ?>
                <tr>
                    <td><?php echo $row['attendance_id']; ?></td>
                    <td><?php echo $row['employer_id']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['department']; ?></td>
                    <td><?php echo $row['mode']; ?></td>
                    <td><?php echo $row['data']; ?></td>
                    <td><?php echo $latitude; ?></td>
                    <td><?php echo $longitude; ?></td>
                    <td><?php echo $row['in_time']; ?></td>
                    <td><?php echo $row['out_time']; ?></td>
                    <td>
                        <?php
                        $selfieInPath = $row['selfie_in'];
                        if (!empty($selfieInPath)) {
                            $relativeSelfieInPath = str_replace('C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/', '', $selfieInPath);
                            $imageInSrc = 'Selfies_in&out/' . htmlspecialchars($relativeSelfieInPath);
                            if (file_exists($imageInSrc)) {
                                echo '<img src="' . $imageInSrc . '" alt="Selfie In">';
                            } else {
                                echo 'N/A';
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        $selfieOutPath = $row['selfie_out'];
                        if (!empty($selfieOutPath)) {
                            $relativeSelfieOutPath = str_replace('C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/', '', $selfieOutPath);
                            $imageOutSrc = 'Selfies_in&out/' . htmlspecialchars($relativeSelfieOutPath);
                            if (file_exists($imageOutSrc)) {
                                echo '<img src="' . $imageOutSrc . '" alt="Selfie Out">';
                            } else {
                                echo 'N/A';
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if (!empty($row['latitude']) && !empty($row['longitude'])) {
                            echo '<a href="https://maps.google.com/?q=' . htmlspecialchars($row['latitude']) . ',' . htmlspecialchars($row['longitude']) . '" target="_blank">View Map</a>';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>
