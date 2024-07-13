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
$averageAttendance = $averageAttendanceRow['average_attendance'];

// Today's total present
$totalPresentTodayQuery = "SELECT COUNT(*) AS total_present_today FROM attendance WHERE DATE(in_time) = CURDATE()";
$totalPresentTodayResult = $conn->query($totalPresentTodayQuery);
$totalPresentTodayRow = $totalPresentTodayResult->fetch_assoc();
$totalPresentToday = $totalPresentTodayRow['total_present_today'];

// Total absent today
$totalAbsentToday = $totalEmployees - $totalPresentToday;
?>

  <!-- Content Wrapper. Contains page content -->
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
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Small boxes (Stat box) -->
        <div class="row">
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-info">
              <div class="inner">
                <h3><?php echo $totalEmployees; ?></h3>

                <p>Total employees</p>
              </div>
              <div class="icon">
                <i class="ion ion-person-add"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-success">
              <div class="inner">
                <h3><?php echo $averageAttendance; ?><sup style="font-size: 20px">%</sup></h3>

                <p>Average attendance</p>
              </div>
              <div class="icon">
                <i class="ion ion-stats-bars"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-warning">
              <div class="inner">
                <h3><?php echo $totalPresentToday; ?></h3>

                <p>Total present today</p>
              </div>
              <div class="icon">
                <i class="fas fa-map-marker"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
          <div class="col-lg-3 col-6">
            <!-- small box -->
            <div class="small-box bg-danger">
              <div class="inner">
                <h3><?php echo $totalAbsentToday; ?></h3>

                <p>Total Absent Today</p>
              </div>
              <div class="icon">
                <i class="fa fa-ban"></i>
              </div>
              <a href="#" class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></a>
            </div>
          </div>
          <!-- ./col -->
        </div>
        <!-- /.row -->
        <!-- Main row -->
        <div class="row">
        <section class="col-lg-7 connectedSortable">
          
        </section>
        </div>
        <!-- /.row (main row) -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 1.0
    </div>
    <strong>Copyright &copy; 2024 <a href="https://outerinfo.online">Outerinfo</a>.</strong> All rights reserved.
  </footer>

<?php
include("include/footer.php");
?>