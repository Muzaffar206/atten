<?php
session_start();
session_regenerate_id(true);
date_default_timezone_set('Asia/Kolkata');
header("Access-Control-Allow-Origin: *"); // Allows requests from any origin
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Allows specified HTTP methods
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Allows specified headers

// Your existing PHP code

header('Content-Type: application/json'); // Set content type to JSON
include("../assest/connection/config.php");

// CSRF token generation (Optional)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    

    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = trim(mysqli_real_escape_string($conn, $_POST['password']));

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if account is locked
        if ($row['lockout_time'] && strtotime($row['lockout_time']) > time()) {
            echo json_encode(['success' => false, 'message' => 'Account locked due to too many failed attempts. Please try again later.']);
        } else {
            // Verify the password
            if (password_verify($password, $row['password'])) {
                // Reset failed attempts and lockout time
                $sql_reset = "UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE username = ?";
                $stmt_reset = $conn->prepare($sql_reset);
                $stmt_reset->bind_param("s", $username);
                $stmt_reset->execute();

                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                session_regenerate_id(true);

                echo json_encode(['success' => true, 'message' => 'Login successful', 'role' => $row['role']]);
            } else {
                // Increment failed attempts
                $failed_attempts = $row['failed_attempts'] + 1;
                $lockout_time = $failed_attempts >= 5 ? date("Y-m-d H:i:s", strtotime("+15 minutes")) : NULL;

                $sql_update = "UPDATE users SET failed_attempts = ?, lockout_time = ? WHERE username = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("iss", $failed_attempts, $lockout_time, $username);
                $stmt_update->execute();

                echo json_encode(['success' => false, 'message' => 'Wrong username or password']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No users found']);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
