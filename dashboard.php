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
$from_date = isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : date('Y-m-d');

// Fetch attendance data
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
                        users.department,
                        final_attendance.total_hours
                    FROM final_attendance 
                    JOIN users ON final_attendance.user_id = users.id 
                    LEFT JOIN attendance ON attendance.user_id = users.id AND DATE(attendance.in_time) = DATE(final_attendance.first_in) 
                    WHERE users.id = ? AND DATE(final_attendance.first_in) BETWEEN ? AND ?
                    GROUP BY 
                        users.id, 
                        users.username, 
                        users.employer_id, 
                        users.full_name, 
                        DATE(final_attendance.first_in), 
                        users.department,
                        final_attendance.total_hours";

$stmt_attendance = $conn->prepare($attendance_query);
$stmt_attendance->bind_param("iss", $user_id, $from_date, $to_date);
$stmt_attendance->execute();
$result = $stmt_attendance->get_result();

?>
   <?php
   $pageTitle = 'Dashboard'; 
   $pageDescription = 'View and manage your attendance records with MESCO Attendance System dashboard.';
   include("include/header.php");
   ?>

    <div class="app-container">
        <div class="content-wrapper">
            <h2 class="mt-4 mb-4">Your Attendance</h2>
            <form method="get" action="dashboard.php" class="mb-4">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="from_date">From Date:</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($from_date); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="to_date">To Date:</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($to_date); ?>">
                    </div>
                    <div class="form-group col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table id="attendanceTable" class="table table-bordered table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>Date</th>
                            <th>First Mode</th>
                            <th>Last Mode</th>
                            <th>From Where</th>
                            <th>First In</th>
                            <th>Last Out</th>
                            <th>Total Hours Worked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['date']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['first_mode']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['last_mode']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['data']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['first_in']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['last_out']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['total_hours']) . "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <nav class="bottom-navbar">
            <ul class="nav nav-justified">
                <li class="nav-item">
                    <a class="nav-link" href="home.php">
                        <i class="fas fa-home"></i>
                        <span class="d-block">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="d-block">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i>
                        <span class="d-block">Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-block">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>


    <?php include("include/footer.php"); ?>
</body>

</html>

<?php
$stmt_attendance->close();
$conn->close();
?>
