<?php
session_start();
session_regenerate_id(true);
include("../assest/connection/config.php");
include("include/header.php");
include("include/topbar.php");
$activePage = 'Report';
include("include/sidebar.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}
// Pagination
$entries_per_page = isset($_POST['entries']) ? intval($_POST['entries']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $entries_per_page;

// Search functionality
$search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';
$search_condition = '';
if (!empty($search)) {
    $search_condition = " AND (u.full_name LIKE '%$search%' OR u.employer_id LIKE '%$search%' OR u.department LIKE '%$search%')";
}

// Fetch distinct departments from users table
$departments_query = "SELECT DISTINCT department FROM users";
$result_departments = $conn->query($departments_query);

// Check if query was successful
if (!$result_departments) {
    die('Error fetching departments: ' . $conn->error);
}

$department = isset($_POST['department']) ? $_POST['department'] : 'All';
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-01');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

$to_date_adjusted = date('Y-m-d', strtotime($to_date . ' +1 day'));

// Modify the main query to include search and pagination
$users_query = ($department === 'All') ?
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin' $search_condition
     GROUP BY u.id
     LIMIT $offset, $entries_per_page" :
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE u.department = ? AND fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin' $search_condition
     GROUP BY u.id
     LIMIT $offset, $entries_per_page";

$stmt_users = $conn->prepare($users_query);
if ($department === 'All') {
    $stmt_users->bind_param("ss", $from_date, $to_date_adjusted);
} else {
    $stmt_users->bind_param("sss", $department, $from_date, $to_date_adjusted);
}
$stmt_users->execute();
$users_result = $stmt_users->get_result();

$dates = [];
$current_date = strtotime($from_date);
$end_date = strtotime($to_date);

while ($current_date <= $end_date) {
    $dates[] = date('d-m-Y', $current_date);
    $current_date = strtotime('+1 day', $current_date);
}
// Count total rows for pagination
$count_query = ($department === 'All') ?
    "SELECT COUNT(DISTINCT u.id) as total FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     WHERE fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin' $search_condition" :
    "SELECT COUNT(DISTINCT u.id) as total FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     WHERE u.department = ? AND fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin' $search_condition";

$stmt_count = $conn->prepare($count_query);
if ($department === 'All') {
    $stmt_count->bind_param("ss", $from_date, $to_date_adjusted);
} else {
    $stmt_count->bind_param("sss", $department, $from_date, $to_date_adjusted);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $entries_per_page);
$stmt_users->close();
?>
<style>
    .table-wrapper {
        position: relative;
    }
    .table-scroll {
        overflow: auto;
        margin-top: 20px; /* Space for the top scroll bar */
    }
    .table-scroll table {
        width: 100%;
    }
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 2;
        background-color: #f8f9fa; /* Adjust color as needed */
    }
    .sticky-col {
        position: sticky;
        left: 0;
        z-index: 1;
        background-color: #f8f9fa; /* Adjust color as needed */
    }
    .sticky-row {
        position: sticky;
        top: 48px; /* Adjust this value based on your header height */
        z-index: 1;
        background-color: #f8f9fa; /* Adjust color as needed */
    }
    .sticky-row .sticky-col {
        z-index: 3;
    }
    /* Custom scrollbar styles */
    .table-scroll::-webkit-scrollbar {
        height: 10px;
        width: 10px;
    }
    .table-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .table-scroll::-webkit-scrollbar-thumb {
        background: #888;
    }
    .table-scroll::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
</style>
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
                        <li class="breadcrumb-item active">Filter</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="department">Select Department:</label>
                                            <select name="department" id="department" class="form-control">
                                                <option value="All" <?php echo ($department === 'All') ? 'selected' : ''; ?>>All Departments</option>
                                                <?php while ($row = $result_departments->fetch_assoc()) : ?>
                                                    <option value="<?php echo $row['department']; ?>" <?php echo ($department === $row['department']) ? 'selected' : ''; ?>><?php echo $row['department']; ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="from_date">From Date:</label>
                                            <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="to_date">To Date:</label>
                                            <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                <div class="form-group">
                    <label for="entries">Entries per Page:</label>
                    <select name="entries" id="entries" class="form-control">
                        <option value="10" <?php echo $entries_per_page == 10 ? 'selected' : ''; ?>>10</option>
                        <option value="25" <?php echo $entries_per_page == 25 ? 'selected' : ''; ?>>25</option>
                        <option value="50" <?php echo $entries_per_page == 50 ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo $entries_per_page == 100 ? 'selected' : ''; ?>>100</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" class="form-control" value="<?php echo $search; ?>" placeholder="Search...">
                </div>
            </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>&nbsp;</label><br>
                                            <button type="submit" value="Show Data" class="btn btn-primary">Show Data</button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <?php if ($users_result->num_rows > 0) : ?>
                                <form method="post" action="get_atten_report_csv.php">
                                    <input type="hidden" name="department" value="<?php echo $department; ?>">
                                    <input type="hidden" name="from_date" value="<?php echo $from_date; ?>">
                                    <input type="hidden" name="to_date" value="<?php echo $to_date; ?>">
                                    <input type="hidden" name="search" value="<?php echo $search; ?>">
                                    <button type="submit" class="btn btn-success">Download CSV</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <!-- /.card-header -->
                         <div class="card-body">
                            <div class="table-responsive">
                            <div class="table-scroll">
                                <table id="attendanceTable" class="table table-bordered table-hover">
                                <thead>
                    <tr class="sticky-top">
                        <th class="sticky-col">Department</th>
                        <th class="sticky-col">Employee Code</th>
                        <th class="sticky-col">Employee Name</th>
                        <?php foreach ($dates as $date) : ?>
                            <th><?php echo $date; ?></th>
                        <?php endforeach; ?>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $row_count = 0;
                    while ($user = $users_result->fetch_assoc()) : 
                        $row_class = $row_count < 3 ? 'sticky-row' : '';
                        $row_count++;
                    ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td class="sticky-col"><?php echo $user['department']; ?></td>
                                                <td class="sticky-col"><?php echo $user['employer_id']; ?></td>
                                                <td class="sticky-col"><?php echo $user['full_name']; ?></td>
                                                <?php
                                                $total_absents = 0;
                                                $total_half_days = 0;
                                                $total_full_days = 0;
                                                $holiday = 0;
                                                
                                                foreach ($dates as $date) :
                                                    $attendance_date = DateTime::createFromFormat('d-m-Y', $date);
                                                    $day_of_week = $attendance_date->format('w');
                                                ?>
                                                    <td <?php if ($day_of_week == 0) echo 'style="background-color: #f0f0f0;"'; ?>>
                                                        <?php
                                                        if (isset($user_data[$date])) {
                                                            echo $user_data[$date] . "<br>";
                                                        } else {
                                                            echo "";
                                                        }
                                                
                                                        $attendance_query = "SELECT fa.first_in, fa.last_out, fa.first_mode, fa.last_mode, fa.total_hours, a.is_present 
                                                            FROM final_attendance fa
                                                            LEFT JOIN attendance a ON fa.user_id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
                                                            WHERE fa.user_id = ? AND DATE(fa.first_in) = ?";
                                                        $stmt_attendance = $conn->prepare($attendance_query);
                                                        $formatted_date = date('Y-m-d', strtotime($date));
                                                        $stmt_attendance->bind_param("is", $user['id'], $formatted_date);
                                                        $stmt_attendance->execute();
                                                        $attendance_result = $stmt_attendance->get_result();
                                                
                                                        if ($attendance_result->num_rows > 0) {
                                                            $attendance_data = $attendance_result->fetch_assoc();
                                                            echo "In: " . date('H:i:s', strtotime($attendance_data['first_in'])) . "<br>";
                                                            if ($attendance_data['last_out'] != null) {
                                                                echo "Out: " . date('H:i:s', strtotime($attendance_data['last_out'])) . "<br>";
                                                                
                                                                // Calculate attendance status based on total hours
                                                                if ($attendance_data['total_hours'] >= 6.5) {
                                                                    $total_full_days += 1; // Full day marked as 1
                                                                } else if ($attendance_data['total_hours'] > 0) {
                                                                    $total_half_days += 1; // Half day marked as 1
                                                                }
                                                            } else {
                                                                echo '<div style="background-color: #FFFF00;">No Out</div>';
                                                                $total_absents += 1; // Absents marked as 1
                                                            }
                                                        } else {
                                                            if ($day_of_week == 0) {
                                                                $holiday +=1;
                                                                echo "Holiday";
                                                            } else {
                                                                echo "Absent";
                                                                $total_absents += 1;
                                                            }
                                                        }
                                                
                                                        $stmt_attendance->close();
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                                
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                        </div>
                      

                    </div>
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php
                        $start_page = max(1, $page - 1);
                        $end_page = min($total_pages, $start_page + 2);
                        
                        if ($end_page - $start_page < 2) {
                            $start_page = max(1, $end_page - 2);
                        }
                
                        if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>&department=<?php echo $department; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo $search; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&department=<?php echo $department; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo $search; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>&department=<?php echo $department; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&entries=<?php echo $entries_per_page; ?>&search=<?php echo $search; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <!-- Pagination controls -->
        </div>
    </section>
</div>

<?php
include("include/footer.php");
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tableScroll = document.querySelector('.table-scroll');
    var topScroll = document.createElement('div');
    topScroll.style.overflowX = 'auto';
    topScroll.style.overflowY = 'hidden';
    topScroll.style.width = tableScroll.clientWidth + 'px';
    
    var innerDiv = document.createElement('div');
    innerDiv.style.width = tableScroll.scrollWidth + 'px';
    innerDiv.style.height = '20px';
    
    topScroll.appendChild(innerDiv);
    tableScroll.parentNode.insertBefore(topScroll, tableScroll);
    
    topScroll.addEventListener('scroll', function() {
        tableScroll.scrollLeft = topScroll.scrollLeft;
    });
    
    tableScroll.addEventListener('scroll', function() {
        topScroll.scrollLeft = tableScroll.scrollLeft;
    });
});
</script>