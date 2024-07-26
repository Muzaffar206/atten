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
include("delete_old_selfies.php");





$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

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
        JOIN users ON attendance.user_id = users.id";

$whereClause = [];

if (!empty($filterDepartment)) {
    $whereClause[] = "users.department = '$filterDepartment'";
}

if (!empty($startDate) && !empty($endDate)) {
    $whereClause[] = "DATE(attendance.in_time) BETWEEN '$startDate' AND '$endDate'";
} elseif (!empty($startDate)) {
    $whereClause[] = "DATE(attendance.in_time) = '$startDate'";
}

if (!empty($whereClause)) {
    $sql .= " WHERE " . implode(" AND ", $whereClause);
}

$sql .= " ORDER BY attendance.id DESC ";

$result = $conn->query($sql);

// Fetch departments dynamically
$departmentsQuery = "SELECT DISTINCT department FROM users";
$departmentsResult = $conn->query($departmentsQuery);

include("include/header.php");
include("include/topbar.php");
$activePage = 'attendance_report';
include("include/sidebar.php");
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Attendance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">filter</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"></div>

                        <div class="card-body">
                            <div class="mb-3">
                                <form method="GET" action="">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="department">Department:</label>
                                                <select name="department" id="department" class="form-control">
                                                    <option value="">All Departments</option>
                                                    <?php while ($deptRow = $departmentsResult->fetch_assoc()) { ?>
                                                        <option value="<?php echo $deptRow['department']; ?>" <?php echo ($filterDepartment == $deptRow['department']) ? 'selected' : ''; ?>>
                                                            <?php echo $deptRow['department']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="start_date">Start Date:</label>
                                                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="end_date">End Date:</label>
                                                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label><br>
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                                <a href="report_xls.php?department=<?php echo urlencode($filterDepartment); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-success">Export XLS</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <?php displayAlert(); ?>
                                <form id="deleteSelfiesForm" method="POST" action="">
                                    <input type="hidden" name="delete_selfies" value="true">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selfies older than 2 minutes?')">Delete Selfies</button>
                                </form>
                                <?php
                                if (isset($_SESSION['success_message'])) {
                                    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                                    unset($_SESSION['success_message']);
                                }
                                if (isset($_SESSION['info_message'])) {
                                    echo '<div class="alert alert-info">' . $_SESSION['info_message'] . '</div>';
                                    unset($_SESSION['info_message']);
                                }
                                ?>
                            </div>
                            <div class="table-responsive">
                                <table id="attendanceTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Emp id</th>
                                            <th>Username</th>
                                            <th>Fullname</th>
                                            <th>Department</th>
                                            <th>Mode</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
                                            <th>In time</th>
                                            <th>Out time</th>
                                            <th>Selfie_in</th>
                                            <th>Selfie_out</th>
                                            <th>Map</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                // Remove 'admin/' from selfie paths
                                                $selfie_in = !empty($row['selfie_in']) ? str_replace('admin/', '', $row['selfie_in']) : '';
                                                $selfie_out = !empty($row['selfie_out']) ? str_replace('admin/', '', $row['selfie_out']) : '';
                                        
                                                echo "<tr>
                                                        <td>" . htmlspecialchars($row['attendance_id']) . "</td>
                                                        <td>" . htmlspecialchars($row['employer_id']) . "</td>
                                                        <td>" . htmlspecialchars($row['username']) . "</td>
                                                        <td>" . htmlspecialchars($row['full_name']) . "</td>
                                                        <td>" . htmlspecialchars($row['department']) . "</td>
                                                        <td>" . htmlspecialchars($row['mode']) . "</td>
                                                        <td>" . (!empty($row['latitude']) ? htmlspecialchars($row['latitude']) : 'N/A') . "</td>
                                                        <td>" . (!empty($row['longitude']) ? htmlspecialchars($row['longitude']) : 'N/A') . "</td>
                                                        <td>" . htmlspecialchars($row['in_time']) . "</td>
                                                        <td>" . htmlspecialchars($row['out_time']) . "</td>
                                                         <td>" . (!empty($selfie_in) ? '<img src="get_image.php?path=' . urlencode($selfie_in) . '" alt="Selfie_in" width="150" height="150">' : 'N/A') . "</td>
    <td>" . (!empty($selfie_out) ? '<img src="get_image.php?path=' . urlencode($selfie_out) . '" alt="Selfie_out" width="150" height="150">' : 'N/A') . "</td>
                                                        <td>" . (!empty($row['latitude']) && !empty($row['longitude']) ? '<a href="https://www.google.com/maps?q=' . htmlspecialchars($row['latitude']) . ',' . htmlspecialchars($row['longitude']) . '" target="_blank">View on Map</a>' : 'N/A') . "</td>
                                                      </tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='13'>No records found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include("include/footer.php"); ?>

<script>
    $(document).ready(function() {
        $('#attendanceTable').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
        });
    });
</script>