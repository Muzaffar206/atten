<?php
session_start();
session_regenerate_id(true); // Regenerate session ID to prevent session fixation

include("../assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata'); // Set default timezone to IST

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username']; // Assuming username is stored in session

// Define file paths
$selfieInPath = "../uploads/selfies/" . basename($username) . '/' . basename($_FILES['selfie_in']['name']);
$selfieOutPath = "../uploads/selfies/" . basename($username) . '/' . basename($_FILES['selfie_out']['name']);

try {
    // Check if directory exists
    $userDir = dirname($selfieInPath);
    if (!is_dir($userDir)) {
        mkdir($userDir, 0700, true); // Use safer permissions
    }

    // File validation and handling
    $allowedExtensions = ['jpg', 'jpeg', 'png'];
    $allowedMimeTypes = ['image/jpeg', 'image/png'];

    function validateFile($file, $allowedExtensions, $allowedMimeTypes) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error.");
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileMimeType = mime_content_type($file['tmp_name']);

        if (!in_array($fileExtension, $allowedExtensions) || !in_array($fileMimeType, $allowedMimeTypes)) {
            throw new Exception("Invalid file type.");
        }

        return $fileExtension;
    }

    if (isset($_FILES['selfie_in']) && $_FILES['selfie_in']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = validateFile($_FILES['selfie_in'], $allowedExtensions, $allowedMimeTypes);
        $selfieInPath = "../uploads/selfies/" . basename($username) . '/' . basename($username . '_in_' . date('Ymd_His') . '.' . $fileExtension);
        if (move_uploaded_file($_FILES['selfie_in']['tmp_name'], $selfieInPath)) {
            error_log("Selfie IN uploaded successfully: " . $selfieInPath);
        } else {
            error_log("Failed to upload selfie IN: " . $selfieInPath);
        }
    }

    if (isset($_FILES['selfie_out']) && $_FILES['selfie_out']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = validateFile($_FILES['selfie_out'], $allowedExtensions, $allowedMimeTypes);
        $selfieOutPath = "../uploads/selfies/" . basename($username) . '/' . basename($username . '_out_' . date('Ymd_His') . '.' . $fileExtension);
        if (move_uploaded_file($_FILES['selfie_out']['tmp_name'], $selfieOutPath)) {
            error_log("Selfie OUT uploaded successfully: " . $selfieOutPath);
        } else {
            error_log("Failed to upload selfie OUT: " . $selfieOutPath);
        }
    }

    // Insert or Update Database logic here

    // Log database path handling
    error_log("Selfie IN path: " . $selfieInPath);
    error_log("Selfie OUT path: " . $selfieOutPath);

    // For deletion (as needed)
    // if (file_exists($selfieInPath)) {
    //     unlink($selfieInPath);
    // } else {
    //     error_log("File not found for deletion: " . $selfieInPath);
    // }
    // if (file_exists($selfieOutPath)) {
    //     unlink($selfieOutPath);
    // } else {
    //     error_log("File not found for deletion: " . $selfieOutPath);
    // }

} catch (Exception $e) {
    // Handle exceptions and log errors
    error_log($e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred. Please try again later."]);
} finally {
    $conn->close();
}
?>
