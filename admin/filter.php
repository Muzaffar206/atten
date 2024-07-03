<?php
session_start();
include("../assest/connection/config.php");
include("include/header.php");
include("include/topbar.php");
include("include/sidebar.php");


if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Fetch all users for the filter dropdown
$userSql = "SELECT id, username FROM users";
$userResult = $conn->query($userSql);

$users = [];
if ($userResult->num_rows > 0) {
    while ($userRow = $userResult->fetch_assoc()) {
        $users[] = $userRow;
    }
}

// Handle filter form submission
$filterUser = isset($_GET['user']) ? intval($_GET['user']) : 0;
$department = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$filterSql = "SELECT users.id AS user_id, users.username, DATE(attendance.in_time) as date, 
                attendance.in_time, attendance.out_time, users.department 
                FROM attendance 
                JOIN users ON attendance.user_id = users.id 
                WHERE 1=1";

if ($filterUser > 0) {
    $filterSql .= " AND users.id = $filterUser";
}

if (!empty($department)) {
    $filterSql .= " AND users.department = '$department'";
}

if (!empty($startDate) && !empty($endDate)) {
    $filterSql .= " AND DATE(attendance.in_time) BETWEEN '$startDate' AND '$endDate'";
} elseif (!empty($startDate)) {
    $filterSql .= " AND DATE(attendance.in_time) = '$startDate'";
}

$filterSql .= " ORDER BY DATE(attendance.in_time) DESC";

$filterResult = $conn->query($filterSql);

// Function to sanitize CSV data
function sanitizeCsvField($value) {
    // Escape double quotes
    $escaped = str_replace('"', '""', $value);
    // Enclose in double quotes
    return '"' . $escaped . '"';
}

// Export CSV functionality
if (isset($_GET['export_csv'])) {
    $filename = "filtered_attendance.csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    // Write CSV headers
    fputcsv($output, ['User ID', 'Username', 'Department', 'Date', 'In Time', 'Out Time']);

    // Write CSV rows
    if ($filterResult->num_rows > 0) {
        while ($row = $filterResult->fetch_assoc()) {
            fputcsv($output, [
                $row['user_id'],
                $row['username'],
                $row['department'],
                $row['date'],
                $row['in_time'],
                $row['out_time']
            ]);
        }
    }

    fclose($output);
    exit;
}

$conn->close();
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
              <form method="GET" action="">
        <label for="user">User:</label>
        <select name="user" id="user">
            <option value="0">All Users</option>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>" <?php echo ($filterUser == $user['id']) ? 'selected' : ''; ?>>
                    <?php echo $user['username']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="department">Department:</label>
        <select name="department" id="department" >
            <option value="">All Departments</option>
            <option value="ROP" <?php echo ($department == 'ROP') ? 'selected' : ''; ?>>ROP</option>
            <option value="Admin" <?php echo ($department == 'Admin') ? 'selected' : ''; ?>>Admin</option>
            <option value="Clinics" <?php echo ($department == 'Clinics') ? 'selected' : ''; ?>>Clinics</option>
        </select>

        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" id="start_date" value="<?php echo $startDate; ?>">

        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" id="end_date" value="<?php echo $endDate; ?>">

        <button type="submit" class="btn btn-primary">Filter</button>
        <button type="submit" class="btn btn-success" name="export_csv" value="1">Export CSV</button>
    </form>
    

              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example2" class="table table-bordered table-hover">
                  <thead>
                <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Department</th>
            <th>Date</th>
            <th>In Time</th>
            <th>Out Time</th>
        </tr></thead>
        <?php
        if ($filterResult->num_rows > 0) {
            while ($row = $filterResult->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['user_id'] . "</td>
                        <td>" . $row['username'] . "</td>
                        <td>" . $row['department'] . "</td>
                        <td>" . $row['date'] . "</td>
                        <td>" . $row['in_time'] . "</td>
                        <td>" . $row['out_time'] . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No records found</td></tr>";
        }
        ?>
        
    </table>
    
    </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
    <button class="btn btn-block btn-danger btn-sm" onclick="document.location='../logout.php'">Logout</button>

          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

    <footer class="main-footer">
    <strong>Copyright &copy; 2024 <a href="https://outerinfo.online">Outerinfo</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0
    </div>
  </footer>
  </div>
  
    <?php
include("include/footer.php");
?>