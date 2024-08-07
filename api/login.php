<?php
session_start();
date_default_timezone_set('Asia/Kolkata');
include("../assest/connection/config.php");

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Prevent direct access to this file
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

// Validate and sanitize input
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_UNSAFE_RAW);

if (!$username || !$password) {
    http_response_code(400);
    exit(json_encode(['status' => 'error', 'message' => 'Invalid input']));
}

$response = ['status' => 'error', 'message' => 'An error occurred'];

try {
    $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $response['status'] = 'success';
            $response['role'] = $row['role'];
        } else {
            $response['message'] = 'Incorrect username or password';
        }
    } else {
        $response['message'] = 'Incorrect username or password';
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $response['message'] = 'An unexpected error occurred';
} finally {
    $conn->close();
}

echo json_encode($response);
?>