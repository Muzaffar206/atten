<?php
session_start();
include("assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Fetch all users for the filter dropdown
$userSql = "SELECT id, username FROM users";
$userResult = $conn->query($userSql);

$users = [];
if ($userResult->num_rows > 0) {
    while ($userRow = $userResult->fetch_assoc()) {
        $users[] = $userRow;
    }
}

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

<!DOCTYPE html>
<html>
<head>
    <title>Filtered Attendance</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Filtered Attendance</h1>

    <form method="GET" action="">
        <label for="user">User:</label>
        <select name="user" id="user">
            <option value="0">All Users</option>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>" <?php echo ($filterUser == $user['id']) ? 'selected' : ''; ?>>
                    <?php echo $user['username']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="department">Department:</label>
        <select name="department" id="department">
            <option value="">All Departments</option>
            <option value="ROP" <?php echo ($department == 'ROP') ? 'selected' : ''; ?>>ROP</option>
            <option value="Admin" <?php echo ($department == 'Admin') ? 'selected' : ''; ?>>Admin</option>
            <option value="Clinics" <?php echo ($department == 'Clinics') ? 'selected' : ''; ?>>Clinics</option>
        </select>

        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" id="start_date" value="<?php echo $startDate; ?>">

        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" id="end_date" value="<?php echo $endDate; ?>">

        <button type="submit">Filter</button>
        <button type="submit" name="export_csv" value="1">Export CSV</button>
    </form>

    <table>
        <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Department</th>
            <th>Date</th>
            <th>In Time</th>
            <th>Out Time</th>
        </tr>
        <?php
        if ($filterResult->num_rows > 0) {
            while ($row = $filterResult->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['user_id'] . "</td>
                        <td>" . $row['username'] . "</td>
                        <td>" . $row['department'] . "</td>
                        <td>" . $row['date'] . "</td>
                        <td>" . $row['in_time'] . "</td>
                        <td>" . $row['out_time'] . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No records found</td></tr>";
        }
        ?>
    </table>
    <button onclick="document.location='logout.php'">Logout</button>
</body>
</html>
