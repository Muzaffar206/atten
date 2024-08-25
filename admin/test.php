<?php
session_start();
session_regenerate_id(true);

date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

// Redirect if user is not logged in or is not an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}

include("../assest/connection/config.php");
include("delete_old_selfies.php");

// Get filter and pagination parameters
$filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$entriesPerPage = isset($_GET['entries_per_page']) ? (int)$_GET['entries_per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $entriesPerPage;

// Fetch departments dynamically
$departmentsQuery = "SELECT DISTINCT department FROM users";
$departmentsResult = $conn->query($departmentsQuery);

// Prepare SQL query for total records
$totalCountQuery = "SELECT COUNT(*) AS total 
                    FROM attendance 
                    JOIN users ON attendance.user_id = users.id 
                    WHERE users.role <> 'admin'";

// Prepare the query dynamically based on filters
$params = [];
$types = '';

if (!empty($filterDepartment)) {
    $totalCountQuery .= " AND users.department = ?";
    $params[] = $filterDepartment;
    $types .= 's';
}
if (!empty($startDate) && !empty($endDate)) {
    $totalCountQuery .= " AND DATE(attendance.in_time) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
} elseif (!empty($startDate)) {
    $totalCountQuery .= " AND DATE(attendance.in_time) = ?";
    $params[] = $startDate;
    $types .= 's';
}
if (!empty($searchQuery)) {
    $totalCountQuery .= " AND (users.username LIKE ? OR users.full_name LIKE ? OR attendance.mode LIKE ?)";
    $searchQuery = "%$searchQuery%";
    $params[] = $searchQuery;
    $params[] = $searchQuery;
    $params[] = $searchQuery;
    $types .= 'sss';
}

// Prepare and execute the total count query
$totalCountStmt = $conn->prepare($totalCountQuery);
if ($types) {
    $totalCountStmt->bind_param($types, ...$params);
}
$totalCountStmt->execute();
$totalCountResult = $totalCountStmt->get_result();
$totalCountRow = $totalCountResult->fetch_assoc();
$totalRecords = $totalCountRow['total'];
$totalCountStmt->close();

// Prepare SQL query for paginated results
$sql = "SELECT 
            attendance.id AS attendance_id,
            users.employer_id,
            users.username,
            users.full_name,
            users.department,
            attendance.mode,
            attendance.latitude,
            attendance.longitude,
            attendance.in_time,
            attendance.out_time,
            attendance.selfie_in,
            attendance.selfie_out,
            attendance.data
        FROM attendance
        JOIN users ON attendance.user_id = users.id
        WHERE users.role <> 'admin'";

// Prepare the query dynamically based on filters
$params = [];
$types = '';

if (!empty($filterDepartment)) {
    $sql .= " AND users.department = ?";
    $params[] = $filterDepartment;
    $types .= 's';
}
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND DATE(attendance.in_time) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= 'ss';
} elseif (!empty($startDate)) {
    $sql .= " AND DATE(attendance.in_time) = ?";
    $params[] = $startDate;
    $types .= 's';
}
if (!empty($searchQuery)) {
    $sql .= " AND (users.username LIKE ? OR users.full_name LIKE ? OR attendance.mode LIKE ?)";
    $searchQuery = "%$searchQuery%";
    $params[] = $searchQuery;
    $params[] = $searchQuery;
    $params[] = $searchQuery;
    $types .= 'sss';
}

$sql .= " ORDER BY attendance.id DESC LIMIT ? OFFSET ?";

// Prepare and execute the paginated query
$stmt = $conn->prepare($sql);
$params[] = $entriesPerPage;
$params[] = $offset;
$types .= 'ii';

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Include HTML headers
include("include/header.php");
include("include/topbar.php");
$activePage = 'attendance_report';
include("include/sidebar.php");
?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Attendance</h1>
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

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"></div>

                        <div class="card-body">
                            <div class="mb-3">

                                <form method="GET" action="" class="mb-3">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="department">Department:</label>
                                                <select name="department" id="department" class="form-control">
                                                    <option value="">All Departments</option>
                                                    <?php while ($deptRow = $departmentsResult->fetch_assoc()) { ?>
                                                        <option value="<?php echo $deptRow['department']; ?>" <?php echo ($filterDepartment == $deptRow['department']) ? 'selected' : ''; ?>>
                                                            <?php echo $deptRow['department']; ?>
                                                        </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="start_date">Start Date:</label>
                                                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="end_date">End Date:</label>
                                                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="entries_per_page">Entries per Page:</label>
                                                <select name="entries_per_page" id="entries_per_page" class="form-control">
                                                    <option value="10" <?php echo ($entriesPerPage == 10) ? 'selected' : ''; ?>>10</option>
                                                    <option value="25" <?php echo ($entriesPerPage == 25) ? 'selected' : ''; ?>>25</option>
                                                    <option value="50" <?php echo ($entriesPerPage == 50) ? 'selected' : ''; ?>>50</option>
                                                    <option value="100" <?php echo ($entriesPerPage == 100) ? 'selected' : ''; ?>>100</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label><br>
                                                <button type="submit" class="btn btn-primary">Apply Date Filters</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                                <form method="GET" action="">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="search">Search:</label>
                                                <input type="text" name="search" id="search" class="form-control" value="<?php echo $searchQuery; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>&nbsp;</label><br>
                                                <button type="submit" class="btn btn-primary">Search</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <?php displayAlert(); ?>
                                <div class="row">
                                    <div class="col-auto">
                                        <!-- Delete Selfies Form -->
                                        <form id="deleteSelfiesForm" method="POST" action="">
                                            <input type="hidden" name="delete_selfies" value="true">
                                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete selfies older than 2 minutes?')">Delete Selfies</button>
                                        </form>
                                    </div>
                                    <div class="col-auto">
                                        <!-- Download Selfies Form -->
                                        <form action="generate_selfies_zip.php" method="post">
                                            <button type="submit" class="btn btn-primary">Download All Selfies</button>
                                        </form>
                                    </div>
                                </div>

                                <?php
                                if (isset($_SESSION['success_message'])) {
                                    echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                                    unset($_SESSION['success_message']);
                                }
                                if (isset($_SESSION['info_message'])) {
                                    echo '<div class="alert alert-info">' . $_SESSION['info_message'] . '</div>';
                                    unset($_SESSION['info_message']);
                                }
                                ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employer ID</th>
                                            <th>Username</th>
                                            <th>Full Name</th>
                                            <th>Department</th>
                                            <th>Mode</th>
                                            <th>Where</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
                                            <th>In Time</th>
                                            <th>Out Time</th>
                                            <th>Selfie In</th>
                                            <th>Selfie Out</th>
                                            <th>Map</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($result->num_rows > 0) {
                                            while ($row = $result->fetch_assoc()) {
                                                $latitude = !empty($row['latitude']) ? $row['latitude'] : 'NA';
                                                $longitude = !empty($row['longitude']) ? $row['longitude'] : 'NA';
                                        ?>
                                                <tr>
                                                    <td><?php echo $row['attendance_id']; ?></td>
                                                    <td><?php echo $row['employer_id']; ?></td>
                                                    <td><?php echo $row['username']; ?></td>
                                                    <td><?php echo $row['full_name']; ?></td>
                                                    <td><?php echo $row['department']; ?></td>
                                                    <td><?php echo $row['mode']; ?></td>
                                                    <td><?php echo $row['data']; ?></td>
                                                    <td><?php echo $latitude; ?></td>
                                                    <td><?php echo $longitude; ?></td>
                                                    <td><?php echo $row['in_time']; ?></td>
                                                    <td><?php echo $row['out_time']; ?></td>
                                                    <td>
                                                        <?php
                                                        $selfieInPath = $row['selfie_in'];
                                                        $relativeSelfieInPath = str_replace('C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/', '', $selfieInPath);
                                                        $imageInSrc = 'Selfies_in&out/' . htmlspecialchars($relativeSelfieInPath);
                                                        if (file_exists($imageInSrc)) : ?>
                                                            <img src="<?php echo $imageInSrc; ?>" alt="Selfie In" width="70" height="70">
                                                        <?php else : ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $selfieOutPath = $row['selfie_out'];
                                                        $relativeSelfieOutPath = str_replace('C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/', '', $selfieOutPath);
                                                        $imageOutSrc = 'Selfies_in&out/' . htmlspecialchars($relativeSelfieOutPath);
                                                        if (file_exists($imageOutSrc)) : ?>
                                                            <img src="<?php echo $imageOutSrc; ?>" alt="Selfie Out" width="70" height="70">
                                                        <?php else : ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>

                                                    <td>
                                                        <?php if (!empty($row['latitude']) && !empty($row['longitude'])) : ?>
                                                            <a href="https://maps.google.com/?q=<?php echo htmlspecialchars($row['latitude']); ?>,<?php echo htmlspecialchars($row['longitude']); ?>" target="_blank">View Map</a>
                                                        <?php else : ?>
                                                            N/A
                                                        <?php endif; ?>
                                                    </td>

                                                </tr>
                                        <?php
                                            }
                                        } else {
                                            echo '<tr><td colspan="12">No records found</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th>ID</th>
                                            <th>Employer ID</th>
                                            <th>Username</th>
                                            <th>Full Name</th>
                                            <th>Department</th>
                                            <th>Mode</th>
                                            <th>Latitude</th>
                                            <th>Longitude</th>
                                            <th>In Time</th>
                                            <th>Out Time</th>
                                            <th>Selfie In</th>
                                            <th>Selfie Out</th>
                                            <th>Map</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="pagination">
                                <!-- Pagination -->
                                <?php
                                $totalPages = ceil($totalRecords / $entriesPerPage);
                                $baseUrl = $_SERVER['PHP_SELF'] . '?';
                                $filterParams = http_build_query([
                                    'department' => $filterDepartment,
                                    'start_date' => $startDate,
                                    'end_date' => $endDate,
                                    'search' => $searchQuery,
                                    'entries_per_page' => $entriesPerPage
                                ]);

                                echo '<nav aria-label="Page navigation">';
                                echo '<ul class="pagination">';
                                if ($page > 1) {
                                    echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($page - 1) . '&' . $filterParams . '">Previous</a></li>';
                                }
                                for ($i = 1; $i <= $totalPages; $i++) {
                                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '&' . $filterParams . '">' . $i . '</a></li>';
                                }
                                if ($page < $totalPages) {
                                    echo '<li class="page-item"><a class="page-link" href="' . $baseUrl . 'page=' . ($page + 1) . '&' . $filterParams . '">Next</a></li>';
                                }
                                echo '</ul>';
                                echo '</nav>';
                                ?>
                            </div>

                            <div class="text-center mt-3">
                                <p>Total Records: <?php echo $totalRecords; ?> | Showing: <?php echo min($entriesPerPage * $page, $totalRecords); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
include("include/footer.php");
?>
//CREATE TABLE holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(255) NOT NULL
);
