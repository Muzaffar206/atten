<?php
session_start();
include("assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESCO | Attendance</title>
    <link rel="stylesheet" href="assest/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="assest/css/main.css">
    <style>
        table {
            table-layout: fixed;
            width: 100%;
        }
        th, td {
            word-wrap: break-word;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Welcome <?php echo htmlspecialchars($username); ?></h1>
    <h2>Attendance Report</h2>

    <form method="post" action="">
        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="department">Department:</label>
            <select id="department" name="department" class="form-control">
                <option value="">All Departments</option>
                <?php
                $departmentsQuery = "SELECT DISTINCT department FROM users";
                $departmentsResult = $conn->query($departmentsQuery);
                if ($departmentsResult) {
                    while ($row = $departmentsResult->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['department']) . '">' . htmlspecialchars($row['department']) . '</option>';
                    }
                } else {
                    echo '<option value="">Error Fetching Departments</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="employee">Employee:</label>
            <select id="employee" name="employee" class="form-control">
                <option value="">All Employees</option>
                <?php
                $employeesQuery = "SELECT id, full_name FROM users";
                $employeesResult = $conn->query($employeesQuery);
                if ($employeesResult) {
                    while ($row = $employeesResult->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['full_name']) . '</option>';
                    }
                } else {
                    echo '<option value="">Error Fetching Employees</option>';
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Get Attendance</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
            include("assest/connection/config.php");

            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $department = isset($_POST['department']) ? $_POST['department'] : '';
            $employee = isset($_POST['employee']) ? $_POST['employee'] : '';

            $sql = "SELECT users.id AS employer_id, users.full_name, users.department, 
                           DATE(attendance.in_time) AS attendance_date,
                           DAYOFWEEK(attendance.in_time) AS weekday,
                           attendance.is_present,
                           MAX(CASE WHEN attendance.type = 'In' THEN attendance.in_time END) AS in_time,
                           MAX(CASE WHEN attendance.type = 'Out' THEN attendance.out_time END) AS out_time
                    FROM users
                    LEFT JOIN attendance ON users.id = attendance.user_id
                    WHERE DATE(attendance.in_time) BETWEEN ? AND ?";
            $params = array($start_date, $end_date);
            $types = "ss";

            if (!empty($department)) {
                $sql .= " AND users.department = ?";
                $params[] = $department;
                $types .= "s";
            }

            if (!empty($employee)) {
                $sql .= " AND users.id = ?";
                $params[] = $employee;
                $types .= "i";
            }

            $sql .= " GROUP BY users.id, DATE(attendance.in_time)
                      ORDER BY users.id, DATE(attendance.in_time)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            echo '<table class="table table-bordered">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Employer ID</th>';
            echo '<th>Full Name</th>';
            echo '<th>Department</th>';
            echo '<th>Status</th>';

            // Generate header with dates and weekdays
            $current_date = strtotime($start_date);
            $end_date = strtotime($end_date);
            $date_columns = [];
            while ($current_date <= $end_date) {
                $date_str = date('d-M', $current_date) . '<br>' . date('D', $current_date);
                echo '<th>' . $date_str . '</th>';
                $date_columns[] = date('Y-m-d', $current_date);
                $current_date = strtotime('+1 day', $current_date);
            }

            echo '<th>Total Work Time</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            $attendance_data = [];
            while ($row = $result->fetch_assoc()) {
                $employer_id = isset($row['employer_id']) ? $row['employer_id'] : '';
                $fullname = isset($row['full_name']) ? $row['full_name'] : '';
                $department = isset($row['department']) ? $row['department'] : '';

                $attendance_data[$employer_id]['full_name'] = $fullname;
                $attendance_data[$employer_id]['department'] = $department;
                $attendance_data[$employer_id][$row['attendance_date']] = [
                    'is_present' => $row['is_present'],
                    'in_time' => $row['in_time'],
                    'out_time' => $row['out_time']
                ];
            }

            foreach ($attendance_data as $employer_id => $data) {
                echo '<tr>';
                echo '<td rowspan="3">' . $employer_id . '</td>';
                echo '<td rowspan="3">' . $data['full_name'] . '</td>';
                echo '<td rowspan="3">' . $data['department'] . '</td>';
                echo '<td>Status</td>';
                foreach ($date_columns as $date) {
                    $is_present = isset($data[$date]['is_present']) ? $data[$date]['is_present'] : 'N/A';
                    echo '<td>' . $is_present . '</td>';
                }
                echo '<td rowspan="3"></td>'; // Placeholder for total work time calculation
                echo '</tr>';

                echo '<tr>';
                echo '<td>In Time</td>';
                foreach ($date_columns as $date) {
                    $in_time = isset($data[$date]['in_time']) ? $data[$date]['in_time'] : 'N/A';
                    echo '<td>' . $in_time . '</td>';
                }
                echo '</tr>';

                echo '<tr>';
                echo '<td>Out Time</td>';
                foreach ($date_columns as $date) {
                    $out_time = isset($data[$date]['out_time']) ? $data[$date]['out_time'] : 'N/A';
                    echo '<td>' . $out_time . '</td>';
                }
                echo '</tr>';

                // Calculate total work time for each user
                $total_work_time = 0;
                foreach ($date_columns as $date) {
                    $in_time = isset($data[$date]['in_time']) ? strtotime($data[$date]['in_time']) : null;
                    $out_time = isset($data[$date]['out_time']) ? strtotime($data[$date]['out_time']) : null;
                    if ($in_time && $out_time) {
                        $total_work_time += ($out_time - $in_time);
                    }
                }
                $total_work_hours = floor($total_work_time / 3600);
                $total_work_minutes = floor(($total_work_time % 3600) / 60);
                echo '<td>' . $total_work_hours . 'h ' . $total_work_minutes . 'm</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';

            $stmt->close();
            $conn->close();
        } else {
            echo '<div class="alert alert-warning" role="alert">Please select both Start Date and End Date.</div>';
        }
    }
    ?>

</div>
</body>
</html>
