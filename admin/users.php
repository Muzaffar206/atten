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
include("include/header.php");
include("include/topbar.php");
$activePage = 'employee';
include("include/sidebar.php");

// Fetch departments for filter dropdown
$sql_departments = "SELECT DISTINCT department FROM users WHERE deleted_at IS NULL";
$stmt_departments = $conn->prepare($sql_departments);
$stmt_departments->execute();
$result_departments = $stmt_departments->get_result();

// Fetch roles for filter dropdown
$sql_roles = "SELECT DISTINCT role FROM users WHERE deleted_at IS NULL";
$stmt_roles = $conn->prepare($sql_roles);
$stmt_roles->execute();
$result_roles = $stmt_roles->get_result();

$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

// Use prepared statements to prevent SQL injection
$sql = "SELECT * FROM users WHERE deleted_at IS NULL";
$params = [];
$types = '';
if (!empty($filter_department)) {
    $sql .= " AND department = ?";
    $params[] = $filter_department;
    $types .= 's';
}
if (!empty($filter_role)) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
    $types .= 's';
}
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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
                        <div class="card-header">
                            <?php // Display success message
                            if (isset($_SESSION['success_message'])) {
                                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8') . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>';
                                // Clear the message
                                unset($_SESSION['success_message']);
                            }

                            // Display error message
                            if (isset($_SESSION['error_message'])) {
                                echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>';
                                // Clear the message
                                unset($_SESSION['error_message']);
                            }
                            if (isset($_SESSION['message'])) {
                                echo '<div class="alert alert-success" role="alert">' . $_SESSION['message'] . '</div>';
                                // Unset the message after displaying
                                unset($_SESSION['message']);
                            } ?>
                            <div class="card-body">
                                <h1>Users</h1>
                                <div class="mb-3">
                                    <form method="get" action="">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="department">Filter by Department:</label>
                                                    <select class="form-control" id="department" name="department">
                                                        <option value="">All Departments</option>
                                                        <?php while ($row = $result_departments->fetch_assoc()) : ?>
                                                            <option value="<?php echo htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($filter_department == $row['department']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="role">Filter by Role:</label>
                                                    <select class="form-control" id="role" name="role">
                                                        <option value="">All Roles</option>
                                                        <?php while ($row = $result_roles->fetch_assoc()) : ?>
                                                            <option value="<?php echo htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($filter_role == $row['role']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'); ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>&nbsp;</label><br>
                                                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                                                    <a href="users.php" class="btn btn-secondary">Clear Filter</a>
                                                    <?php if (!empty($filter_department) || !empty($filter_role)) : ?>
                                                        <a href="export_filtered_data.php?department=<?php echo urlencode($filter_department); ?>&role=<?php echo urlencode($filter_role); ?>" class="btn btn-success">Download Filtered Data (XLS)</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="table-responsive">
                                    <table id="attendanceTable" class="table table-bordered table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Employee ID</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Phone Number</th>
                                                <th>Passport Size Photo</th>
                                                <th>Role</th>
                                                <th>Department</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($result->num_rows > 0) : ?>
                                                <?php while ($row = $result->fetch_assoc()) : ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['employer_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['phone_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td>
                                                            <?php
                                                            $imagePath = htmlspecialchars($row['passport_size_photo'], ENT_QUOTES, 'UTF-8');
                                                            if (file_exists($imagePath) && !empty($imagePath)) {
                                                                echo '<img src="' . $imagePath . '" width="50" height="50">';
                                                            } else {
                                                                echo 'NA';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td><?php echo htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                        <td>
                                                            <a class="btn btn-primary" href="edit_user.php?id=<?php echo urlencode($row['id']); ?>">Edit</a>
                                                            <a class="btn btn-danger" href="delete_user.php?id=<?php echo urlencode($row['id']); ?>" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else : ?>
                                                <tr>
                                                    <td colspan="10">No users found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button onclick="document.location='registration.php'" type="button" class="btn btn-primary">Add new User</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

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
