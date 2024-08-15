const workDuration = 7 * 60 * 60 * 1000; // 7 hours in milliseconds
const inactivityThreshold = 20000; // 20 seconds
let workStartTime = null; // Track when the work starts

// Request notification permission on page load
function requestNotificationPermission() {
    if ("Notification" in window) {
        if (Notification.permission === "default") {
            Notification.requestPermission().then(function(permission) {
                if (permission === "granted") {
                    console.log("Notification permission granted.");
                }
            });
        }
    }
}

// Check if user is logged in
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

// Function to check time and show notification
function checkWorkCompletion() {
    const now = new Date().getTime();
    const loggedIn = getCookie('remember_me');
    const timeSinceLastInteraction = now - lastInteractionTime; // in milliseconds

    if (loggedIn && workStartTime) {
        const workTimeElapsed = now - workStartTime;

        // If 7 hours of work is completed, notify the user to mark attendance for "out"
        if (workTimeElapsed >= workDuration) {
            if (timeSinceLastInteraction >= inactivityThreshold) {
                showNotification("Attendance Reminder", "You've completed 7 hours of work. Don't forget to mark your attendance for 'out'.");
            }
        }
    }
}

// Function to show notification
function showNotification(title, message) {
    if ("Notification" in window) {
        if (Notification.permission === "granted") {
            new Notification(title, { body: message });
        } else if (Notification.permission !== "denied") {
            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    new Notification(title, { body: message });
                }
            });
        }
    }
}

// Update last interaction time
function updateLastInteractionTime() {
    lastInteractionTime = new Date().getTime();
}

// Start work timer when the user logs in
function startWorkTimer() {
    if (getCookie('remember_me')) {
        workStartTime = new Date().getTime(); // Record the start time of work
    }
}

// Track user activity
document.addEventListener('mousemove', updateLastInteractionTime);
document.addEventListener('keydown', updateLastInteractionTime);

// Request notification permission on page load
requestNotificationPermission();

// Initialize times
let lastInteractionTime = new Date().getTime();
startWorkTimer(); // Start the work timer when the page loads

// Check every second for work completion
setInterval(checkWorkCompletion, 1000);

// Initial check
checkWorkCompletion();
