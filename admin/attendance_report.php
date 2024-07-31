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





$filterDepartment = isset($_GET['department']) ? htmlspecialchars($_GET['department']) : '';
$startDate = isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date']) : '';


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
                                <div class="row">
                                    <div class="col-auto">
                                        <!-- Delete Selfies Form -->
                                        <form id="deleteSelfiesForm" method="POST" action="">
                                            <input type="hidden" name="delete_selfies" value="true">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selfies older than 2 minutes?')">Delete Selfies</button>
                                        </form>
                                    </div>
                                    <div class="col-auto">
                                        <!-- Download Selfies Form -->
                                        <form action="generate_selfies_zip.php" method="post">
                                            <button type="submit" class="btn btn-primary">Download All Selfies</button>
                                        </form>
                                    </div>
                                </div>
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
    var table = $('#attendanceTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "get_attendance_data.php",
            "type": "POST",
            "data": function(d) {
                d.department = $('#department').val();
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
            }
        },
        "columns": [
            { "data": "attendance_id" },
            { "data": "employer_id" },
            { "data": "username" },
            { "data": "full_name" },
            { "data": "department" },
            { "data": "mode" },
            { "data": "latitude" },
            { "data": "longitude" },
            { "data": "in_time" },
            { "data": "out_time" },
            { "data": "selfie_in" },
            { "data": "selfie_out" },
            { "data": "map" }
        ],
        "pageLength": 10,
        "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
        "order": [[0, "desc"]]
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });
});
</script>