<?php
session_start();
session_regenerate_id(true);

include("assest/connection/config.php");

// Clear the remember token from the database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Unset all session variables
$_SESSION = array();

// If the session was propagated using a cookie, remove the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Remove the "Remember Me" cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Close the database connection
$conn->close();

// Redirect to the login page
header("Location: login.php");
exit();
?>
