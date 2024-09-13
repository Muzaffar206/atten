<?php
session_start();
session_regenerate_id(true);
include("../assest/connection/config.php");

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

// Fetch all holidays
$holidays_query = "SELECT * FROM holidays ORDER BY holiday_date DESC";
$holidays_result = $conn->query($holidays_query);

include("include/header.php");
include("include/topbar.php");
$activePage = 'holiday';
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
                            <h3 class="card-title">Holiday List</h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php while ($holiday = $holidays_result->fetch_assoc()) : ?>
                                    <li class="list-group-item">
                                        <strong><?php echo date('d M, Y', strtotime($holiday['holiday_date'])); ?></strong> - <?php echo $holiday['holiday_name']; ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
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
});
</script>

<?php include("include/footer.php"); ?>
