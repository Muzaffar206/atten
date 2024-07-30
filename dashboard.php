<?php
session_start();
session_regenerate_id(true);
include("assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] === 'admin') {
    header("Location: admin/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = "";

// Fetch username from database
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

// Default date values
$from_date = isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : date('Y-m-d');

// Fetch aggregated attendance data based on user ID and date range
$attendance_query = "SELECT 
                        users.id AS user_id, 
                        users.username, 
                        users.employer_id, 
                        users.full_name, 
                        DATE(final_attendance.first_in) as date, 
                        MIN(final_attendance.first_in) as first_in, 
                        MAX(final_attendance.last_out) as last_out, 
                        MIN(final_attendance.first_mode) as first_mode, 
                        MAX(final_attendance.last_mode) as last_mode, 
                        COUNT(attendance.id) AS total_entries,
                        GROUP_CONCAT(attendance.data SEPARATOR ', ') AS data,
                        CASE WHEN MAX(attendance.is_present) = 1 THEN 'Present' ELSE 'Absent' END AS attendance_status,
                        users.department,
                        final_attendance.total_hours
                    FROM final_attendance 
                    JOIN users ON final_attendance.user_id = users.id 
                    LEFT JOIN attendance ON attendance.user_id = users.id AND DATE(attendance.in_time) = DATE(final_attendance.first_in) 
                    WHERE users.id = ?";

// Modify query based on filter conditions
if (!empty($from_date) && !empty($to_date)) {
    $attendance_query .= " AND DATE(final_attendance.first_in) BETWEEN ? AND ?";
    $attendance_query .= " GROUP BY DATE(final_attendance.first_in)";
    $stmt_attendance = $conn->prepare($attendance_query);
    $stmt_attendance->bind_param("iss", $user_id, $from_date, $to_date);
} elseif (!empty($from_date)) {
    $attendance_query .= " AND DATE(final_attendance.first_in) >= ?";
    $attendance_query .= " GROUP BY DATE(final_attendance.first_in)";
    $stmt_attendance = $conn->prepare($attendance_query);
    $stmt_attendance->bind_param("is", $user_id, $from_date);
} elseif (!empty($to_date)) {
    $attendance_query .= " AND DATE(final_attendance.first_in) <= ?";
    $attendance_query .= " GROUP BY DATE(final_attendance.first_in)";
    $stmt_attendance = $conn->prepare($attendance_query);
    $stmt_attendance->bind_param("is", $user_id, $to_date);
} else {
    $attendance_query .= " GROUP BY DATE(final_attendance.first_in)";
    $stmt_attendance = $conn->prepare($attendance_query);
    $stmt_attendance->bind_param("i", $user_id);
}

$stmt_attendance->execute();
$result = $stmt_attendance->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESCO | Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <?php include("include/header.php"); ?>
</head>

<body>
    <div class="container-fluid">
        <div class="text-center my-4">
            <img src="assest/images/MESCO.png" alt="MESCO LOGO" width="100px" class="my-3">
            <h2>Welcome <?php echo htmlspecialchars($username); ?></h2>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <form method="get" action="dashboard.php">
                    <div class="form-group">
                        <label for="from_date">From Date:</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label for="to_date">To Date:</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table id="attendanceTable" class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Employer ID</th>
                        <th>Full Name</th>
                        <th>Date</th>
                        <th>First Mode</th>
                        <th>Last Mode</th>
                        <th>From Where</th>
                        <th>First In</th>
                        <th>Last Out</th>
                        <th>Attendance Status</th>
                        <th>Total Hours Worked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['employer_id'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['full_name'] ?? '') . "</td>";
                        echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['first_mode']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_mode']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['data']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['first_in']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['last_out']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['attendance_status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['total_hours']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="text-center">
            <button class="btn btn-danger" onclick="document.location='logout.php'">Logout</button>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        window.onload = function() {
            // Hide the preloader
            document.querySelector(".preloader").style.display = "none";
        }
        $(document).ready(function() {
            $('#attendanceTable').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true // Enable responsiveness
            });
        });
    </script>
</body>

</html>
