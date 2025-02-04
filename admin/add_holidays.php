<?php
session_start();
session_regenerate_id(true);
include("../assest/connection/config.php");

$alert = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['edit_holiday'])) {
        $id = $_POST['holiday_id'];
        $holiday_date = $_POST['edit_holiday_date'];
        $holiday_name = $_POST['edit_holiday_name'];
        
        $stmt = $conn->prepare("UPDATE holidays SET holiday_date = ?, holiday_name = ? WHERE id = ?");
        $stmt->bind_param("ssi", $holiday_date, $holiday_name, $id);
        if ($stmt->execute()) {
            $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Holiday updated successfully!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
        } else {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Error updating holiday: ' . $conn->error . '
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
        }
        $stmt->close();
    } elseif (isset($_POST['delete_holiday'])) {
        $id = $_POST['holiday_id'];
        
        $stmt = $conn->prepare("DELETE FROM holidays WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $alert = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                        Holiday deleted successfully!
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
        } else {
            $alert = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        Error deleting holiday: ' . $conn->error . '
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                      </div>';
        }
        $stmt->close();
    }
}

// Add this new section for handling edit and delete actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['holiday_date']) && isset($_POST['holiday_name'])) {
    $holiday_date = $_POST['holiday_date'];
    $holiday_name = $_POST['holiday_name'];

    $stmt = $conn->prepare("INSERT INTO holidays (holiday_date, holiday_name) VALUES (?, ?)");
    $stmt->bind_param("ss", $holiday_date, $holiday_name);
    if ($stmt->execute()) {
        echo "Holiday added successfully!";
    } else {
        echo "Error adding holiday: " . $conn->error;
    }
    $stmt->close();
}

// Fetch holidays for the current year
$current_year = date('Y');
$holidays_query = "SELECT * FROM holidays WHERE YEAR(holiday_date) = ? ORDER BY holiday_date DESC";
$stmt = $conn->prepare($holidays_query);
$stmt->bind_param("i", $current_year);
$stmt->execute();
$holidays_result = $stmt->get_result();

include("include/header.php");
include("include/topbar.php");
$activePage = 'holiday';
include("include/sidebar.php");
?>

<!-- Add this right after the opening <body> tag or at the beginning of your content -->
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
    <?php echo $alert; ?>
</div>

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
                <!-- Form for adding holidays -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Add Holiday</h3>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="form-group">
                                    <label for="holiday_date">Holiday Date:</label>
                                    <input type="date" class="form-control" name="holiday_date" id="holiday_date" required>
                                </div>
                                <div class="form-group">
                                    <label for="holiday_name">Holiday Name:</label>
                                    <input type="text" class="form-control" name="holiday_name" id="holiday_name" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Add Holiday</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Calendar and Holiday List -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Holiday Calendar</h3>
                        </div>
                        <div class="card-body">
                            <div id="calendar"></div> <!-- Calendar placeholder -->
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Holiday List (<?php echo $current_year; ?>)</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($holidays_result->num_rows > 0): ?>
                                <ul class="list-group">
                                    <?php while ($holiday = $holidays_result->fetch_assoc()) : ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <strong><?php echo date('d M, Y', strtotime($holiday['holiday_date'])); ?></strong> - <?php echo $holiday['holiday_name']; ?>
                                            </span>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#editModal<?php echo $holiday['id']; ?>">Edit</button>
                                                <button type="button" class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal<?php echo $holiday['id']; ?>">Delete</button>
                                            </div>
                                        </li>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo $holiday['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $holiday['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel<?php echo $holiday['id']; ?>">Edit Holiday</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                                            <div class="form-group">
                                                                <label for="edit_holiday_date<?php echo $holiday['id']; ?>">Holiday Date:</label>
                                                                <input type="date" class="form-control" name="edit_holiday_date" id="edit_holiday_date<?php echo $holiday['id']; ?>" value="<?php echo $holiday['holiday_date']; ?>" required>
                                                            </div>
                                                            <div class="form-group">
                                                                <label for="edit_holiday_name<?php echo $holiday['id']; ?>">Holiday Name:</label>
                                                                <input type="text" class="form-control" name="edit_holiday_name" id="edit_holiday_name<?php echo $holiday['id']; ?>" value="<?php echo $holiday['holiday_name']; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                            <button type="submit" name="edit_holiday" class="btn btn-primary">Save changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo $holiday['id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel<?php echo $holiday['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?php echo $holiday['id']; ?>">Delete Holiday</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete this holiday?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form method="post">
                                                            <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="delete_holiday" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <p>No holidays found for <?php echo $current_year; ?>.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Include FullCalendar CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        events: [
            <?php
            $holidays_result->data_seek(0); // Reset result pointer to the beginning
            while ($holiday = $holidays_result->fetch_assoc()) :
                echo "{ title: '" . addslashes($holiday['holiday_name']) . "', start: '" . $holiday['holiday_date'] . "' },";
            endwhile;
            ?>
        ]
    });

    calendar.render();

    // Auto-hide alert after 5 seconds
    var alertElement = document.querySelector('.alert');
    if (alertElement) {
        setTimeout(function() {
            alertElement.classList.remove('show');
        }, 5000);
    }
});
</script>

<?php include("include/footer.php"); ?>