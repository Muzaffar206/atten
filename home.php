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
    <script src="https://cdn.jsdelivr.net/npm/html5-qrcode/minified/html5-qrcode.min.js"></script>
</head>
<body>
    <h1>Welcome to the Attendance System</h1>
    <button onclick="getLocation()">Enable Camera</button>
    <div id="camera" style="width: 500px; height: 400px;"></div>

    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition, showError);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPosition(position) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
        //     var parisLat = 19.07454352059129;
        //     var parisLon = 72.88698322125363;
        //     var distance = getDistanceFromLatLonInKm(lat, lon, parisLat, parisLon);
        //     if (distance < 10) { // 10 km radius
        //         startCamera();
        //     } else {
        //         alert("You are not in MESCO");
        //     }
        // }
        var locations = [
                { name: "MESCO", lat: 19.134100, lon:  72.896900 },
                { name: "RC Mahim", lat: 19.07554352059129, lon: 72.88798322125363 },
                { name: "Mumbra", lat: 19.07654352059129, lon: 72.88898322125363 }
            ];

            // Check distance from each location
            var withinRange = false;
            locations.forEach(location => {
                var distance = getDistanceFromLatLonInKm(lat, lon, location.lat, location.lon);
                var radius = 0.1; // 100 meters in kilometers

                if (distance < radius) {
                    alert(`You are near ${location.name}`);
                    withinRange = true;
                }
            });

            if (withinRange) {
                startCamera();
            } else {
                alert("You are not near any of the specified locations.");
            }
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
            const html5QrCode = new Html5Qrcode("camera");
            html5QrCode.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: 250
                },
                qrCodeMessage => {
                    alert(`QR Code detected: ${qrCodeMessage}`);
                    logAttendance(qrCodeMessage);
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

        function logAttendance(qrCodeMessage) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "log_attendance.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log(xhr.responseText);
                }
            };
            xhr.send("qr_code=" + qrCodeMessage);
        }
    </script>
     <button onclick="document.location='logout.php'">Logout</button>
</body>
</html>
