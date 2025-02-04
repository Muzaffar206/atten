<?php
session_start();
session_regenerate_id(true); // Regenerate session ID to prevent session fixation

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}

include("../assest/connection/config.php");

// Validate and sanitize input
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $id = intval($_GET['id']);

    // Prepare SQL statement to prevent SQL injection
    $sql = "UPDATE users SET deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Error preparing the statement
        $_SESSION['error_message'] = "Error preparing the statement.";
        header("Location: users.php");
        exit();
    }

    $stmt->bind_param("i", $id);

    // Execute the statement
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User deleted successfully.";
    } else {
        $_SESSION['error_message'] = "Error executing the query.";
    }

    $stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid user ID.";
}

$conn->close();
header("Location: users.php");
exit();
?>
