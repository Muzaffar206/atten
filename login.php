<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'root', '', 'attendance_system');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (isDeviceIDExists()) {
        $storedDeviceID = getStoredDeviceID();
        $currentDeviceID = generateDeviceID();

        if ($storedDeviceID !== $currentDeviceID) {
            echo "You cannot log in with another person's ID on this device.";
            exit();
        }
    }
   
    else {
        // Store current device ID in session
        $currentDeviceID = generateDeviceID();
        storeDeviceID($currentDeviceID);
    }

    
    $username = $_POST['username'];
    $password = $_POST['password'];

    $conn = new mysqli('localhost', 'root', '', 'attendance_system');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

<<<<<<< HEAD
    
   
   
=======
>>>>>>> 4c8284e7294b48bf9ceb916f10e4d27a2864c664
    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role']; // Set role in session
            header("Location: home.php");
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "No user found.";
    }

    $conn->close();
}
?>
<form method="post" action="">
    Username: <input type="text" name="username" required><br>
    Password: <input type="password" name="password" required><br>
    <input type="submit" value="Login">
</form>
