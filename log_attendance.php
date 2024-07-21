<?php
session_start();
session_regenerate_id(true); // Regenerate session ID to prevent session fixation

include("assest/connection/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata'); // Set default timezone to IST

$user_id = $_SESSION['user_id'];
$mode = filter_input(INPUT_POST, 'mode', FILTER_SANITIZE_STRING);
$scanType = filter_input(INPUT_POST, 'scanType', FILTER_SANITIZE_STRING);
$timestamp = date('Y-m-d H:i:s'); // IST timezone timestamp
$date = date('Y-m-d'); // Current date

$selfie_in_path = null;
$selfie_out_path = null;

try {

    // Assuming username is stored in session
    $username = $_SESSION['username'];

    // Directory to store selfies (ensure this directory is protected)
    $userDir = 'uploads/selfies/' . basename($username) . '/';
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
        $selfie_in_filename = $username . '_in_' .$mode. date('Ymd_His') . '.' . $fileExtension;
        $selfie_in_path = $userDir . $selfie_in_filename;
        move_uploaded_file($_FILES['selfie_in']['tmp_name'], $selfie_in_path);
    }

    if (isset($_FILES['selfie_out']) && $_FILES['selfie_out']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = validateFile($_FILES['selfie_out'], $allowedExtensions, $allowedMimeTypes);
        $selfie_out_filename = $username . '_out_' .$mode. date('Ymd_His') . '.' . $fileExtension;
        $selfie_out_path = $userDir . $selfie_out_filename;
        move_uploaded_file($_FILES['selfie_out']['tmp_name'], $selfie_out_path);
    }

    if ($mode === 'Office') {
        $data1 = filter_input(INPUT_POST, 'data1', FILTER_SANITIZE_STRING);
        if ($scanType === "In") {
            $sql = "INSERT INTO attendance (user_id, mode, data, in_time, selfie_in) VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE in_time = VALUES(in_time), selfie_in = VALUES(selfie_in)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $user_id, $mode, $data1, $timestamp, $selfie_in_path);
        } else if ($scanType === "Out") {
            $sql = "UPDATE attendance SET out_time = ?, selfie_out = ? WHERE user_id = ? AND data = ? AND mode = ? AND in_time IS NOT NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiss", $timestamp, $selfie_out_path, $user_id, $data1, $mode);
        } else {
            throw new Exception("Invalid scan type.");
        }
    } else if ($mode === 'Outdoor') {
        $coords = filter_input(INPUT_POST, 'data1', FILTER_SANITIZE_STRING);
        $coords = explode(',', $coords);
        $latitude = filter_var($coords[0], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($coords[1], FILTER_VALIDATE_FLOAT);
        if ($scanType === "In") {
            $sql = "INSERT INTO attendance (user_id, mode, latitude, longitude, in_time, selfie_in) VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE in_time = VALUES(in_time), selfie_in = VALUES(selfie_in)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isddss", $user_id, $mode, $latitude, $longitude, $timestamp, $selfie_in_path);
        } else if ($scanType === "Out") {
            $sql = "UPDATE attendance SET out_time = ?, selfie_out = ? WHERE user_id = ? AND latitude = ? AND longitude = ? AND mode = ? AND in_time IS NOT NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssidds", $timestamp, $selfie_out_path, $user_id, $latitude, $longitude, $mode);
        } else {
            throw new Exception("Invalid scan type.");
        }
    } else {
        throw new Exception("Invalid attendance mode.");
    }

    if ($stmt->execute()) {
        // Update final_attendance table
        if ($scanType === 'In') {
            $finalSql = "INSERT INTO final_attendance (user_id, date, first_in, first_mode)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                            first_in = LEAST(COALESCE(first_in, VALUES(first_in)), VALUES(first_in)),
                            first_mode = IF(COALESCE(first_in, VALUES(first_in)) = VALUES(first_in), VALUES(first_mode), first_mode)";
            $finalStmt = $conn->prepare($finalSql);
            $finalStmt->bind_param("isss", $user_id, $date, $timestamp, $mode);
        } else if ($scanType === 'Out') {
            $finalSql = "INSERT INTO final_attendance (user_id, date, last_out, last_mode)
                         VALUES (?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE 
                            last_out = GREATEST(COALESCE(last_out, VALUES(last_out)), VALUES(last_out)),
                            last_mode = IF(COALESCE(last_out, VALUES(last_out)) = VALUES(last_out), VALUES(last_mode), last_mode)";
            $finalStmt = $conn->prepare($finalSql);
            $finalStmt->bind_param("isss", $user_id, $date, $timestamp, $mode);
        }

        if ($finalStmt->execute()) {
            // Update is_present
            if ($stmt->insert_id) {
                $attendance_id = $stmt->insert_id;
                $is_present = ($scanType === 'In') ? 1 : 0;
                $updateUserSql = "UPDATE attendance SET is_present = ? WHERE id = ?";
                $updateUserStmt = $conn->prepare($updateUserSql);
                $updateUserStmt->bind_param("ii", $is_present, $attendance_id);
                $updateUserStmt->execute();
                $updateUserStmt->close();
            }
            echo json_encode(["status" => "success", "message" => "Attendance logged successfully."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update final attendance."]);
        }
        $finalStmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to log attendance."]);
    }

    $stmt->close();
} catch (Exception $e) {
    // Handle exceptions and log errors
    error_log($e->getMessage());
    echo json_encode(["status" => "error", "message" => "An error occurred. Please try again later."]);
} finally {
    $conn->close();
}
?>
