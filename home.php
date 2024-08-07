<?php
session_start();
session_regenerate_id(true);
date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

include("assest/connection/config.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] === 'admin') {
    header("Location: admin/index.php"); // Redirect to home.php
    exit(); // Ensure no further code is executed
}
$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();
$conn->close();


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESCO | Attendance</title>
    <?php include("include/header.php"); ?>
    <script src="assest/js/notification.js"></script>

    <div class="limiter">
        <div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
            <div class="wrap-login100">
                <div class="date" id="date"></div>
                <div class="clock" id="clock"></div>
                <span class="login100-form-logo">
                    <img src="assest/images/MESCO.png" width="100px">
                </span>

                <span class="login100-form-title p-b-34 p-t-27">
                    Welcome <?php echo htmlspecialchars($username); ?>
                </span>

                <div class="wrap-input100">
                    <div class="radio-inputs">
                        <label class="radio">
                            <input type="radio" name="scheme" id="type" value="In" checked="" required>
                            <span class="name">In</span>
                        </label>
                        <label class="radio">
                            <input type="radio" name="scheme" id="type" value="Out" required>
                            <span class="name">Out</span>
                        </label>
                    </div>
                </div>

                <div class="wrap-input100">
                    <div class="radio-inputs">
                        <label class="radio">
                            <input type="radio" name="attendance_mode" value="office" checked="">
                            <span class="name">Office</span>
                        </label>
                        <label class="radio">
                            <input type="radio" name="attendance_mode" value="outdoor">
                            <span class="name">Outdoor</span>
                        </label>
                    </div>
                </div>



                <div class="container-login100-form-btn">
                    <button onclick="enableAttendance()" class="login100-form-btn">
                        Give attendance
                    </button>

                </div>
                <!-- Camera Section -->
                <div id="camera" style="display:none;"></div>
                <div class="camera-container" style="display:none;">
                    <video id="video" width="320" height="240" autoplay></video>
                    <canvas id="canvas" style="display:none;"></canvas>
                </div>
                

                <div class="container-login100-form-btn">
                    <button class="btn btn-danger" onclick="document.location='logout.php'"><span>Logout!</span></button>
                </div>

                <div class="container-login100-form-btn">
                    <a class="dashboard" target="_blank" href="dashboard.php">Check your Attendance</a>
                </div>



            </div>
        </div>
    </div>

    <script src="assest/js/home.js"></script>




    <?php include("include/footer.php"); ?>
    </body>

</html>