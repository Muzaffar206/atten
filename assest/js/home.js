
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
                    showError, {
                        enableHighAccuracy: true
                    }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }

        function showPositionForOffice(position, scanType) {
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            console.log(`Current Position: Lat=${lat}, Lon=${lon}`);

            var officeLocations = [{
                    name: "Office 1",
                    lat: 19.0748,
                    lon: 72.8856,
                    radius: 0.2
                }, // Adjusted radius for MESCO
                {
                    name: "Office 2",
                    lat: 19.07654352059129,
                    lon: 72.88898322125363,
                    radius: 0.2
                } // Adjusted radius for MUMBRA
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
            html5QrCode.start({
                    facingMode: "environment"
                }, {
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
            const constraints = {
                video: true
            };
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
            const video = document.createElement('video');
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            navigator.mediaDevices.getUserMedia({
                    video: true
                })
                .then(stream => {
                    video.srcObject = stream;
                    video.play();
                    setTimeout(() => {
                        context.drawImage(video, 0, 0, canvas.width, canvas.height);
                        const selfie = canvas.toDataURL('image/png');
                        logAttendance(mode, data1, null, selfie, scanType);
                        stream.getTracks().forEach(track => track.stop()); // Stop video stream
                    }, 500); // Capture selfie after 1 seconds
                })
                .catch(err => {
                    console.log("Error accessing webcam: " + err);
                });
        }


        function logAttendance(mode, data1, data2, selfie, scanType) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "log_attendance.php", true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.status === 'success') {
                                alert(response.message);
                            } else {
                                alert('Error: ' + response.message);
                            }
                        } catch (e) {
                            alert('An unexpected error occurred. Please try again.');
                        }
                    } else {
                        alert('An error occurred while recording attendance. Please try again.');
                    }
                }
            };        
        
            var formData = new FormData();
            formData.append("mode", mode);
            formData.append("data1", data1);
            formData.append("scanType", scanType);
            if (selfie) {
                var blob = dataURLToBlob(selfie);
                formData.append(scanType === "In" ? "selfie_in" : "selfie_out", blob, scanType === "In" ? "selfie_in.jpg" : "selfie_out.jpg");
            }
        
            xhr.send(formData);
        }
        
        function dataURLToBlob(dataURL) {
            var binary = atob(dataURL.split(',')[1]);
            var array = [];
            for (var i = 0; i < binary.length; i++) {
                array.push(binary.charCodeAt(i));
            }
            return new Blob([new Uint8Array(array)], { type: 'image/jpeg' });
        }
        
        function updateClock() {
            const now = new Date();
            const options = { timeZone: 'Asia/Kolkata', hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const timeString = now.toLocaleTimeString('en-GB', options);
            document.getElementById('clock').textContent = timeString;

            const dateOptions = { timeZone: 'Asia/Kolkata', weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateString = now.toLocaleDateString('en-GB', dateOptions);
            document.getElementById('date').textContent = dateString;
        }
        setInterval(updateClock, 1000);
        updateClock(); // initial call to display the clock immediately

