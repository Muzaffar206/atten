<?php
// Check if a session hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID if needed
if (!isset($_SESSION['last_regeneration']) || time() - $_SESSION['last_regeneration'] >= 60) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Rest of your session checks
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}

// Include other files and continue with your script
include_once("../assest/connection/config.php");
include_once("include/header.php");
include_once("include/topbar.php");
$activePage = 'home';
include_once("include/sidebar.php");

// Total employees
$totalEmployeesQuery = "SELECT COUNT(*) AS total_employees FROM users WHERE role = 'user'";
$totalEmployeesResult = $conn->query($totalEmployeesQuery);
$totalEmployeesRow = $totalEmployeesResult->fetch_assoc();
$totalEmployees = $totalEmployeesRow['total_employees'];

// Average attendance
$averageAttendanceQuery = "SELECT ROUND(AVG(present_count), 2) AS average_attendance
FROM (
    SELECT COUNT(*) AS present_count
    FROM attendance
    WHERE in_time IS NOT NULL
    GROUP BY DATE(in_time)
) AS daily_attendance";
$averageAttendanceResult = $conn->query($averageAttendanceQuery);
$averageAttendanceRow = $averageAttendanceResult->fetch_assoc();
$averageAttendance = isset($averageAttendanceRow['average_attendance']) ? $averageAttendanceRow['average_attendance'] : 0;

// Today's total present
$totalPresentTodayQuery = "
    SELECT COUNT(DISTINCT user_id) AS total_present_today
    FROM final_attendance
    WHERE DATE(first_in) = CURDATE()
";
$totalPresentTodayResult = $conn->query($totalPresentTodayQuery);
$totalPresentTodayRow = $totalPresentTodayResult->fetch_assoc();
$totalPresentToday = $totalPresentTodayRow['total_present_today'];

// Get names of present employees
$presentEmployeesQuery = "
    SELECT DISTINCT user_id
    FROM final_attendance
    WHERE DATE(first_in) = CURDATE()
";
$presentEmployeesResult = $conn->query($presentEmployeesQuery);

$presentEmployees = [];
while ($row = $presentEmployeesResult->fetch_assoc()) {
    $userQuery = "SELECT full_name FROM users WHERE id = " . $row['user_id'];
    $userResult = $conn->query($userQuery);
    if ($userRow = $userResult->fetch_assoc()) {
        $presentEmployees[] = $userRow['full_name'];
    }
}


// Get names of absent employees
$absentEmployeesQuery = "
    SELECT full_name
    FROM users
    WHERE id NOT IN (
        SELECT user_id
        FROM final_attendance
        WHERE DATE(first_in) = CURDATE()
    ) AND role = 'user'
";
$absentEmployeesResult = $conn->query($absentEmployeesQuery);
$absentEmployees = [];
while ($row = $absentEmployeesResult->fetch_assoc()) {
    $absentEmployees[] = $row['full_name'];
}

// Total absent today
$totalAbsentToday = $totalEmployees - $totalPresentToday;

// Monthly attendance data
$monthlyAttendanceData = array();
$monthlyAttendanceQuery = "
    SELECT DATE_FORMAT(in_time, '%Y-%m') AS month_year, 
           COUNT(DISTINCT DATE(in_time)) AS attendance_count
    FROM attendance
    WHERE in_time IS NOT NULL
    GROUP BY DATE_FORMAT(in_time, '%Y-%m')
";
$monthlyAttendanceResult = $conn->query($monthlyAttendanceQuery);
while ($row = $monthlyAttendanceResult->fetch_assoc()) {
  $monthlyAttendanceData[$row['month_year']] = $row['attendance_count'];
}

// Yearly attendance data (average present and absent)
$yearlyAttendanceData = array();
$yearlyAttendanceQuery = "SELECT DATE_FORMAT(in_time, '%Y-%m') AS month_year,
                                 SUM(CASE WHEN present_count = 0 THEN 1 ELSE 0 END) AS absent_count,
                                 SUM(CASE WHEN present_count > 0 THEN 1 ELSE 0 END) AS present_count
                          FROM (
                              SELECT DATE(in_time) AS in_time,
                                     COUNT(*) AS present_count
                              FROM attendance
                              WHERE in_time IS NOT NULL
                              GROUP BY DATE(in_time)
                          ) AS daily_attendance
                          GROUP BY DATE_FORMAT(in_time, '%Y-%m')";
$yearlyAttendanceResult = $conn->query($yearlyAttendanceQuery);
while ($row = $yearlyAttendanceResult->fetch_assoc()) {
  $yearlyAttendanceData[$row['month_year']] = array(
    'present_count' => $row['present_count'],
    'absent_count' => $row['absent_count']
  );
}

$daysAgo = 15; // Updated to 15 days
$recentAttendanceQuery = "
    SELECT DATE(first_in) AS date, 
           COUNT(DISTINCT user_id) AS present_count,
           (SELECT COUNT(*) 
            FROM users 
            WHERE role = 'user') - COUNT(DISTINCT user_id) AS absent_count
    FROM final_attendance
    WHERE DATE(first_in) >= CURDATE() - INTERVAL $daysAgo DAY
    GROUP BY DATE(first_in)
    ORDER BY DATE(first_in) DESC
";
$recentAttendanceResult = $conn->query($recentAttendanceQuery);

$recentAttendanceData = array();
while ($row = $recentAttendanceResult->fetch_assoc()) {
    $recentAttendanceData[$row['date']] = array(
        'present_count' => $row['present_count'],
        'absent_count' => $row['absent_count']
    );
}
?>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Dashboard</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <!-- Small boxes (Stat box) -->
      <div class="row">
        <!-- Total Employees -->
        <div class="col-lg-3 col-6">
          <!-- small box -->
          <div class="small-box bg-info">
            <div class="inner">
              <h3><?php echo $totalEmployees; ?></h3>
              <p>Total Employees</p>
            </div>
            <div class="icon">
              <i class="ion ion-person"></i>
            </div>
            <a href="users.php" class="small-box-footer">All Employees <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Present Today -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success">
            <div class="inner">
              <h3><?php echo $totalPresentToday; ?></h3>
              <p>Present Today</p>
            </div>
            <div class="icon">
              <i class="ion ion-checkmark-circled"></i>
            </div>
            <a href="#" class="small-box-footer" data-toggle="modal" data-target="#presentEmployeesModal">View Present <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Absent Today -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger">
            <div class="inner">
              <h3><?php echo $totalAbsentToday; ?></h3>
              <p>Absent Today</p>
            </div>
            <div class="icon">
              <i class="ion ion-close-circled"></i>
            </div>
            <a href="#" class="small-box-footer" data-toggle="modal" data-target="#absentEmployeesModal">View Absent <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>

        <!-- Average Attendance -->
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning">
            <div class="inner">
              <h3><?php echo $averageAttendance; ?>%</h3>
              <p>Average Attendance</p>
            </div>
            <div class="icon">
              <i class="ion ion-stats-bars"></i>
            </div>
            <a href="attendance_report.php" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
          </div>
        </div>
      </div>
      <!-- /.row -->
    </div><!-- /.container-fluid -->
  </section>

  <!-- Present Employees Modal -->
  <div class="modal fade" id="presentEmployeesModal" tabindex="-1" role="dialog" aria-labelledby="presentEmployeesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="presentEmployeesModalLabel">Present Employees</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (!empty($presentEmployees)): ?>
            <ul>
              <?php foreach ($presentEmployees as $employee): ?>
                <li><?php echo htmlspecialchars($employee); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>No employees are present today.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Absent Employees Modal -->
  <div class="modal fade" id="absentEmployeesModal" tabindex="-1" role="dialog" aria-labelledby="absentEmployeesModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="absentEmployeesModalLabel">Absent Employees</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (!empty($absentEmployees)): ?>
            <ul>
              <?php foreach ($absentEmployees as $employee): ?>
                <li><?php echo htmlspecialchars($employee); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <p>All employees are present today.</p>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>


      <div class="card">
          <div class="card-header">
            <h3 class="card-title">Last 9 Days Attendance</h3>
          </div>
          <div class="card-body">
            <canvas id="barChart" style="height: 380px;"></canvas>
          </div>
        </div>
    </div><!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Include jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Include Bootstrap 4 JS -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>

<!-- Script to initialize the line chart -->
<script>
  $(function() {
    // Data from PHP
    var monthlyData = <?php echo json_encode($monthlyAttendanceData); ?>;
    var yearlyData = <?php echo json_encode($yearlyAttendanceData); ?>;
    var recentData = <?php echo json_encode($recentAttendanceData); ?>;

    // Convert PHP data to Chart.js datasets format
    var months = Object.keys(monthlyData);
    var monthlyCounts = Object.values(monthlyData);

    // Last 5 Days data
    var recentDates = Object.keys(recentData);
    var recentPresents = recentDates.map(function(date) {
      return recentData[date]['present_count'];
    });
    var recentAbsents = recentDates.map(function(date) {
      return recentData[date]['absent_count'];
    });

    // Last 5 Days Attendance Chart
    var ctxBar = document.getElementById('barChart').getContext('2d');
    var barChart = new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: recentDates,
        datasets: [{
            label: 'Present',
            data: recentPresents,
            backgroundColor: '#28A745',
            borderColor: '#28A745',
            borderWidth: 1
          },
          {
            label: 'Absent',
            data: recentAbsents,
            backgroundColor: '#DC3545',
            borderColor: '#DC3545',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          xAxes: [{
            display: true,
            scaleLabel: {
              display: true,
              labelString: 'Date'
            }
          }],
          yAxes: [{
            display: true,
            scaleLabel: {
              display: true,
              labelString: 'Count'
            }
          }]
        }
      }
    });
  });
 </script>

<!-- Additional scripts as needed -->
</body>

</html>