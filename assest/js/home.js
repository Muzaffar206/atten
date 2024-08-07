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
          (position) => showPositionForOffice(position, scanType),
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
      { name: "Muzaffar Home", lat: 19.038538, lon: 72.851958, radius: 0.2 },
      { name: "Muzaffar Home", lat: 19.074870, lon: 72.885557, radius: 0.2 },
      { name: "Mesco Landmark", lat: 19.035677, lon: 72.847581, radius: 0.02 },
      { name: "Natalwala", lat: 19.038842, lon: 72.8393, radius: 0.02 },
      { name: "RC Mahim", lat: 19.040013, lon: 72.840608, radius: 0.03 },
      { name: "Study Centre", lat: 19.040176, lon: 72.839605, radius: 0.03 },
      { name: "Clinics", lat: 19.174013, lon: 73.021686, radius: 0.03 },
      { name: "Clinics Physiotherapy", lat: 19.174070, lon: 73.021912, radius: 0.03 }
  ];

  var withinRange = false;
  officeLocations.forEach((location) => {
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
      navigator.geolocation.getCurrentPosition(
          (position) => {
              var lat = position.coords.latitude;
              var lon = position.coords.longitude;
              showSelfieButton("Outdoor", `${lat},${lon}`, scanType);
          },
          showError
      );
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
  html5QrCode
      .start(
          { facingMode: "environment" },
          { fps: 10, qrbox: 250 },
          (qrCodeMessage) => {
              alert(`QR Code detected: ${qrCodeMessage}`);
              html5QrCode.stop().then((ignore) => {
                  document.getElementById("camera").style.display = "none";
                  showSelfieButton("Office", qrCodeMessage, scanType);
              }).catch((err) => {
                  console.log("Unable to stop scanning.");
              });
          },
          (errorMessage) => {
              console.log(`QR Code no longer in front of camera.`);
          }
      )
      .catch((err) => {
          console.log(`Unable to start scanning, error: ${err}`);
      });
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

function captureSelfie() {
  const video = document.getElementById("video");
  const canvas = document.getElementById("canvas");
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  const context = canvas.getContext("2d");
  context.drawImage(video, 0, 0, canvas.width, canvas.height);
  const selfie = canvas.toDataURL("image/png");
  return selfie;
}

function captureSelfieAndLogAttendance(mode, data1, scanType) {
  const video = document.createElement("video");
  const canvas = document.createElement("canvas");
  const context = canvas.getContext("2d");

  navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
      .then((stream) => {
          video.srcObject = stream;
          video.onloadedmetadata = () => {
              video.play();
              canvas.width = video.videoWidth;
              canvas.height = video.videoHeight;
              setTimeout(() => {
                  context.drawImage(video, 0, 0, canvas.width, canvas.height);
                  const selfie = canvas.toDataURL("image/png");
                  logAttendance(mode, data1, null, selfie, scanType);
                  stream.getTracks().forEach((track) => track.stop());
              }, 300); // capture in 1 second 
          };
      })
      .catch((err) => {
          console.log("Error accessing webcam: " + err);
          alert("Error accessing camera: " + err.message);
      });
}

function logAttendance(mode, data1, data2, selfie, scanType) {
  var xhr = new XMLHttpRequest();
  xhr.open("POST", "log_attendance.php", true);
  xhr.onreadystatechange = function () {
      if (xhr.readyState == 4) {
          try {
              var response = JSON.parse(xhr.responseText.trim());
              if (response.status === "success") {
                  alert(response.message);
              } else {
                  alert("Error: " + response.message);
              }
          } catch (e) {
              console.error("Error parsing response:", e);
              console.error("Raw response:", xhr.responseText);
              alert("An error occurred. Please try again.");
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
  const byteString = atob(dataURL.split(",")[1]);
  const mimeString = dataURL.split(",")[0].split(":")[1].split(";")[0];
  const ab = new ArrayBuffer(byteString.length);
  const ia = new Uint8Array(ab);
  for (let i = 0; i < byteString.length; i++) {
      ia[i] = byteString.charCodeAt(i);
  }
  return new Blob([ab], { type: mimeString });
}
