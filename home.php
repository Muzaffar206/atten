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
    <title>MESCO | Attendance</title><?php include("include/header.php"); ?>
    <script src="assest/js/notification.js"></script>
    <nav class="navbar navbar-expand-md navbar navbar-light">
        <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <img src="assest/images/MESCO.png" width="100" height="40" class="d-inline-block align-top" alt="">
                    Attendance
                </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-clock"></i> Check your Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-user"></i> My Profile</a>
                    </li>
                    <button type="button" class="btn btn-danger" onclick="document.location='logout.php'">Logout</button>
                </ul>
            </div>
        </div>
    </nav>

    <div class="limiter">
        <div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
            <div class="wrap-login100">
                <div class="date" id="date"></div>
                <div class="clock" id="clock"></div>
                
                <div class="reminder">
                    <p class="reminder-note">If you have marked as <b>"In"</b>, please remember to mark as <b>"Out"</b> before leaving.</p>
                </div>
                <span class="login100-form-title p-b-34 p-t-27">
                    Welcome <?php echo htmlspecialchars($username); ?>
                </span>


                <div class="wrap-input1000">
                    <div class="radio-inputs">
                        <label class="radio">
                            <i class="fas fa-sign-in-alt"></i>
                            <input type="radio" name="scheme" id="type" value="In" checked="" required>
                            <span class="name">In</span>
                        </label>
                        <label class="radio">
                            <i class="fas fa-sign-out-alt"></i>
                            <input type="radio" name="scheme" id="type" value="Out" required>
                            <span class="name">Out</span>
                        </label>
                    </div>
                </div>

                <div class="wrap-input1000">
                    <div class="radio-inputs">
                        <label class="radio">
                            <i class="fas fa-building"></i>
                            <input type="radio" name="attendance_mode" value="office" checked="">
                            <span class="name">Office</span>
                        </label>
                        <label class="radio">
                            <i class="fas fa-map-marker-alt"></i>
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
                <div id="successOverlay" style="display: none;">
                    <div class="overlay"></div>
                    <div class="icon-container">
                        <div class="checkmark-circle">
                            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none" />
                                <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                            </svg>
                        </div>
                    </div>
                </div>
                <audio id="successSound" src="assest/sounds/success.mp3"></audio>

                <div id="errorOverlay" style="display: none;">
                    <div class="overlay"></div>
                    <div class="icon-container">
                        <div class="cross-circle">
                            <svg class="cross" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                <circle class="cross-circle" cx="26" cy="26" r="25" fill="none" />
                                <path class="cross-line" fill="none" d="M16 16l20 20m-20 0l20-20" />
                            </svg>
                        </div>
                    </div>
                </div>



            </div>
        </div>
    </div>

    <script src="assest/js/home.js"></script>




    <?php include("include/footer.php"); ?>
    </body>

</html>