// Cache frequently accessed DOM elements
const cameraElement = document.getElementById("camera");
const videoElement = document.createElement("video");
const canvasElement = document.createElement("canvas");
const context = canvasElement.getContext("2d");


function enableAttendance() {
  const mode = document.querySelector('input[name="attendance_mode"]:checked')?.value;
  const type = document.querySelector('input[name="scheme"]:checked')?.value;
  if (!type) {
    alert("Please select In or Out.");
    return;
  }

  if (type === "In") {
    // Check for existing attendance
    fetch('check_attendance.php')
      .then(response => response.json())
      .then(data => {
        if (data.exists) {
          if (confirm("You have already given attendance for today. Do you want to give attendance again?")) {
            proceedWithAttendance(mode, type);
          }
        } else {
          proceedWithAttendance(mode, type);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert("An error occurred while checking attendance. Please try again.");
      });
  } else {
    proceedWithAttendance(mode, type);
  }
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
}

function showLoadingScreen(message = "Loading...") {
  const overlay = document.createElement("div");
  overlay.className = "loading-overlay";
  overlay.innerHTML = `
      <div class="loading-text">${message}</div>
      <div class="loading-message"></div>
  `;
  document.body.appendChild(overlay);
}

function updateLoadingScreen(message, percentage) {
  const overlay = document.querySelector(".loading-overlay");
  if (overlay) {
      const textElement = overlay.querySelector(".loading-text");
      const messageElement = overlay.querySelector(".loading-message");
      
      textElement.textContent = message;
      messageElement.style.width = `${percentage}%`;
  }
}

function hideLoadingScreen() {
  const overlay = document.querySelector(".loading-overlay");
  if (overlay) {
    document.body.removeChild(overlay);
  }
}

function getLocationForOffice(scanType) {
  showLoadingScreen("Please wait checking your location");
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => showPositionForOffice(position, scanType),
      showError,
      { enableHighAccuracy: true }
    );
  } else {
    hideLoadingScreen();
    alert("Geolocation is not supported by this browser.");
  }
}

function showPositionForOffice(position, scanType) {
  const lat = position.coords.latitude;
  const lon = position.coords.longitude;
  console.log(`Current Position: Lat=${lat}, Lon=${lon}`);

  const officeLocations = [
    { name: "Muzaffar Home", lat: 19.038538, lon: 72.851958, radius: 0.2 },
    { name: "Muzaffar Home", lat: 19.074870, lon: 72.885557, radius: 0.2 },
    { name: "Mesco Landmark", lat: 19.035677, lon: 72.847581, radius: 0.02 },
    { name: "Natalwala", lat: 19.038842, lon: 72.8393, radius: 0.02 },
    { name: "RC Mahim", lat: 19.040013, lon: 72.840608, radius: 0.03 },
    { name: "Study Centre", lat: 19.040176, lon: 72.839605, radius: 0.03 },
    { name: "Clinics", lat: 19.174013, lon: 73.021686, radius: 0.03 },
    { name: "Clinics Physiotherapy", lat: 19.174070, lon: 73.021912, radius: 0.03 },
    { name: "NP Thane Unit", lat: 19.160409, lon: 73.025730, radius: 0.03 }
  ];

  const withinRange = officeLocations.some(location => {
    const distance = getDistanceFromLatLonInKm(lat, lon, location.lat, location.lon);
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
    alert("You are not in any of the specified office locations.");
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
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        const coordString = `${lat},${lon}`;
        showSelfieButton("Outdoor", coordString, scanType);
      },
      showError
    );
  } else {
    hideLoadingScreen();
    alert("Geolocation is not supported by this browser.");
  }
}

function showError(error) {
  hideLoadingScreen();
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
  const R = 6371; // Radius of the earth in km
  const dLat = deg2rad(lat2 - lat1);
  const dLon = deg2rad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
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
      { fps: 10,qrbox: { width: 250, height: 250 }, aspectRatio: 1.0,
      disableFlip: false,
      experimentalFeatures: {
        useBarCodeDetectorIfSupported: true
      } },
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
          html5QrCode.stop().then(() => {
            overlay.remove();
            showSelfieButton("Office", qrCodeMessage, scanType);
          }).catch(err => console.log("Unable to stop scanning.", err));
        }, 1000);
      },
      (errorMessage) => {
        // Handle error if necessary
      }
    )
    .catch(err => {
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
  showLoadingScreen("Initializing camera...");
  navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
    .then(stream => {
      videoElement.srcObject = stream;
      videoElement.onloadedmetadata = () => {
        videoElement.play();
        canvasElement.width = videoElement.videoWidth;
        canvasElement.height = videoElement.videoHeight;
        updateLoadingScreen("Capturing selfie...", 20);
        setTimeout(() => {
          context.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
          const selfie = canvasElement.toDataURL("image/png");
          updateLoadingScreen("Processing image...", 40);
          logAttendance(mode, data1, null, selfie, scanType);
          stream.getTracks().forEach(track => track.stop());
        }, 300); // capture after 1 second
      };
    })
    .catch(err => {
      hideLoadingScreen();
      alert("Error accessing camera: " + err.message);
    });
}

function logAttendance(mode, data1, data2, selfie, scanType) {
  updateLoadingScreen("Sending data to server...", 60);
  const xhr = new XMLHttpRequest();
  xhr.open("POST", "log_attendance.php", true);
  xhr.onreadystatechange = function () {
    if (xhr.readyState === XMLHttpRequest.DONE) {
      hideLoadingScreen();
      try {
        const response = JSON.parse(xhr.responseText.trim());
        if (response.status === "success") {
          showSuccessIcon();
          playSuccessSound();
          vibrate();
        } else {
          showErrorIcon();
        }        
      } catch (e) {
        console.error("Error parsing response:", e);
        alert("An error occurred. Please try again.");
      }
    }
  };
  function playSuccessSound() {
    const audio = document.getElementById("successSound");
    audio.play();
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
  function vibrate() {
    if ("vibrate" in navigator) {
      // Vibrate for 200ms
      navigator.vibrate(200);
    } else {
      console.log("Vibration not supported on this device");
    }
  }
  const formData = new FormData();
  formData.append("mode", mode);
  formData.append("data1", data1);
  formData.append("scanType", scanType);
  if (selfie) {
    const blob = dataURLToBlob(selfie);
    formData.append(scanType === "In" ? "selfie_in" : "selfie_out", blob, scanType === "In" ? "selfie_in.png" : "selfie_out.png");
  }

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
