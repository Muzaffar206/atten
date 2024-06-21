<?php
session_start();

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
    <div id="cameraSelfie" style="width: 500px; height: 400px; display: none;">
        <video id="video" width="500" height="400" autoplay></video>
        <button onclick="captureSelfie()">Capture Selfie</button>
        <canvas id="canvas" width="500" height="400" style="display: none;"></canvas>
    </div>
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

            // office locations
            var officeLocations = [
                { name: "Office 1", lat: 19.035626377699412, lon:  72.84758504874834, radius: 0.1 }, // MESCO
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
            captureSelfieAndLogAttendance('Outdoor', lat + ',' + lon);
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
                    document.getElementById("camera").style.display = "none";
                    html5QrCode.stop().then(ignore => {
                        // QR Code scanning is stopped.
                        captureSelfieAndLogAttendance('Office', qrCodeMessage);
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

        function captureSelfie() {
            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const selfie = canvas.toDataURL('image/png');
            return selfie;
        }

        function captureSelfieAndLogAttendance(mode, data1) {
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
                            logAttendance(mode, data1, null, selfie);
                            stream.getTracks().forEach(track => track.stop()); // Stop video stream
                        };
                    };
                })
                .catch(err => {
                    console.log("Error accessing webcam: " + err);
                });
        }

        function logAttendance(mode, data1, data2 = null, selfie = null) {
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
            if (selfie !== null) {
                params += "&selfie=" + encodeURIComponent(selfie);
            }
            xhr.send(params);
        }

        
    </script>
    <button onclick="document.location='logout.php'">Logout</button>
</body>
</html>
