<?php
session_start();
session_regenerate_id(true);
date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

include("assest/connection/config.php");
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
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

    <div class="limiter">
        <div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
            <div class="wrap-login100">
                <div class="date" id="date"></div>
                <div class="clock" id="clock"></div>
                <span class="login100-form-logo">
                    <img src="assest/css/MESCO.png" alt="MESCO LOGO" width="100px">
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
                <div class="camera-container">
                    <div id="camera" class="camera" style="display: none;">
                        <!-- Placeholder for camera display -->
                    </div>
                    <div id="cameraSelfie" class="camera" style="display: none;">
                        <video id="video" width="500" height="400" autoplay></video>
                        <canvas id="canvas" width="500" height="400" style="display: none;"></canvas>
                        <button class="button123" onclick="captureSelfie()">Capture Selfie</button>
                    </div>
                </div>


                <div class="container-login100-form-btn">
                    <button class="button123" onclick="document.location='logout.php'"><span>Logout!</span></button>
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