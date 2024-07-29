const notificationInterval = 5 * 60 * 1000; // 5 minutes in milliseconds
    const inactivityThreshold = 20000; // 20 seconds
    const startTime = 8 * 60 + 50; // 08:50
    const endTime = 9 * 60 + 0; // 09:00

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
    function checkAttendanceTime() {
        const now = new Date();
        const hours = now.getHours();
        const minutes = now.getMinutes();
        const currentTime = hours * 60 + minutes;

        const timeSinceLastInteraction = (now.getTime() - lastInteractionTime); // in milliseconds

        // Check if user is logged in and within time range
        const loggedIn = getCookie('remember_me');
        const shouldNotify = loggedIn && currentTime >= startTime && currentTime <= endTime;

        if (shouldNotify) {
            if (timeSinceLastNotification >= notificationInterval) {
                showNotification("Attendance Reminder", "Please mark your attendance soon to avoid a late remark.");
                lastNotificationTime = now.getTime();
            } else if (timeSinceLastInteraction >= inactivityThreshold) {
                showNotification("Attendance Reminder", "Please mark your attendance soon to avoid a late remark.");
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

    // Track user activity
    document.addEventListener('mousemove', updateLastInteractionTime);
    document.addEventListener('keydown', updateLastInteractionTime);

    // Request notification permission on page load
    requestNotificationPermission();

    // Initialize times
    let lastInteractionTime = new Date().getTime();
    let lastNotificationTime = new Date().getTime();

    // Check every second
    setInterval(checkAttendanceTime, 1000);

    // Initial check
    checkAttendanceTime();