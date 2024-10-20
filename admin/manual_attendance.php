<?php
session_start();
session_regenerate_id(true);

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Include necessary files
require_once("../assest/connection/config.php");

// Fetch office locations from the database
$officeLocationsQuery = "SELECT name, qr_code FROM office_locations ORDER BY name";
$officeLocationsResult = $conn->query($officeLocationsQuery);
$officeLocations = $officeLocationsResult->fetch_all(MYSQLI_ASSOC);

// CSRF protection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed");
    }
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == 'true') {
    try {
        // Fetch recent manual attendance history
        $page = isset($_GET['page']) ? filter_var($_GET['page'], FILTER_VALIDATE_INT) : 1;
        if ($page === false || $page < 1) {
            throw new Exception("Invalid page number");
        }
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $historySql = "SELECT h.id, h.user_id, u.full_name, h.action_type, h.action_time, h.details, a.username as admin_username
                       FROM manual_attendance_history h
                       JOIN users u ON h.user_id = u.id
                       JOIN users a ON h.admin_id = a.id
                       ORDER BY h.action_time DESC
                       LIMIT ? OFFSET ?";
        $historyStmt = $conn->prepare($historySql);
        $historyStmt->bind_param("ii", $limit, $offset);
        $historyStmt->execute();
        $historyResult = $historyStmt->get_result();

        $data = [];
        while ($entry = $historyResult->fetch_assoc()) {
            $data[] = array_map('htmlspecialchars', $entry);
        }

        echo json_encode($data);
    } catch (Exception $e) {
        error_log("Error in AJAX request: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "An error occurred while fetching data"]);
    }
    exit;
}

// Set default date and time to current
$currentDateTime = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
$defaultDate = $currentDateTime->format('Y-m-d');
$defaultTime = $currentDateTime->format('H:i');

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'delete') {
        // Delete attendance entry
        $attendance_id = filter_var($_POST['attendance_id'], FILTER_VALIDATE_INT);
        if ($attendance_id === false) {
            $_SESSION['error_message'] = "Invalid attendance ID";
        } else {
            $conn->begin_transaction();

            try {
                // Delete from attendance table
                $deleteSql = "DELETE FROM attendance WHERE id = ?";
                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param("i", $attendance_id);
                $deleteStmt->execute();

                // Delete from final_attendance table
                $deleteFinalSql = "DELETE FROM final_attendance WHERE user_id = ? AND date = ?";
                $deleteFinalStmt = $conn->prepare($deleteFinalSql);
                $deleteFinalStmt->bind_param("is", $user_id, $date);
                $deleteFinalStmt->execute();

                // Add to history
                $historySql = "INSERT INTO manual_attendance_history (user_id, action_type, action_time, details, admin_id) VALUES (?, 'delete', NOW(), ?, ?)";
                $historyStmt = $conn->prepare($historySql);
                $details = "Deleted attendance entry ID: " . $attendance_id;
                $historyStmt->bind_param("isi", $user_id, $details, $_SESSION['user_id']);
                $historyStmt->execute();

                $conn->commit();
                $_SESSION['success_message'] = "Attendance entry deleted successfully.";
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error_message'] = "Error deleting attendance entry: " . $e->getMessage();
            }
        }
    } else {
        // Add new attendance entry
        $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
        $date = htmlspecialchars(strip_tags($_POST['date']));
        $in_time = htmlspecialchars(strip_tags($_POST['in_time']));
        $out_time = !empty($_POST['out_time']) ? htmlspecialchars(strip_tags($_POST['out_time'])) : null;
        $mode = htmlspecialchars(strip_tags($_POST['mode']));
        $office_location = htmlspecialchars(strip_tags($_POST['office_location']));
        $reason = htmlspecialchars(strip_tags($_POST['reason']));

        if ($user_id === false || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $date) || !preg_match("/^([01]\d|2[0-3]):([0-5]\d)$/", $in_time)) {
            $_SESSION['error_message'] = "Invalid input data";
        } else {
            $in_time = $date . ' ' . $in_time . ':00';
            $out_time = $out_time ? $date . ' ' . $out_time . ':00' : null;

            // Check if an entry already exists for this user on this date
            $checkSql = "SELECT id FROM attendance WHERE user_id = ? AND DATE(in_time) = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("is", $user_id, $date);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                $_SESSION['error_message'] = "An attendance entry already exists for this user on the selected date.";
            } else {
                $conn->begin_transaction();

                try {
                    $sql = "INSERT INTO attendance (user_id, in_time, out_time, mode, data, reason_for_manual_entry) 
                            VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $data = "Where: " . $office_location;
                    $stmt->bind_param("isssss", $user_id, $in_time, $out_time, $mode, $data, $reason);
                    $stmt->execute();

                    $updateSql = "INSERT INTO final_attendance (user_id, date, first_in, last_out, first_mode, last_mode, total_hours) 
                                  VALUES (?, ?, ?, ?, ?, ?, TIMESTAMPDIFF(HOUR, ?, IFNULL(?, NOW())))
                                  ON DUPLICATE KEY UPDATE 
                                  first_in = LEAST(first_in, ?),
                                  last_out = GREATEST(last_out, IFNULL(?, last_out)),
                                  first_mode = IF(? = first_in, ?, first_mode),
                                  last_mode = IF(? IS NOT NULL, ?, last_mode),
                                  total_hours = TIMESTAMPDIFF(HOUR, LEAST(first_in, ?), GREATEST(IFNULL(last_out, NOW()), IFNULL(?, NOW())))";
                    
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("ssssssssssssssss", 
                        $user_id, $date, $in_time, $out_time, $mode, $mode, 
                        $in_time, $out_time, $in_time, $out_time, 
                        $in_time, $mode, $out_time, $mode, 
                        $in_time, $out_time
                    );
                    $updateStmt->execute();

                    // Add to history
                    $historySql = "INSERT INTO manual_attendance_history (user_id, action_type, action_time, details, admin_id) VALUES (?, 'add', NOW(), ?, ?)";
                    $historyStmt = $conn->prepare($historySql);
                    $details = "In: $in_time, Out: $out_time, Mode: $mode, Location: $office_location, Reason: $reason";
                    $historyStmt->bind_param("isi", $user_id, $details, $_SESSION['user_id']);
                    $historyStmt->execute();

                    $conn->commit();
                    $_SESSION['success_message'] = "Attendance added successfully.";
                } catch (Exception $e) {
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error adding attendance: " . $e->getMessage();
                }
            }
        }
    }

    header("Location: manual_attendance.php");
    exit();
}

// Fetch all users
$usersSql = "SELECT id, username, full_name FROM users WHERE role != 'admin'";
$usersResult = $conn->query($usersSql);

// Fetch recent manual attendance history
$limit = 10;
$historySql = "SELECT h.id, h.user_id, u.full_name, h.action_type, h.action_time, h.details, a.username as admin_username
               FROM manual_attendance_history h
               JOIN users u ON h.user_id = u.id
               JOIN users a ON h.admin_id = a.id
               ORDER BY h.action_time DESC
               LIMIT ?";
$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param("i", $limit);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

include("include/header.php");
include("include/topbar.php");
$activePage = 'manual_attendance';
include("include/sidebar.php");
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Manual Attendance Entry</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active">Manual Attendance</li>
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
                        <div class="card-header">
                            <h3 class="card-title">Add Manual Attendance</h3>
                        </div>
                        <div class="card-body">
                            <?php
                            if (isset($_SESSION['success_message'])) {
                                echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
                                unset($_SESSION['success_message']);
                            }
                            if (isset($_SESSION['error_message'])) {
                                echo '<div class="alert alert-danger">' . $_SESSION['error_message'] . '</div>';
                                unset($_SESSION['error_message']);
                            }
                            ?>
                            <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <div class="form-group">
                                    <label for="user_id">User:</label>
                                    <select name="user_id" id="user_id" class="form-control" required>
                                        <option value="">Select User</option>
                                        <?php while ($user = $usersResult->fetch_assoc()) : ?>
                                            <option value="<?php echo $user['id']; ?>"><?php echo $user['full_name'] . ' (' . $user['username'] . ')'; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="date">Date:</label>
                                    <input type="date" name="date" id="date" class="form-control" value="<?php echo $defaultDate; ?>" disabled>
                                    <input type="hidden" name="date" value="<?php echo $defaultDate; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="in_time">In Time:</label>
                                    <input type="time" name="in_time" id="in_time" class="form-control" value="<?php echo $defaultTime; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="out_time">Out Time:</label>
                                    <input type="time" name="out_time" id="out_time" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="mode">Mode:</label>
                                    <select name="mode" id="mode" class="form-control" required>
                                        <option value="Office">Office</option>
                                        <option value="Outdoor">Outdoor</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="office_location">From where:</label>
                                    <select name="office_location" id="office_location" class="form-control" required>
                                        <?php foreach ($officeLocations as $location) : ?>
                                            <option value="<?php echo htmlspecialchars($location['qr_code']); ?>"><?php echo htmlspecialchars($location['qr_code']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="reason">Reason for Manual Entry:</label>
                                    <textarea name="reason" id="reason" class="form-control" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Attendance</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Manual Attendance Actions</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-striped" id="historyTable">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Time</th>
                                        <th>Details</th>
                                        <th>Admin</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($entry = $historyResult->fetch_assoc()) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($entry['full_name']); ?></td>
                                            <td><?php echo ucfirst($entry['action_type']); ?></td>
                                            <td><?php echo $entry['action_time']; ?></td>
                                            <td><?php echo htmlspecialchars($entry['details']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['admin_username']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <div class="text-center mt-3">
                                <button id="loadMoreBtn" class="btn btn-primary">Load More</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include("include/footer.php"); ?>

<script>
$(document).ready(function() {
    var page = 2;
    $('#loadMoreBtn').click(function() {
        $.ajax({
            url: 'manual_attendance.php',
            method: 'GET',
            data: { ajax: 'true', page: page },
            dataType: 'json',
            success: function(data) {
                if (data.length > 0) {
                    var tableBody = $('#historyTable tbody');
                    $.each(data, function(index, entry) {
                        var row = '<tr>' +
                            '<td>' + entry.full_name + '</td>' +
                            '<td>' + capitalizeFirstLetter(entry.action_type) + '</td>' +
                            '<td>' + entry.action_time + '</td>' +
                            '<td>' + entry.details + '</td>' +
                            '<td>' + entry.admin_username + '</td>' +
                            '</tr>';
                        tableBody.append(row);
                    });
                    page++;
                }
                if (data.length < 10) {
                    $('#loadMoreBtn').hide();
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX error: " + textStatus + ' : ' + errorThrown);
                alert('Error loading more data. Please check the console for more information.');
            }
        });
    });

    function capitalizeFirstLetter(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
});
</script>
