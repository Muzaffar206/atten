// Cache frequently accessed DOM elements
const cameraElement = document.getElementById("camera");
const videoElement = document.createElement("video");
const canvasElement = document.createElement("canvas");
const context = canvasElement.getContext("2d");

function enableAttendance() {
  const mode = document.querySelector(
    'input[name="attendance_mode"]:checked'
  )?.value;
  const type = document.querySelector('input[name="scheme"]:checked')?.value;
  if (!mode || !type) {
    Swal.fire({
      icon: 'warning',
      title: 'Oops...',
      text: 'Please select both attendance mode and In/Out.',
    });
    return;
  }

  checkAttendanceStatus(mode, type);
}

function checkAttendanceStatus(mode, type) {
  fetch("check_attendance.php")
    .then((response) => response.json())
    .then((data) => {
      if (type === "In") {
        if (data[`${mode}_in`]) {
          Swal.fire({
            icon: 'info',
            title: 'Already Marked',
            text: `You have already marked ${mode} attendance for today.`,
          });
        } else if (data.office_in && data.outdoor_in) {
          Swal.fire({
            icon: 'info',
            title: 'Already Marked',
            text: 'You have already marked both office and outdoor attendance for today.',
          });
        } else {
          proceedWithAttendance(mode, type);
        }
      } else if (type === "Out") {
        if (data[`${mode}_out`]) {
          Swal.fire({
            icon: 'info',
            title: 'Already Marked',
            text: `You have already marked ${mode} out attendance for today.`,
          });
        } else if (!data[`${mode}_in`]) {
          Swal.fire({
            icon: 'warning',
            title: 'Mark In First',
            text: `You need to mark ${mode} in attendance before marking out.`,
          });
        } else {
          proceedWithAttendance(mode, type);
        }
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'An error occurred while checking attendance. Please try again.',
      });
    });
}

function proceedWithAttendance(mode, type) {
  if (mode === "office") {
    // Create overlay
    const overlay = document.createElement("div");
    overlay.id = "attendance-overlay";
    overlay.style.position = "fixed";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = "100%";
    overlay.style.height = "100%";
    overlay.style.backgroundColor = "rgba(0, 0, 0, 0.7)";
    overlay.style.backdropFilter = "blur(5px)";
    overlay.style.zIndex = "1000";
    overlay.style.display = "flex";
    overlay.style.justifyContent = "center";
    overlay.style.alignItems = "center";
    document.body.appendChild(overlay);

    // Create container for camera
    const cameraContainer = document.createElement("div");
    cameraContainer.id = "camera-container";
    cameraContainer.style.width = "80%";
    cameraContainer.style.maxWidth = "500px";
    cameraContainer.style.aspectRatio = "1";
    cameraContainer.style.backgroundColor = "#000";
    cameraContainer.style.borderRadius = "10px";
    cameraContainer.style.overflow = "hidden";
    overlay.appendChild(cameraContainer);

    // Move the camera element inside the new container
    cameraContainer.appendChild(cameraElement);
    getLocationForOffice(type);
  } else if (mode === "outdoor") {
    getLocationForOutdoor(type);
  }

  // Add this new function call to show recent activity
  showRecentActivity();
}

function showLoadingScreen(message) {
  // Create the loading screen element
  const loadingScreen = document.createElement("div");
  loadingScreen.className = "loading-screen";
  loadingScreen.innerHTML = `
        <div class="loading-content">
            <div class="loading-message">${message}</div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
    `;
  document.body.appendChild(loadingScreen);
}

function updateLoadingScreen(message, percentage) {
  const loadingScreen = document.querySelector(".loading-screen");
  if (loadingScreen) {
    loadingScreen.querySelector(".loading-message").textContent = message;
    loadingScreen.querySelector(
      ".progress-fill"
    ).style.width = `${percentage}%`;
  }
}

function hideLoadingScreen() {
  const loadingScreen = document.querySelector(".loading-screen");
  if (loadingScreen) {
    loadingScreen.remove();
  }
}

function getLocationForOffice(scanType) {
  showLoadingScreen("Please wait checking your location");
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => showPositionForOffice(position, scanType),
      (error) => {
        hideLoadingScreen();
        handleLocationError(error);
      },
      { enableHighAccuracy: true }
    );
  } else {
    hideLoadingScreen();
    Swal.fire({
      icon: 'error',
      title: 'Geolocation Error',
      text: 'Geolocation is not supported by this browser.',
    });
  }
}

function showPositionForOffice(position, scanType) {
  const lat = position.coords.latitude;
  const lon = position.coords.longitude;
  console.log(`Current Position: Lat=${lat}, Lon=${lon}`);

  const officeLocations = [
    { name: "Mesco Landmark", lat: 19.035677, lon: 72.847581, radius: 0.02 },
    { name: "TEST HOUSE", lat: 18.976891, lon: 73.031354, radius: 0.10 },
    { name: "Natalwala", lat: 19.038842, lon: 72.8393, radius: 0.02 },
    { name: "RC Mahim", lat: 19.040013, lon: 72.840608, radius: 0.03 },
    { name: "Study Centre", lat: 19.040176, lon: 72.839605, radius: 0.03 },
    { name: "Clinics", lat: 19.174013, lon: 73.021686, radius: 0.03 },
    { name: "Clinics Physiotherapy", lat: 19.174070, lon: 73.021912, radius: 0.03 },
    { name: "NP Thane Unit", lat: 19.159877, lon: 73.026609, radius: 0.03 }
  ];

  const withinRange = officeLocations.some((location) => {
    const distance = getDistanceFromLatLonInKm(
      lat,
      lon,
      location.lat,
      location.lon
    );
    console.log(`Distance to ${location.name}: ${distance} km`);
    if (distance < location.radius) {
      showNotification(`You are near ${location.name}`);
      startCamera(scanType);
      return true;
    }
    return false;
  });

  if (!withinRange) {
    hideLoadingScreen();
    const overlay = document.getElementById("attendance-overlay");
    const cameraContainer = document.createElement("div");

    if (cameraContainer) {
      cameraContainer.remove();
    }
    if (overlay) {
      overlay.remove();
    }
    Swal.fire({
      icon: 'error',
      title: 'Location Error',
      text: 'You are not in any of the specified office locations.',
    });
  }
}
function showNotification(message) {
  // Create the notification element
  const notification = document.createElement("div");
  notification.className = "alert alert-info text-center";
  notification.style.position = "fixed";
  notification.style.top = "10px";
  notification.style.left = "50%";
  notification.style.transform = "translateX(-50%)";
  notification.style.zIndex = "1050"; // Higher z-index to ensure it's on top
  notification.textContent = message;

  // Append the notification to the body
  document.body.appendChild(notification);

  // Remove the notification after 2 seconds
  setTimeout(() => {
    notification.remove();
  }, 2000);
}

function getLocationForOutdoor(scanType) {
  showLoadingScreen("Please wait checking your location");
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        const coordString = `${lat},${lon}`;
        hideLoadingScreen();
        showSelfieButton("Outdoor", coordString, scanType);
      },
      (error) => {
        hideLoadingScreen();
        handleLocationError(error);
      }
    );
  } else {
    hideLoadingScreen();
    Swal.fire({
      icon: 'error',
      title: 'Geolocation Error',
      text: 'Geolocation is not supported by this browser.',
    });
  }
}

function showError(error) {
  hideLoadingScreen();
  let errorMessage = '';
  switch (error.code) {
    case error.PERMISSION_DENIED:
      errorMessage = "User denied the request for Geolocation.";
      break;
    case error.POSITION_UNAVAILABLE:
      errorMessage = "Location information is unavailable.";
      break;
    case error.TIMEOUT:
      errorMessage = "The request to get user location timed out.";
      break;
    case error.UNKNOWN_ERROR:
      errorMessage = "An unknown error occurred.";
      break;
  }
  Swal.fire({
    icon: 'error',
    title: 'Location Error',
    text: errorMessage,
  });
}

function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
  const R = 6371; // Radius of the earth in km
  const dLat = deg2rad(lat2 - lat1);
  const dLon = deg2rad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(deg2rad(lat1)) *
      Math.cos(deg2rad(lat2)) *
      Math.sin(dLon / 2) *
      Math.sin(dLon / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c; // Distance in km
}

function deg2rad(deg) {
  return deg * (Math.PI / 180);
}
function startCamera(scanType) {
  hideLoadingScreen();
  const cameraContainer = document.getElementById("camera-container");
  cameraElement.style.display = "block";
  cameraElement.style.width = "100%";
  cameraElement.style.height = "100%";

  // Add a status message element
  const statusMessage = document.createElement("div");
  statusMessage.style.position = "absolute";
  statusMessage.style.bottom = "50px";
  statusMessage.style.left = "50%";
  statusMessage.style.transform = "translateX(-50%)";
  statusMessage.style.color = "white";
  statusMessage.style.backgroundColor = "rgba(0, 0, 0, 0.5)";
  statusMessage.style.padding = "10px";
  statusMessage.style.borderRadius = "5px";
  cameraContainer.appendChild(statusMessage);

  function updateStatus(message) {
    statusMessage.textContent = message;
  }

  const html5QrCode = new Html5Qrcode("camera");

  html5QrCode
    .start(
      { facingMode: "environment" },
      { fps: 10, qrbox: { width: 250, height: 250 } },
      (qrCodeMessage) => {
        // QR Code detected
        updateStatus("QR Code detected!");
        const overlay = document.getElementById("attendance-overlay");
        const qrDetectedMessage = document.createElement("div");
        qrDetectedMessage.textContent = `QR Code detected: ${qrCodeMessage}`;
        qrDetectedMessage.style.backgroundColor = "white";
        qrDetectedMessage.style.padding = "20px";
        qrDetectedMessage.style.borderRadius = "10px";
        qrDetectedMessage.style.position = "absolute";
        qrDetectedMessage.style.top = "20px";
        qrDetectedMessage.style.left = "50%";
        qrDetectedMessage.style.transform = "translateX(-50%)";
        overlay.appendChild(qrDetectedMessage);
        
        
        

        
        setTimeout(() => {
          html5QrCode
            .stop()
            .then(() => {
              overlay.remove();
              showSelfieButton("Office", qrCodeMessage, scanType);
            })
            .catch((err) => console.log("Unable to stop scanning.", err));
        }, 1000);
      },
      (errorMessage) => {
        // Handle error if necessary
      }
    )
    .catch((err) => {
      console.log(`Unable to start scanning, error: ${err}`);
      updateStatus("Error starting camera");
    });

  updateStatus("Scanning for QR code...");
}

function showSelfieButton(mode, data1, scanType) {
  // Create overlay
  const overlay = document.createElement("div");
  overlay.className = "camera-overlay";

  // Create button container
  const buttonContainer = document.createElement("div");
  buttonContainer.className = "button-container";

  // Create button
  const button = document.createElement("button");
  button.className = "capture-button";
  button.textContent = "Click here to Proceed";
  button.onclick = () => {
    captureSelfieAndLogAttendance(mode, data1, scanType);
    document.body.removeChild(overlay); // Remove overlay after capturing selfie
  };

  // Append button to container and container to overlay
  buttonContainer.appendChild(button);
  overlay.appendChild(buttonContainer);

  // Append overlay to body
  document.body.appendChild(overlay);
}

function captureSelfieAndLogAttendance(mode, data1, scanType) {
  showLoadingScreen("Initializing camera...", 20);
  navigator.mediaDevices
    .getUserMedia({ video: { facingMode: "user" } })
    .then((stream) => {
      videoElement.srcObject = stream;
      videoElement.onloadedmetadata = () => {
        videoElement.play();
        canvasElement.width = 240;
        canvasElement.height = 320;
        updateLoadingScreen("Capturing selfie...", 40);
        setTimeout(() => {
          context.drawImage(
            videoElement,
            0,
            0,
            canvasElement.width,
            canvasElement.height
          );
          const selfie = canvasElement.toDataURL("image/jpeg", 0.8); // Compress the image
          updateLoadingScreen("Processing image...", 60);
          logAttendance(mode, data1, null, selfie, scanType);
          stream.getTracks().forEach((track) => track.stop());
        }, 100); // Reduced delay to 100ms
      };
    })
    .catch((err) => {
      hideLoadingScreen();
      Swal.fire({
        icon: 'error',
        title: 'Camera Error',
        text: 'Error accessing camera: ' + err.message,
      });
    });
}

function logAttendance(mode, data1, data2, selfie, scanType) {
  updateLoadingScreen("Sending data to server...", 80);
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "log_attendance.php", true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      try {
        const response = JSON.parse(xhr.responseText.trim());
        if (response.status === "success") {
          updateLoadingScreen("Finalizing...", 100);
          showSuccessIcon();
          playSuccessSound();
          vibrate();
        } else {
          updateLoadingScreen("Error occurred", 100);
          showErrorIcon();
        }
        hideLoadingScreen();
      } catch (e) {
        console.error("Error parsing response:", e);
        hideLoadingScreen();
        Swal.fire({
          icon: 'error',
          title: 'Server Response Error',
          html: `
            <p>An error occurred while processing the server response. This could be due to:</p>
            <ul style="text-align: left;">
              <li>Temporary server issues</li>
              <li>Network connectivity problems</li>
              <li>Outdated app version</li>
            </ul>
            <p>Please try the following:</p>
            <ol style="text-align: left;">
              <li>Check your internet connection</li>
              <li>Refresh the page and try again</li>
              <li>Clear your browser cache and cookies</li>
              <li>If the problem persists, please contact support</li>
            </ol>
          `,
          footer: '<a href="mailto:shaikhmuzaffar206@gmail.com">Contact Support</a>'
        });
      }
    }
  };

  const formData = new FormData();
  formData.append("mode", mode);
  formData.append("data1", data1);
  formData.append("scanType", scanType);
  formData.append(
    scanType === "In" ? "selfie_in" : "selfie_out",
    dataURLToBlob(selfie),
    scanType === "In" ? "selfie_in.jpg" : "selfie_out.jpg"
  );

  xhr.send(formData);
}

function dataURLToBlob(dataURL) {
  const [header, data] = dataURL.split(",");
  const mime = header.match(/:(.*?);/)[1];
  const binary = atob(data);
  const array = [];
  for (let i = 0; i < binary.length; i++) {
    array.push(binary.charCodeAt(i));
  }
  return new Blob([new Uint8Array(array)], { type: mime });
}

function showSuccessIcon() {
  const overlay = document.getElementById("successOverlay");
  overlay.style.display = "block";

  setTimeout(() => {
    overlay.style.display = "none";
  }, 2000);
}

function showErrorIcon() {
  const overlay = document.getElementById("errorOverlay");
  overlay.style.display = "block";

  setTimeout(() => {
    overlay.style.display = "none";
  }, 2000);
}
function playSuccessSound() {
  const audio = document.createElement("audio");
  audio.id = "successSound";
  audio.src = "assest/sounds/success.mp3";
  document.body.appendChild(audio);
  audio.play();
}

function vibrate() {
  if ("vibrate" in navigator) {
    // Vibrate for 200ms
    navigator.vibrate(200);
  } else {
    console.log("Vibration not supported on this device");
  }
}

function handleLocationError(error) {
  // Remove camera container and overlay
  const overlay = document.getElementById("attendance-overlay");
  const cameraContainer = document.getElementById("camera-container");

  if (overlay) {
    overlay.remove();
  }
  if (cameraContainer) {
    cameraContainer.remove();
  }

  let errorMessage = "An unknown error occurred.";
  switch (error.code) {
    case error.PERMISSION_DENIED:
      errorMessage = "User denied the request for Geolocation.";
      break;
    case error.POSITION_UNAVAILABLE:
      errorMessage = "Location information is unavailable.";
      break;
    case error.TIMEOUT:
      errorMessage = "The request to get user location timed out.";
      break;
    case error.UNKNOWN_ERROR:
      errorMessage = "An unknown error occurred.";
      break;
  }
  Swal.fire({
    icon: 'error',
    title: 'Location Error',
    text: errorMessage,
  });
}

function showRecentActivity() {
  fetch("get_recent_activity.php")
    .then(response => response.json())
    .then(data => {
      const recentActivityContainer = document.getElementById("recentActivity");
      recentActivityContainer.innerHTML = `
        <h3>Recent Activity</h3>
        <ul class="list-group">
          ${data.map(activity => `
            <li class="list-group-item">
              <strong>${activity.date}</strong> - ${activity.mode} ${activity.type} at ${activity.time}
            </li>
          `).join('')}
        </ul>
      `;
      recentActivityContainer.style.display = "block";
    })
    .catch(error => {
      console.error("Error fetching recent activity:", error);
    });
}