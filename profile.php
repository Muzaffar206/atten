<?php
session_start();
session_regenerate_id(true);

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("assest/connection/config.php");

$user_id = $_SESSION['user_id'];

// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch recent attendance records (10 entries)
$sql_attendance = "SELECT * FROM attendance WHERE user_id = ? ORDER BY in_time DESC LIMIT 10";
$stmt_attendance = $conn->prepare($sql_attendance);
$stmt_attendance->bind_param("i", $user_id);
$stmt_attendance->execute();
$result_attendance = $stmt_attendance->get_result();
$pageTitle = 'Profile'; 
$pageDescription = 'View and manage your MESCO Attendance System profile. Update personal information and view attendance history.';
include("include/header.php");
?>

    <div class="content-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#personal" data-toggle="tab">Personal Information</a></li>
                                <li class="nav-item"><a class="nav-link" href="#attendance" data-toggle="tab">Recent Attendance</a></li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="active tab-pane" id="personal">
                                    <h3 class="profile-username text-center mb-4"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                    <p class="text-muted text-center mb-4"><?php echo htmlspecialchars($user['department']); ?></p>
                                    <form class="form-horizontal">
                                        <div class="form-group">
                                            <label for="username">Username</label>
                                            <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="employer_id">Employee ID</label>
                                            <input type="text" class="form-control" id="employer_id" value="<?php echo htmlspecialchars($user['employer_id']); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="email">Email</label>
                                            <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                        </div>
                                        <div class="form-group">
                                            <label for="department">Department</label>
                                            <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($user['department']); ?>" readonly>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane" id="attendance">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>In Time</th>
                                                    <th>Out Time</th>
                                                    <th>Mode</th>
                                                    <th>Where</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($attendance = $result_attendance->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d', strtotime($attendance['in_time'])); ?></td>
                                                    <td><?php echo date('H:i:s', strtotime($attendance['in_time'])); ?></td>
                                                    <td><?php echo $attendance['out_time'] ? date('H:i:s', strtotime($attendance['out_time'])) : 'N/A'; ?></td>
                                                    <td><?php echo htmlspecialchars($attendance['mode']); ?></td>
                                                    <td><?php echo htmlspecialchars($attendance['data']); ?></td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="dashboard.php" class="btn btn-success">View More Attendance Records</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <nav class="bottom-navbar">
        <ul class="nav nav-justified">
            <li class="nav-item">
                <a class="nav-link" href="home.php">
                    <i class="fas fa-home"></i>
                    <span class="d-block">Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span class="d-block">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="profile.php">
                    <i class="fas fa-user"></i>
                    <span class="d-block">Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="d-block">Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
include("include/footer.php");
$conn->close();
?>