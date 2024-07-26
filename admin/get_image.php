<?php
// Place this code in a new PHP file, e.g., 'get_image.php'

session_start();

// Check if the user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// Validate and sanitize the image path
$image_path = filter_input(INPUT_GET, 'path', FILTER_SANITIZE_STRING);
if (!$image_path || !file_exists($image_path)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Determine MIME type
$mime_type = mime_content_type($image_path);

// Validate MIME type (adjust as needed for your image types)
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($mime_type, $allowed_types)) {
    header("HTTP/1.0 403 Forbidden");
    exit;
}

// Output the image
header("Content-Type: $mime_type");
readfile($image_path);