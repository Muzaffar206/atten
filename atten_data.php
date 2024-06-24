<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <link rel="stylesheet" href="assest/css/style.css">
</head>
<body>
    <h1>Welcome to the Attendance System</h1>
    
    <!-- Section to display and download attendance data -->
    <h2>Attendance Records</h2>
    <div id="attendanceData">
        <?php
        $conn = new mysqli('localhost', 'root', '', 'attendance_system');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $sql = "SELECT * FROM attendance";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo '<table border="1">';
            echo '<tr><th>ID</th><th>Mode</th><th>Data</th><th>Latitude</th><th>Longitude</th><th>Timestamp</th><th>Type</th></tr>';
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['id'] . '</td>';
                echo '<td>' . $row['mode'] . '</td>';
                echo '<td>' . $row['data'] . '</td>';
                echo '<td>' . $row['latitude'] . '</td>';
                echo '<td>' . $row['longitude'] . '</td>';
                echo '<td>' . $row['timestamp'] . '</td>';
                echo '<td>' . $row['type'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo "No attendance records found.";
        }
        $conn->close();
        ?>
    </div>
    <br>
    <button onclick="downloadCSV()">Download CSV</button>

    <script>
        function downloadCSV() {
            window.location.href = 'download_csv.php';
        }
    </script>
    <button onclick="document.location='logout.php'">Logout</button>
</body>
</html>
