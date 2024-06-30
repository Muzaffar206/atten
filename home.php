<?php
session_start();
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
    <?php include("include/header.php");?>

    <div class="limiter">
		<div class="container-login100" style="background-image: url('assest/images/bg-01.jpg');">
			<div class="wrap-login100">
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
                    <div id="camera" style="width: 400px; height: 500px; display: none;"></div>
    <div id="cameraSelfie" style="width: 400px; height: 500px; display: none;">
        <video id="video" width="500" height="400" autoplay></video>
        <canvas id="canvas" width="500" height="400" style="display: none;"></canvas>
        <button class="button123" onclick="captureSelfie()">Capture Selfie</button>
    </div>

                    <div class="container-login100-form-btn">
                    <button class="button123" onclick="document.location='logout.php'"><span>Logout!</span></button></div>

				
			</div>
		</div>
	</div>


    
 <script>
        function enableAttendance() {
            var mode = document.querySelector('input[name="attendance_mode"]:checked').value;
            var type = document.querySelector('input[name="scheme"]:checked').value;
            if (type === "") {
                alert("Please select In or Out.");
                return;
            }
            if (mode === "office") {
                getLocationForOffice(type);
            } else if (mode === "outdoor") {
                getLocationForOutdoor(type);
            }
        }

        function getLocationForOffice(scanType) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => showPositionForOffice(position, scanType),
                    showError,
                    { enableHighAccuracy: true }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPositionForOffice(position, scanType) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            console.log(`Current Position: Lat=${lat}, Lon=${lon}`);

            var officeLocations = [
                { name: "Office 1", lat: 19.0748, lon: 72.8856, radius: 0.2 }, // Adjusted radius for MESCO
                { name: "Office 2", lat: 19.07654352059129, lon: 72.88898322125363, radius: 0.2 } // Adjusted radius for MUMBRA
            ];

            var withinRange = false;
            officeLocations.forEach(location => {
                var distance = getDistanceFromLatLonInKm(lat, lon, location.lat, location.lon);
                console.log(`Distance to ${location.name}: ${distance} km`);
                if (distance < location.radius) {
                    alert(`You are near ${location.name}`);
                    withinRange = true;
                    startCamera(scanType);
                }
            });

            if (!withinRange) {
                alert("You are not in any of the specified office locations.");
            }
        }

        function getLocationForOutdoor(scanType) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => showPositionForOutdoor(position, scanType), showError);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPositionForOutdoor(position, scanType) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            startCameraForOutdoor(scanType, lat, lon);
        }

        function showError(error) {
            switch (error.code) {
                case error.PERMISSION_DENIED:
                    alert("User denied the request for Geolocation.");
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert("Location information is unavailable.");
                    break;
                case error.TIMEOUT:
                    alert("The request to get user location timed out.");
                    break;
                case error.UNKNOWN_ERROR:
                    alert("An unknown error occurred.");
                    break;
            }
        }

        function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
            var R = 6371; // Radius of the earth in km
            var dLat = deg2rad(lat2 - lat1);
            var dLon = deg2rad(lon2 - lon1);
            var a = 
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) * 
                Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            var d = R * c; // Distance in km
            return d;
        }

        function deg2rad(deg) {
            return deg * (Math.PI / 180);
        }

        function startCamera(scanType) {
            document.getElementById("camera").style.display = "block";
            const html5QrCode = new Html5Qrcode("camera");
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: 250
                },
                qrCodeMessage => {
                    alert(`QR Code detected: ${qrCodeMessage}`);
                    document.getElementById("camera").style.display = "none";
                    html5QrCode.stop().then(ignore => {
                        captureSelfieAndLogAttendance('Office', qrCodeMessage, scanType);
                    }).catch(err => {
                        console.log("Unable to stop scanning.");
                    });
                },
                errorMessage => {
                    console.log(`QR Code no longer in front of camera.`);
                }
            ).catch(err => {
                console.log(`Unable to start scanning, error: ${err}`);
            });
        }

        function startCameraForOutdoor(scanType, lat, lon) {
            document.getElementById('cameraSelfie').style.display = 'block';
            const video = document.getElementById('video');
            const constraints = { video: true };
            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    video.srcObject = stream;
                    video.onloadedmetadata = () => {
                        video.play();
                        document.querySelector('button[onclick="captureSelfie()"]').onclick = () => {
                            const selfie = captureSelfie();
                            logAttendance('Outdoor', `${lat},${lon}`, null, selfie, scanType);
                            stream.getTracks().forEach(track => track.stop()); // Stop video stream
                        };
                    };
                })
                .catch(err => {
                    console.log("Error accessing webcam: " + err);
                });
        }

        function captureSelfie() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const selfie = canvas.toDataURL('image/png');
            return selfie;
        }

        function captureSelfieAndLogAttendance(mode, data1, scanType) {
            const video = document.getElementById('video');
            const constraints = { video: true };
            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    video.srcObject = stream;
                    video.onloadedmetadata = () => {
                        video.play();
                        document.getElementById('cameraSelfie').style.display = 'block';
                        document.querySelector('button[onclick="captureSelfie()"]').onclick = () => {
                            const selfie = captureSelfie();
                            logAttendance(mode, data1, null, selfie, scanType);
                            stream.getTracks().forEach(track => track.stop()); // Stop video stream
                            document.getElementById('cameraSelfie').style.display = 'none';
                        };
                    };
                })
                .catch(err => {
                    console.log("Error accessing webcam: " + err);
                });
        }

        function logAttendance(mode, data1, data2, selfie, scanType) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "log_attendance.php", true);
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    console.log(xhr.responseText);
                    alert("Attendance logged successfully.");
                }
            };
            var params = "mode=" + mode + "&data1=" + encodeURIComponent(data1) + "&scanType=" + scanType;
            if (data2 !== null) {
                params += "&data2=" + encodeURIComponent(data2);
            }
            if (selfie !== null) {
                params += "&selfie=" + encodeURIComponent(selfie);
            }
            xhr.send(params);
        }

        // IST Clock
        // function updateISTClock() {
        //     const istOffset = 5.5 * 60 * 60 * 1000;
        //     const now = new Date();
        //     const utcNow = now.getTime() + (now.getTimezoneOffset() * 60 * 1000);
        //     const istNow = new Date(utcNow + istOffset);
        //     const hours = istNow.getHours().toString().padStart(2, '0');
        //     const minutes = istNow.getMinutes().toString().padStart(2, '0');
        //     const seconds = istNow.getSeconds().toString().padStart(2, '0');
        //     document.getElementById('istClock').innerText = `IST: ${hours}:${minutes}:${seconds}`;
        // }

        // setInterval(updateISTClock, 1000);
    </script>

<?php include("include/footer.php");?>
</body>
</html>
