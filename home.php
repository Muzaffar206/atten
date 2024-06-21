<?php
session_start();
include_once('functions.php');
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['user_id']) || !isDeviceIDExists()) {
    header("Location: login.php");
    exit();

}
if (isDeviceIDExists()) {
    $storedDeviceID = getStoredDeviceID();
    echo "Stored Device ID: " . $storedDeviceID;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
</head>
<body>
    <h1>Welcome to the Attendance System</h1>
    <label>
        <input type="radio" name="attendance_mode" value="office" checked> In Office
    </label>
    <label>
        <input type="radio" name="attendance_mode" value="outdoor"> Outdoor
    </label>
    <button onclick="enableAttendance()">Enable Attendance</button>
    <div id="camera" style="width: 500px; height: 400px; display: none;"></div>
    <div id="istClock" style="font-size: 24px;"></div>

    <script>
        function enableAttendance() {
            var mode = document.querySelector('input[name="attendance_mode"]:checked').value;
            if (mode === "office") {
                getLocationForOffice();
            } else if (mode === "outdoor") {
                getLocationForOutdoor();
            }
        }

        function getLocationForOffice() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPositionForOffice, showError);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPositionForOffice(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;

            // Define office locations
            var officeLocations = [
                { name: "Office 1", lat: 19.134100, lon: 72.896900, radius: 0.1 }, // MESCO
                { name: "Office 2", lat: 19.07654352059129, lon: 72.88898322125363, radius: 0.1 } // MUMBRA
                
            ];

            var withinRange = false;
            officeLocations.forEach(location => {
                var distance = getDistanceFromLatLonInKm(lat, lon, location.lat, location.lon);
                if (distance < location.radius) {
                    alert(`You are near ${location.name}`);
                    withinRange = true;
                    startCamera();
                }
            });

            if (!withinRange) {
                alert("You are not in any of the specified office locations.");
            }
        }

        function getLocationForOutdoor() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPositionForOutdoor, showError);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPositionForOutdoor(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            logAttendance('Outdoor', lat, lon);
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

        function startCamera() {
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
                    logAttendance('Office', qrCodeMessage);
                    html5QrCode.stop().then(ignore => {
                        // QR Code scanning is stopped.
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

        function logAttendance(mode, data1, data2 = null) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "log_attendance.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log(xhr.responseText);
                }
            };
            var params = "mode=" + mode + "&data1=" + data1;
            if (data2 !== null) {
                params += "&data2=" + data2;
            }
            xhr.send(params);
        }

         function displayISTClock() {
            setInterval(() => {
                var now = new Date();
                var options = {
                    timeZone: 'Asia/Kolkata',
                    hour12: false,
                    hour: 'numeric',
                    minute: 'numeric',
                    second: 'numeric'
                };
                var istTime = now.toLocaleString('en-US', options);
                document.getElementById('istClock').textContent = 'IST: ' + istTime;
            }, 1000); // Update every second
        }

        // Call the function to start displaying IST clock
        displayISTClock();
    </script>
    <button onclick="document.location='logout.php'">Logout</button>
</body>
</html>
