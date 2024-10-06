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

$sql = "SELECT DATE(first_in) as date, first_mode as mode, 'In' as type, TIME(first_in) as time
        FROM final_attendance
        WHERE user_id = ?
        UNION ALL
        SELECT DATE(last_out) as date, last_mode as mode, 'Out' as type, TIME(last_out) as time
        FROM final_attendance
        WHERE user_id = ? AND last_out IS NOT NULL
        ORDER BY date DESC, time DESC
        LIMIT 4";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$recent_activity = array();
while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}

$stmt->close();
$conn->close();
$pageTitle = 'Home';
$pageDescription = 'MESCO Attendance System home page. Mark your attendance and view recent activity.';
include("include/header.php");
?>

    <div class="app-container">
        <div class="top-bar">
            <div id="clock" class="clock"></div>
            <div id="date" class="date"></div>
        </div>
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($username); ?>
        </div>
        <div class="main-content">
            <div class="attendance-options">
                <div class="attendance-option">
                    <div class="custom-radio active" data-value="In">
                        <input type="radio" id="in" name="scheme" value="In" class="custom-control-input" checked>
                        <label class="custom-control-label" for="in">In</label>
                    </div>
                </div>
                <div class="attendance-option">
                    <div class="custom-radio" data-value="Out">
                        <input type="radio" id="out" name="scheme" value="Out" class="custom-control-input">
                        <label class="custom-control-label" for="out">Out</label>
                    </div>
                </div>
            </div>
            <div class="attendance-options">
                <div class="attendance-option">
                    <div class="custom-radio active" data-value="office">
                        <input type="radio" id="office" name="attendance_mode" value="office" class="custom-control-input" checked>
                        <label class="custom-control-label" for="office">Office</label>
                    </div>
                </div>
                <div class="attendance-option">
                    <div class="custom-radio" data-value="outdoor">
                        <input type="radio" id="outdoor" name="attendance_mode" value="outdoor" class="custom-control-input">
                        <label class="custom-control-label" for="outdoor">Outdoor</label>
                    </div>
                </div>
            </div>
            <button class="submit-btn" onclick="enableAttendance()">
                Give attendance
            </button>
            
            <div id="recentActivity">
                <div class="activity-header">
                    <h3>Recent Activity</h3>
                    <a href="dashboard.php" class="see-more">See more</a>
                </div>
                <ul class="activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                        <li class="activity-item">
                            <div class="activity-icon" style="background-color: <?php echo $activity['type'] == 'In' ? '#E8F5E9' : '#FBE9E7'; ?>; color: <?php echo $activity['type'] == 'In' ? '#4CAF50' : '#FF5722'; ?>;">
                                <i class="fas fa-<?php echo $activity['type'] == 'In' ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i>
                            </div>
                            <div class="activity-details">
                                <div class="activity-type"><?php echo htmlspecialchars($activity['type'] == 'In' ? 'Check In' : 'Check Out'); ?></div>
                                <div class="activity-date"><?php echo date('d M Y', strtotime($activity['date'])); ?></div>
                            </div>
                            <div class="activity-time">
                                <div><?php echo date('h:i a', strtotime($activity['time'])); ?></div>
                                <div class="activity-status"><?php echo $activity['mode']; ?></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <nav class="bottom-navbar">
            <ul class="nav nav-justified">
                <li class="nav-item">
                    <a class="nav-link active" href="home.php">
                        <i class="fas fa-home"></i>
                        <span class="d-block">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="d-block">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i>
                        <span class="d-block">Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="d-block">Logout</span>
                    </a>
                </li>
            </ul>
        </nav>
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

    <script src="assest/js/home.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            const clock = document.getElementById('clock');
            const date = document.getElementById('date');
            
            const newTime = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            
            if (!clock.textContent) {
                clock.innerHTML = newTime.split('').map(char => `<span>${char}</span>`).join('');
            } else {
                const currentChars = clock.children;
                newTime.split('').forEach((char, i) => {
                    if (char !== currentChars[i].textContent) {
                        currentChars[i].classList.add('changing');
                        setTimeout(() => {
                            currentChars[i].textContent = char;
                            currentChars[i].classList.remove('changing');
                        }, 150);
                    }
                });
            }
            
            date.textContent = now.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }

        updateClock();
        setInterval(updateClock, 1000);
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radioButtons = document.querySelectorAll('.custom-radio');
            radioButtons.forEach(radio => {
                radio.addEventListener('click', function() {
                    const name = this.querySelector('input').getAttribute('name');
                    radioButtons.forEach(r => {
                        if (r.querySelector('input').getAttribute('name') === name) {
                            r.classList.remove('active');
                        }
                    });
                    this.classList.add('active');
                    this.querySelector('input').checked = true;
                });
            });
        });
    </script>
    <?php include("include/footer.php"); ?>
</body>

</html>