<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include("../assest/connection/config.php");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];
$mode = filter_input(INPUT_POST, 'mode', FILTER_SANITIZE_STRING);
$scanType = filter_input(INPUT_POST, 'scanType', FILTER_SANITIZE_STRING);
$data1 = filter_input(INPUT_POST, 'data1', FILTER_SANITIZE_STRING);
$timestamp = date('Y-m-d H:i:s');
$date = date('Y-m-d');

$response = ['status' => 'error', 'message' => 'An error occurred'];

try {
    $selfie_path = null;
    if (isset($_FILES['selfie_' . $scanType]) && $_FILES['selfie_' . $scanType]['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../Selfies_in&out/' . $_SESSION['username'] . '/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = $_SESSION['username'] . '_' . $scanType . '_' . date('Ymd_His') . '.jpg';
        $selfie_path = $upload_dir . $file_name;
        move_uploaded_file($_FILES['selfie_' . $scanType]['tmp_name'], $selfie_path);
    }

    if ($mode === 'office') {
        // Implement office attendance logic
    } elseif ($mode === 'outdoor') {
        $coords = explode(',', $data1);
        $latitude = filter_var($coords[0], FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($coords[1], FILTER_VALIDATE_FLOAT);

        if ($scanType === "in") {
            $sql = "INSERT INTO attendance (user_id, mode, latitude, longitude, in_time, selfie_in) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE in_time = VALUES(in_time), selfie_in = VALUES(selfie_in)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isddss", $user_id, $mode, $latitude, $longitude, $timestamp, $selfie_path);
        } elseif ($scanType === "out") {
            $sql = "UPDATE attendance 
                    SET out_time = ?, selfie_out = ? 
                    WHERE user_id = ? AND mode = ? AND DATE(in_time) = DATE(?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssiss", $timestamp, $selfie_path, $user_id, $mode, $date);
        } else {
            throw new Exception("Invalid scan type.");
        }

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Attendance recorded successfully.';
        } else {
            throw new Exception("Failed to record attendance.");
        }
    } else {
        throw new Exception("Invalid mode.");
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $response['message'] = 'An unexpected error occurred';
} finally {
    $conn->close();
}

echo json_encode($response);
?>