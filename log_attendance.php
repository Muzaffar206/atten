<?php
ob_start();
session_start();
session_regenerate_id(true);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("assest/connection/config.php");
include("office_locations.php");  // Add this line to include the office locations

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$user_id = $_SESSION['user_id'];
$mode = isset($_POST['mode']) ? htmlspecialchars($_POST['mode'], ENT_QUOTES, 'UTF-8') : '';
$scanType = isset($_POST['scanType']) ? htmlspecialchars($_POST['scanType'], ENT_QUOTES, 'UTF-8') : '';
$timestamp = date('Y-m-d H:i:s');
$date = date('Y-m-d');

$selfie_in_path = null;
$selfie_out_path = null;

// Define upload directories within allowed paths
$upload_dir = 'C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/';
$temp_dir = 'C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/tmp/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

function sendJsonResponse($status, $message) {
    header('Content-Type: application/json');
    echo json_encode(array('status' => $status, 'message' => $message));
    exit;
}

$allowedExtensions = ['jpg', 'jpeg', 'png'];
$allowedMimeTypes = ['image/jpeg', 'image/png'];

function validateFile($file, $allowedExtensions, $allowedMimeTypes) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("File upload error: " . $file['error']);
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileMimeType = $file['type'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception("Invalid file extension.");
    }

    if (!in_array($fileMimeType, $allowedMimeTypes)) {
        throw new Exception("Invalid file type: " . $fileMimeType);
    }

    return $fileExtension;
}

function optimizeImage($filePath, $maxWidth = 800, $maxHeight = 800) {
    list($width, $height) = getimagesize($filePath);
    $aspectRatio = $width / $height;

    if ($width > $maxWidth || $height > $maxHeight) {
        if ($width / $height > $aspectRatio) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $aspectRatio;
        } else {
            $newWidth = $maxHeight * $aspectRatio;
            $newHeight = $maxHeight;
        }

        $imageResized = imagecreatetruecolor($newWidth, $newHeight);

        $fileMimeType = mime_content_type($filePath);
        switch ($fileMimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($filePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($filePath);
                break;
            default:
                throw new Exception("Unsupported image type: " . $fileMimeType);
        }

        imagecopyresampled($imageResized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        switch ($fileMimeType) {
            case 'image/jpeg':
                imagejpeg($imageResized, $filePath, 75);
                break;
            case 'image/png':
                imagepng($imageResized, $filePath, 6);
                break;
        }

        imagedestroy($image);
        imagedestroy($imageResized);
    }
}

function moveFileToUserDir($tempFilePath, $username, $fileExtension, $mode) {
    $userDir = 'C:/HostingSpaces/mescotrust/attendance.mescotrust.org/wwwroot/admin/Selfies_in&out/' . basename($username) . '/';
    if (!is_dir($userDir)) {
        mkdir($userDir, 0777, true);
    }

    $filename = $username . '_' . $mode . date('Ymd_His') . '.' . $fileExtension;
    $filePath = $userDir . $filename;

    if (!rename($tempFilePath, $filePath)) {
        throw new Exception("Failed to move file to final directory.");
    }

    return $filePath;
}

try {
    $username = $_SESSION['username'];

    $userDir = $upload_dir . basename($username) . '/';
    if (!is_dir($userDir)) {
        mkdir($userDir, 0777, true);
    }

    if (isset($_FILES['selfie_in']) && $_FILES['selfie_in']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = validateFile($_FILES['selfie_in'], $allowedExtensions, $allowedMimeTypes);
        $tempFilePath = $temp_dir . uniqid('selfie_in_', true) . '.' . $fileExtension;
        if (!move_uploaded_file($_FILES['selfie_in']['tmp_name'], $tempFilePath)) {
            throw new Exception("Failed to move uploaded file to temporary directory.");
        }
        optimizeImage($tempFilePath);
        $selfie_in_path = moveFileToUserDir($tempFilePath, $username, $fileExtension, 'in_' . $mode);
    }

    if (isset($_FILES['selfie_out']) && $_FILES['selfie_out']['error'] === UPLOAD_ERR_OK) {
        $fileExtension = validateFile($_FILES['selfie_out'], $allowedExtensions, $allowedMimeTypes);
        $tempFilePath = $temp_dir . uniqid('selfie_out_', true) . '.' . $fileExtension;
        if (!move_uploaded_file($_FILES['selfie_out']['tmp_name'], $tempFilePath)) {
            throw new Exception("Failed to move uploaded file to temporary directory.");
        }
        optimizeImage($tempFilePath);
        $selfie_out_path = moveFileToUserDir($tempFilePath, $username, $fileExtension, 'out_' . $mode);
    }

    if ($mode === 'Office') {
        $data1 = isset($_POST['data1']) ? htmlspecialchars($_POST['data1'], ENT_QUOTES, 'UTF-8') : '';

        // Validate QR code data
        $validQRCode = false;
        foreach ($officeLocations as $location) {
            if ($location['qrCode'] === $data1) {
                $validQRCode = true;
                break;
            }
        }

        if (!$validQRCode) {
            sendJsonResponse('error', 'Invalid QR code for this location.');
        }

        if ($scanType === "In") {
            $sql = "INSERT INTO attendance (user_id, mode, data, in_time, selfie_in) VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE in_time = VALUES(in_time), selfie_in = VALUES(selfie_in),
                    data = CONCAT(COALESCE(data, ''), IF(COALESCE(data, '') = '', '', ','), ?)";
                    
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $user_id, $mode, $data1, $timestamp, $selfie_in_path, $data1);
            
        } else if ($scanType === "Out") {
            $checkSql = "SELECT id, data FROM attendance WHERE user_id = ? AND mode = ? AND DATE(in_time) = DATE(?)";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("iss", $user_id, $mode, $date);
            $checkStmt->execute();
            $checkStmt->bind_result($attendance_id, $existing_data);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($attendance_id) {
                $new_data = $existing_data ? $existing_data . ',' . $data1 : $data1;
            $sql = "UPDATE attendance SET out_time = ?, selfie_out = ?, data = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssi", $timestamp, $selfie_out_path, $new_data, $attendance_id);
            } else {
                sendJsonResponse('error', 'You need to check in before checking out.');
            }
        } else {
            throw new Exception("Invalid scan type.");
        }
    }else if ($mode === 'Outdoor') {
        $coords = explode(',', $_POST['data1']);
        $latitude = filter_var($coords[0], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($coords[1], FILTER_VALIDATE_FLOAT);
    
        if ($scanType === "In") {
            $sql = "INSERT INTO attendance (user_id, mode, in_latitude, in_longitude, in_time, selfie_in) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                    in_time = VALUES(in_time), 
                    selfie_in = VALUES(selfie_in), 
                    in_latitude = VALUES(in_latitude),
                    in_longitude = VALUES(in_longitude)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isddss", $user_id, $mode, $latitude, $longitude, $timestamp, $selfie_in_path);
        } else if ($scanType === "Out") {
            $checkSql = "SELECT id FROM attendance WHERE user_id = ? AND mode = ? AND DATE(in_time) = ?";
            $checkStmt = $conn->prepare($checkSql);
            $date = date('Y-m-d', strtotime($timestamp));
            $checkStmt->bind_param("iss", $user_id, $mode, $date);
            $checkStmt->execute();
            $checkStmt->bind_result($attendance_id);
            $checkStmt->fetch();
            $checkStmt->close();
    
            if ($attendance_id) {
                $sql = "UPDATE attendance 
                        SET out_time = ?, 
                            selfie_out = ?, 
                            out_latitude = ?,
                            out_longitude = ?
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssddi", $timestamp, $selfie_out_path, $latitude, $longitude, $attendance_id);
            } else {
                sendJsonResponse('error', 'Please provide attendance for "In" before marking "Out".');
            }
        } else {
            throw new Exception("Invalid scan type.");
        }
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
        } else {
            throw new Exception("Invalid scan type.");
        }

        if ($finalStmt->execute()) {
            // Update is_present field
            if ($stmt->insert_id) {
                $attendance_id = $stmt->insert_id;
                $is_present = ($scanType === 'In') ? 1 : 0;
                $updateUserSql = "UPDATE attendance SET is_present = ? WHERE id = ?";
                $updateUserStmt = $conn->prepare($updateUserSql);
                $updateUserStmt->bind_param("ii", $is_present, $attendance_id);
                $updateUserStmt->execute();
                $updateUserStmt->close();
            }

            // Calculate total hours
            $calcSql = "SELECT TIMESTAMPDIFF(MINUTE, first_in, last_out) / 60 AS total_hours
                        FROM final_attendance
                        WHERE user_id = ? AND date = ?";
            $calcStmt = $conn->prepare($calcSql);
            $calcStmt->bind_param("is", $user_id, $date);
            $calcStmt->execute();
            $calcStmt->bind_result($total_hours);
            $calcStmt->fetch();
            $calcStmt->close();

            // Update the total_hours column
            $updateFinalSql = "UPDATE final_attendance
                               SET total_hours = ?
                               WHERE user_id = ? AND date = ?";
            $updateFinalStmt = $conn->prepare($updateFinalSql);
            $updateFinalStmt->bind_param("dis", $total_hours, $user_id, $date);

            if ($updateFinalStmt->execute()) {
                sendJsonResponse('success', 'Attendance recorded successfully.');
            } else {
                sendJsonResponse('error', 'Failed to update total hours.');
            }
        } else {
            throw new Exception("Failed to record final attendance.");
        }
    } else {
        throw new Exception("Failed to record attendance.");
    }

    $stmt->execute();
    $stmt->close();
    $finalStmt->close();
    $conn->close();

} catch (Exception $e) {
    error_log($e->getMessage());
    sendJsonResponse('error', 'Error: ' . $e->getMessage());
}
?>