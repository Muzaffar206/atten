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




$sql = "SELECT * FROM users";
$result = $conn->query($sql);

$conn->close();
?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Employee</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Employee</li>
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
                <h1>Users</h1>
                <table id="attendanceTable" class="table table-bordered table-hover">
                  <thead>
                <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Employer ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Passport Size Photo</th>
                <th>Address</th>
                <th>Role</th>
                <th>Department<th>
                </tr>
              </thead>
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['username']; ?></td>
                    <td><?php echo $row['employer_id']; ?></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['email']; ?></td>
                    <td><?php echo $row['phone_number']; ?></td>
                    <td><img src="<?php echo $row['passport_size_photo']; ?>" width="50" height="50"></td>
                    <td><?php echo $row['address']; ?></td>
                    <td><?php echo $row['role']; ?></td>
                    <td><?php echo $row['department']; ?></td>
                    <td>
                        <a class="btn btn-primary" href="edit_user.php?id=<?php echo $row['id']; ?>">Edit</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="9">No users found.</td>
            </tr>
        <?php endif; ?>
    </table>
    <button onclick="document.location='registration.php'" type="button" class="btn btn-primary">Add new User</button>
    </section>
    <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
    <footer class="main-footer">
    <strong>Copyright &copy; 2024 <a href="https://outerinfo.online">Outerinfo</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0
    </div>
  </footer>

<?php
include("include/footer.php");
?>
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