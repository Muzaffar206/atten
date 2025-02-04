<?php
session_start();
session_regenerate_id(true);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include("../assest/connection/config.php");

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    
    // Update the user record to remove deleted_at timestamp
    $sql = "UPDATE users SET deleted_at = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User has been recovered successfully.";
    } else {
        $_SESSION['error_message'] = "Error recovering user. Please try again.";
    }
    
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Invalid request.";
}

$conn->close();
header("Location: users.php");
exit();