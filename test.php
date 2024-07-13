<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}
include("../assest/connection/config.php");
include("include/header.php");
include("include/topbar.php");
include("include/sidebar.php");


// Get form inputs for department and date range
$department = isset($_POST['department']) ? $_POST['department'] : 'All';
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-01'); // Default to the first day of the current month
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d'); // Default to today

// Fetch users based on department selection
if ($department === 'All') {
    $users_query = "SELECT id, employer_id, full_name, department FROM users";
    $stmt_users = $conn->prepare($users_query);
} else {
    $users_query = "SELECT id, employer_id, full_name, department FROM users WHERE department = ?";
    $stmt_users = $conn->prepare($users_query);
    $stmt_users->bind_param("s", $department);
}
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
              <div class="card-header">
    <form method="post" action="">
        <label for="department">Select Department:</label>
        <select name="department" id="department">
            <option value="All">All Departments</option>
            <option value="Education">Education</option>
            <option value="Medical">Medical</option>
            <option value="ROP">ROP</option>
            <option value="Admin">Admin</option>
            <option value="Clinics">Clinics</option>
            <!-- Add more options based on your departments -->
        </select>
        <label for="from_date">From Date:</label>
        <input type="date" id="from_date" name="from_date" value="<?php echo $from_date; ?>">
        <label for="to_date">To Date:</label>
        <input type="date" id="to_date" name="to_date" value="<?php echo $to_date; ?>">
        <input type="submit" value="Show Data">
        <input type="submit" name="download_csv" value="Download CSV">
    </form>
    <br>

    </div>
              <!-- /.card-header -->
              <div class="card-body">
    <?php if ($users_result->num_rows > 0): ?>
        <table id="attendanceTable" class="table table-bordered table-hover">
                  <thead>
                <th>Department</th>
                <th>Employer Code</th>
                <th>Employer Name</th>
                <?php foreach ($dates as $date): ?>
                    <th><?php echo $date; ?></th>
                <?php endforeach; ?>
                </thead>
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
