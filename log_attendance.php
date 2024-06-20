<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $qr_code = $_POST['qr_code'];

    $conn = new mysqli('localhost', 'root', '', 'attendance_system');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "INSERT INTO attendance (user_id, scan_time) VALUES ('$user_id', NOW())";
    if ($conn->query($sql) === TRUE) {
        echo "Attendance logged successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }

    $conn->close();
}
?>
