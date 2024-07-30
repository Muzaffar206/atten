<?php
session_start();
session_regenerate_id(true);

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
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Redirect to the login page
header("Location: login.php");
exit();
?>
