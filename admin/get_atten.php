<?php
session_start();
session_regenerate_id(true);
include("../assest/connection/config.php");
include("include/header.php");
include("include/topbar.php");
$activePage = 'monthly_attendance';
include("include/sidebar.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}

// Fetch distinct departments from users table
$departments_query = "SELECT DISTINCT department FROM users";
$result_departments = $conn->query($departments_query);

// Check if query was successful
if (!$result_departments) {
    die('Error fetching departments: ' . $conn->error);
}

$department = isset($_POST['department']) ? $_POST['department'] : 'All';
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-01');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

$to_date_adjusted = date('Y-m-d', strtotime($to_date . ' +1 day'));

$users_query = ($department === 'All') ?
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE fa.first_in >= ? AND fa.first_in < ?
     GROUP BY u.id" :
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE u.department = ? AND fa.first_in >= ? AND fa.first_in < ?
     GROUP BY u.id";

$stmt_users = $conn->prepare($users_query);
if ($department === 'All') {
    $stmt_users->bind_param("ss", $from_date, $to_date_adjusted);
} else {
    $stmt_users->bind_param("sss", $department, $from_date, $to_date_adjusted);
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
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Filtered Attendance</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Filter</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="department">Select Department:</label>
                                            <select name="department" id="department" class="form-control">
                                                <option value="All" <?php echo ($department === 'All') ? 'selected' : ''; ?>>All Departments</option>
                                                <?php while ($row = $result_departments->fetch_assoc()) : ?>
                                                    <option value="<?php echo $row['department']; ?>" <?php echo ($department === $row['department']) ? 'selected' : ''; ?>><?php echo $row['department']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="from_date">From Date:</label>
                                            <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="to_date">To Date:</label>
                                            <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>&nbsp;</label><br>
                                            <button type="submit" value="Show Data" class="btn btn-primary">Show data</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <?php if ($users_result->num_rows > 0) : ?>
                                <form method="post" action="download_xls.php">
                                    <input type="hidden" name="department" value="<?php echo $department; ?>">
                                    <input type="hidden" name="from_date" value="<?php echo $from_date; ?>">
                                    <input type="hidden" name="to_date" value="<?php echo $to_date; ?>">
                                    <button type="submit" class="btn btn-success">Download XLS</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <!-- /.card-header -->

                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="attendanceTable" class="table table-bordered table-hover">
                                    <thead id="sticky-header">
                                        <tr>
                                            <th>Department</th>
                                            <th>Employer Code</th>
                                            <th>Employer Name</th>
                                            <?php foreach ($dates as $date) : ?>
                                                <th style="min-width: 45px;"><?php echo $date; ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($user = $users_result->fetch_assoc()) : ?>
                                            <tr>
                                                <td><?php echo $user['department']; ?></td>
                                                <td><?php echo $user['employer_id']; ?></td>
                                                <td><?php echo $user['full_name']; ?></td>
                                                <?php foreach ($dates as $date) : ?>
                                                    <td>
                                                        <?php
                                                        $attendance_date = DateTime::createFromFormat('d-m-Y', $date);
                                                        $day_of_week = $attendance_date->format('w');
                                                        if ($day_of_week == 0) {
                                                            echo "Holiday";
                                                        } else {
                                                            $attendance_query = "SELECT fa.first_in, fa.last_out, fa.first_mode, fa.last_mode,fa.total_hours, a.is_present, a.data 
                     FROM final_attendance fa
                     LEFT JOIN attendance a ON fa.user_id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
                     WHERE fa.user_id = ? AND DATE(fa.first_in) = ?";
                                                            $stmt_attendance = $conn->prepare($attendance_query);
                                                            $formatted_date = date('Y-m-d', strtotime($date));
                                                            $stmt_attendance->bind_param("is", $user['id'], $formatted_date);
                                                            $stmt_attendance->execute();
                                                            $attendance_result = $stmt_attendance->get_result();

                                                            if ($attendance_result->num_rows > 0) {
                                                                $attendance_data = $attendance_result->fetch_assoc();
                                                                echo $user['data'] . "<br>";
                                                                echo "Status: " . ($attendance_data['is_present'] ? "Present" : "Absent") . "<br>";
                                                                echo "First In: " . date('H:i:s', strtotime($attendance_data['first_in'])) . "<br>";
                                                                echo "First Mode: " . $attendance_data['first_mode'] . "<br>";
                                                                if ($attendance_data['last_out'] != null) {
                                                                    echo "Last Out: " . date('H:i:s', strtotime($attendance_data['last_out'])) . "<br>";
                                                                    echo "Last Mode: " . $attendance_data['last_mode'] . "<br>";
                                                                    echo "Total hours: " . $attendance_data['total_hours'] . "<br>";
                                                                }
                                                            } else {
                                                                echo "Absent";
                                                            }
                                                            $stmt_attendance->close();
                                                        }
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- /.table-responsive -->
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?php
include("include/footer.php");
$conn->close();
?>
<script>
    $(document).ready(function() {
        $('#attendanceTable').DataTable({
            "scrollX": true,
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true
        });
    });
</script>