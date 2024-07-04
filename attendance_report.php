<?php
session_start();
include("assest/connection/config.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$department = isset($_POST['department']) ? $_POST['department'] : 'All';
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-01');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

$users_query = ($department === 'All') ? 
    "SELECT id, employer_id, full_name, department FROM users" : 
    "SELECT id, employer_id, full_name, department FROM users WHERE department = ?";

$stmt_users = $conn->prepare($users_query);
if ($department !== 'All') {
    $stmt_users->bind_param("s", $department);
}
$stmt_users->execute();
$users_result = $stmt_users->get_result();

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
            <option value="All">All Departments</option>
            <option value="Education">Education</option>
            <option value="Medical">Medical</option>
            <option value="ROP">ROP</option>
            <option value="Admin">Admin</option>
            <option value="Clinics">Clinics</option>
        </select>
        <label for="from_date">From Date:</label>
        <input type="date" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
        <label for="to_date">To Date:</label>
        <input type="date" id="to_date" name="to_date" value="<?php echo $to_date; ?>">
        <input type="submit" value="Show Data">
    </form>
    <br>

    <?php if ($users_result->num_rows > 0): ?>
        <form method="post" action="download_xls.php">
            <input type="hidden" name="department" value="<?php echo $department; ?>">
            <input type="hidden" name="from_date" value="<?php echo $from_date; ?>">
            <input type="hidden" name="to_date" value="<?php echo $to_date; ?>">
            <button type="submit">Download XLS</button>
        </form>
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
                    <td><?php echo $user['department']; ?></td>
                    <td><?php echo $user['employer_id']; ?></td>
                    <td><?php echo $user['full_name']; ?></td>
                    <?php foreach ($dates as $date): ?>
                        <td>
                            <?php
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
        <p>No users found for the selected department.</p>
    <?php endif; ?>

</body>
</html>

<?php
$conn->close();
?>
