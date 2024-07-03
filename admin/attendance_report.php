<?php
session_start();
include("../assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$sql = "SELECT 
            users.id AS user_id, 
            users.employer_id,
            users.username, 
            users.full_name,
            attendance.mode, 
            attendance.latitude, 
            attendance.longitude,
            attendance.in_time,
            attendance.out_time, 
            attendance.selfie 
        FROM attendance 
        JOIN users ON attendance.user_id = users.id
        ORDER BY attendance.id DESC";
        

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
              <div class="card-header"></div>
   


      <div class="card-body">
                <table id="example2" class="table table-bordered table-hover">
                  <thead>
        <tr>
            <th>User ID</th>
            <th>Emp id</th>
            <th>Username</th>
            <th>Fullname</th>
            <th>Mode</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>In time</th>
            <th>Out time</th>
            <th>Selfie</th>
        </tr></thead>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['user_id'] . "</td>
                        <td>" . $row['employer_id'] . "</td>
                        <td>" . $row['username'] . "</td>
                        <td>" . $row['full_name'] . "</td>
                        <td>" . $row['mode'] . "</td>
                        <td>" . ($row['latitude'] ?? 'N/A') . "</td>
                        <td>" . ($row['longitude'] ?? 'N/A') . "</td>
                        <td>" . $row['in_time'] . "</td>
                        <td>" . $row['out_time'] . "</td>
                        <td>" . (!empty($row['selfie']) ? '<img src="data:image/jpeg;base64,' . base64_encode($row['selfie']) . '" alt="Selfie" width="150" height="150" >' : 'N/A') . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No records found</td></tr>";
        }
        ?>
                </table>


<?php    include("include/footer.php"); ?>
