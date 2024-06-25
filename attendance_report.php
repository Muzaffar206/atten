<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'attendance_system');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT 
            users.id AS user_id, 
            users.employer_id,
            users.username, 
            users.full_name,
            attendance.mode, 
            attendance.latitude, 
            attendance.longitude,
            attendance.in_time,
            attendance.out_time, 
            attendance.selfie 
        FROM attendance 
        JOIN users ON attendance.user_id = users.id
        ORDER BY attendance.id DESC";
        

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESCO | Attendance report</title>
<!--===============================================================================================-->	
	<link rel="icon" type="image/png" href="assest/images/icons/favicon.ico"/>
<!--===============================================================================================-->	
    <link rel="stylesheet" href="assest/css/bootstrap.min.css">
<!--===============================================================================================-->	
    <link rel="stylesheet" type="text/css" href="assest/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/fonts/iconic/css/material-design-iconic-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/vendor/animate/animate.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="assest/vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/vendor/select2/select2.min.css">
<!--===============================================================================================-->	
	<link rel="stylesheet" type="text/css" href="assest/vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/css/util.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="assest/css/main.css">
<!--===============================================================================================-->

<style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 15px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
    </style>
</head>
<body>
	<div class="preloader">
<div class="lava-lamp">
  <div class="bubble"></div>
  <div class="bubble1"></div>
  <div class="bubble2"></div>
  <div class="bubble3"></div>
</div>
</div>
   
    <h1>Attendance Report</h1>
    <table class="table table-hover">
        <tr>
            <th>User ID</th>
            <th>Emp id</th>
            <th>Username</th>
            <th>Fullname</th>
            <th>Mode</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>In time</th>
            <th>Out time</th>
            <th>Selfie</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row['user_id'] . "</td>
                        <td>" . $row['employer_id'] . "</td>
                        <td>" . $row['username'] . "</td>
                        <td>" . $row['full_name'] . "</td>
                        <td>" . $row['mode'] . "</td>
                        <td>" . ($row['latitude'] ?? 'N/A') . "</td>
                        <td>" . ($row['longitude'] ?? 'N/A') . "</td>
                        <td>" . $row['in_time'] . "</td>
                        <td>" . $row['out_time'] . "</td>
                        <td>" . (!empty($row['selfie']) ? '<img src="data:image/jpeg;base64,' . base64_encode($row['selfie']) . '" alt="Selfie">' : 'N/A') . "</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='7'>No records found</td></tr>";
        }
        ?>
        <!--===============================================================================================-->
	<script src="assest/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/bootstrap/js/popper.js"></script>
	<script src="assest/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/daterangepicker/moment.min.js"></script>
	<script src="assest/vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="assest/vendor/countdowntime/countdowntime.js"></script>
<!--===============================================================================================-->
	<script src="assest/js/main.js"></script>

<script>

window.onload = function(){
        //hide the preloader
        document.querySelector(".preloader").style.display = "none";
    }
</script>
<!-- </form> -->
</body>
</html>
        
