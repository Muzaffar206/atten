<?php
session_start();
session_regenerate_id(true);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}
include("../assest/connection/config.php");

// Initialize variables for filters
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build SQL query with filters
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

// Add department filter
if (!empty($filterDepartment)) {
    $whereClause[] = "users.department = '$filterDepartment'";
}

// Add date range filter
if (!empty($startDate) && !empty($endDate)) {
    $whereClause[] = "DATE(attendance.in_time) BETWEEN '$startDate' AND '$endDate'";
} elseif (!empty($startDate)) {
    $whereClause[] = "DATE(attendance.in_time) = '$startDate'";
}

// Combine where clauses
if (!empty($whereClause)) {
    $sql .= " WHERE " . implode(" AND ", $whereClause);
}

$sql .= " ORDER BY attendance.id DESC ";

$result = $conn->query($sql);

include("include/header.php");
include("include/topbar.php");
$activePage = 'attendance_report';
include("include/sidebar.php");
?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
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
      </div><!-- /.container-fluid -->
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
                                        <option value="Education" <?php echo ($filterDepartment == 'Education') ? 'selected' : ''; ?>>Education</option>
                                        <option value="Medical" <?php echo ($filterDepartment == 'Medical') ? 'selected' : ''; ?>>Medical</option>
                                        <option value="ROP" <?php echo ($filterDepartment == 'ROP') ? 'selected' : ''; ?>>ROP</option>
                                        <option value="Admin" <?php echo ($filterDepartment == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="Accounts" <?php echo ($filterDepartment == 'Accounts') ? 'selected' : ''; ?>>Accounts</option>
                                        <option value="FRD" <?php echo ($filterDepartment == 'FRD') ? 'selected' : ''; ?>>FRD</option>
                                        <option value="Newspaper" <?php echo ($filterDepartment == 'Newspaper') ? 'selected' : ''; ?>>Newspaper</option>
                                        <option value="RC Mahim" <?php echo ($filterDepartment == 'RC Mahim') ? 'selected' : ''; ?>>RC Mahim</option>
                                        <option value="Study centre" <?php echo ($filterDepartment == 'Study centre') ? 'selected' : ''; ?>>Study centre</option>
                                        <option value="Clinics" <?php echo ($filterDepartment == 'Clinics') ? 'selected' : ''; ?>>Clinics</option>
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
                                    <a href="export_xls.php?department=<?php echo urlencode($filterDepartment); ?>&start_date=<?php echo urlencode($startDate); ?>&end_date=<?php echo urlencode($endDate); ?>" class="btn btn-success">Export XLS</a>
                                </div>
                            </div>
                        </div>
                    </form>
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
                                    echo "<tr>
                                            <td>" . $row['attendance_id'] . "</td>
                                            <td>" . $row['employer_id'] . "</td>
                                            <td>" . $row['username'] . "</td>
                                            <td>" . $row['full_name'] . "</td>
                                            <td>" . $row['department'] . "</td>
                                            <td>" . $row['mode'] . "</td>
                                            <td>" . ($row['latitude'] ?? 'N/A') . "</td>
                                            <td>" . ($row['longitude'] ?? 'N/A') . "</td>
                                            <td>" . $row['in_time'] . "</td>
                                            <td>" . $row['out_time'] . "</td>
                                            <td>" . (!empty($row['selfie_in']) ? '<img src="data:image/jpeg;base64,' . base64_encode($row['selfie_in']) . '" alt="Selfie_in" width="150" height="150" >' : 'N/A') . "</td>
                                            <td>" . (!empty($row['selfie_out']) ? '<img src="data:image/jpeg;base64,' . base64_encode($row['selfie_out']) . '" alt="Selfie_out" width="150" height="150" >' : 'N/A') . "</td>
                                            <td>" . (($row['latitude'] && $row['longitude']) ? '<a href="https://www.google.com/maps?q=' . $row['latitude'] . ',' . $row['longitude'] . '" target="_blank">View on Map</a>' : 'N/A') . "</td>
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
      </div><!-- /.container-fluid -->
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
