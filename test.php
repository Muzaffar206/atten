<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MESCO | Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        #map {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        .content-wrapper {
            position: relative;
            z-index: 1;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }
        .logo img {
            max-width: 100px;
            margin-bottom: 1rem;
        }
        .datetime {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .reminder {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-top: 1rem;
        }
        .attendance-options {
            display: flex;
            justify-content: space-around;
            margin-bottom: 1rem;
        }
        .attendance-option {
            text-align: center;
            cursor: pointer;
        }
        .attendance-option i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        .attendance-option.active {
            color: #007bff;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">MESCO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-clock"></i> Check Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-user"></i> My Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div id="map"></div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="content-wrapper">
                    <div class="text-center logo">
                        <img src="assest/images/MESCO.png" alt="MESCO Logo" class="img-fluid">
                    </div>
                    <div class="text-center mb-4">
                        <div class="datetime" id="datetime"></div>
                    </div>
                    <h2 class="text-center mb-4">Welcome <?php echo htmlspecialchars($username); ?></h2>
                    <div class="reminder text-center mb-4">
                        <p class="reminder-note">If you have marked as <b>"In"</b>, please remember to mark as <b>"Out"</b> before leaving.</p>
                    </div>
                    <div class="attendance-options">
                        <div class="attendance-option" onclick="selectOption('in')">
                            <i class="fas fa-sign-in-alt"></i>
                            <div>In</div>
                        </div>
                        <div class="attendance-option" onclick="selectOption('out')">
                            <i class="fas fa-sign-out-alt"></i>
                            <div>Out</div>
                        </div>
                        <div class="attendance-option" onclick="selectOption('office')">
                            <i class="fas fa-building"></i>
                            <div>Office</div>
                        </div>
                        <div class="attendance-option" onclick="selectOption('outdoor')">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>Outdoor</div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button onclick="giveAttendance()" class="btn btn-primary btn-lg">
                            Give attendance
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
    <script>
        let map;
        let marker;
        let selectedOptions = {
            type: null,
            mode: null
        };

        function initMap() {
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: -34.397, lng: 150.644 },
                zoom: 8,
            });

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const pos = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude,
                        };
                        map.setCenter(pos);
                        map.setZoom(15);
                        marker = new google.maps.Marker({
                            position: pos,
                            map: map,
                            title: "Your Location"
                        });
                    },
                    () => {
                        handleLocationError(true, map.getCenter());
                    }
                );
            } else {
                handleLocationError(false, map.getCenter());
            }
        }

        function handleLocationError(browserHasGeolocation, pos) {
            console.log(browserHasGeolocation ? "Error: The Geolocation service failed." : "Error: Your browser doesn't support geolocation.");
        }

        function updateDateTime() {
            const now = new Date();
            document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit', 
                hour12: true 
            });
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();
        initMap();
    </script>
</body>
</html>