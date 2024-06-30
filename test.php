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

// Assuming you have form inputs or variables for department selection and date range
$department = $_POST['department']; // Example: Department selection
$from_date = $_POST['from_date']; // Example: From date selected
$to_date = $_POST['to_date']; // Example: To date selected

// Assuming you have a function to get users based on department (you may need to adjust as per your database schema)
$users_query = "SELECT id, employer_id, full_name FROM users WHERE department = ?";
$stmt_users = $conn->prepare($users_query);
$stmt_users->bind_param("s", $department);
$stmt_users->execute();
$users_result = $stmt_users->get_result();

// Prepare dates array based on selected date range
$dates = [];
$current_date = strtotime($from_date);
$end_date = strtotime($to_date);

while ($current_date <= $end_date) {
    $dates[] = date('d-m-Y', $current_date);
    $current_date = strtotime('+1 day', $current_date);
}

$stmt_users->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Table</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h2>Attendance Table</h2>
    <form method="post" action="">
        <label for="department">Select Department:</label>
        <select name="department" id="department">
            <option value="Education">Education</option>
            <option value="Medical">Medical</option>
            <option value="ROP">ROP</option>
            <option value="Admin">Admin</option>
            <option value="Clinics">Clinics</option>
            <!-- Add more options based on your departments -->
        </select>
        <label for="from_date">From Date:</label>
        <input type="date" id="from_date" name="from_date" value="<?php echo date('Y-m-d'); ?>">
        <label for="to_date">To Date:</label>
        <input type="date" id="to_date" name="to_date" value="<?php echo date('Y-m-d'); ?>">
        <input type="submit" value="Show Data">
    </form>
    <br>

    <?php if ($users_result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Department</th>
                <th>Employer Code</th>
                <th>Employer Name</th>
                <?php foreach ($dates as $date): ?>
                    <th><?php echo $date; ?></th>
                <?php endforeach; ?>
            </tr>
            <?php while ($user = $users_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $department; ?></td>
                    <td><?php echo $user['employer_id']; ?></td>
                    <td><?php echo $user['full_name']; ?></td>
                    <?php foreach ($dates as $date): ?>
                        <td>
                            <!-- Example of how you might fetch attendance data for this user on each date -->
                            <?php
                            // Example SQL query to fetch attendance for this user on $date
                            $attendance_query = "SELECT * FROM attendance WHERE user_id = ? AND DATE_FORMAT(in_time, '%d-%m-%Y') = ?";
                            $stmt_attendance = $conn->prepare($attendance_query);
                            $stmt_attendance->bind_param("is", $user['id'], $date);
                            $stmt_attendance->execute();
                            $attendance_result = $stmt_attendance->get_result();
                            if ($attendance_result->num_rows > 0) {
                                $attendance_data = $attendance_result->fetch_assoc();
                                echo "Status: " . ($attendance_data['is_present'] ? "Present" : "Absent") . "<br>";
                                echo "In Time: " . date('H:i:s', strtotime($attendance_data['in_time'])) . "<br>";
                                if ($attendance_data['out_time'] != null) {
                                    echo "Out Time: " . date('H:i:s', strtotime($attendance_data['out_time'])) . "<br>";
                                }
                                // Calculate total hours worked if applicable
                                // Example: Calculate total hours worked based on in_time and out_time
                                // $total_hours = strtotime($attendance_data['out_time']) - strtotime($attendance_data['in_time']);
                                // echo "Total Hours: " . gmdate('H:i:s', $total_hours);
                            } else {
                                echo "No attendance recorded";
                            }
                            $stmt_attendance->close();
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No users found for selected department.</p>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
