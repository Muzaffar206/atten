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


$sql = "SELECT 
            users.id AS user_id,
            attendance.id AS attendance_id, 
            users.employer_id,
            users.username, 
            users.full_name,
            attendance.mode, 
            attendance.latitude, 
            attendance.longitude,
            attendance.in_time,
            attendance.out_time, 
            attendance.selfie_in, 
            attendance.selfie_out
        FROM attendance 
        JOIN users ON attendance.user_id = users.id
        ORDER BY attendance.id DESC ";

$result = $conn->query($sql);

include("include/header.php");
include("include/topbar.php");
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
                <table id="attendanceTable" class="table table-bordered table-hover">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Emp id</th>
                      <th>Username</th>
                      <th>Fullname</th>
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
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . $row['attendance_id'] . "</td>
                                    <td>" . $row['employer_id'] . "</td>
                                    <td>" . $row['username'] . "</td>
                                    <td>" . $row['full_name'] . "</td>
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
                        echo "<tr><td colspan='10'>No records found</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
</div>

  
    <footer class="main-footer">
    <strong>Copyright &copy; 2024 <a href="https://outerinfo.online">Outerinfo</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0
    </div>
  </footer>

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
