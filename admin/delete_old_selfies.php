<?php

include("../assest/connection/config.php");

date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check for selfies older than 2 minutes
$twoMinutesAgo = date('Y-m-d H:i:s', strtotime('-2 minutes'));

// Function to handle errors
function handle_error($error_message)
{
    $log_file = "error_log.txt";
    $current_time = date('Y-m-d H:i:s');
    $log_message = "[{$current_time}] ERROR: {$error_message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    echo "An error occurred. Please check the logs for details.";
}

// Function to display alert for old selfies
function displayAlert()
{
    if (isset($_SESSION['old_selfies']) && $_SESSION['old_selfies']) {
        echo '<div id="deleteSelfieAlert" class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Reminder!</strong> Please delete selfies older than 2 minutes.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
    }
}

// Check for old selfies
$sql = "SELECT COUNT(*) as old_selfie_count 
        FROM attendance 
        WHERE (selfie_in IS NOT NULL OR selfie_out IS NOT NULL) 
        AND (in_time < ? OR out_time < ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    handle_error("Prepare statement failed: " . $conn->error);
    exit();
}
$stmt->bind_param('ss', $twoMinutesAgo, $twoMinutesAgo);
if (!$stmt->execute()) {
    handle_error("Execute statement failed: " . $stmt->error);
    exit();
}
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$oldSelfiesExist = ($row['old_selfie_count'] > 0);
$_SESSION['old_selfies'] = $oldSelfiesExist;
$stmt->close();

// Delete old selfies when admin triggers the action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_selfies'])) {
    // Query to get old selfies
    $sqlGetSelfies = "SELECT a.id, a.user_id, a.selfie_in, a.selfie_out, u.username 
                      FROM attendance a
                      JOIN users u ON a.user_id = u.id
                      WHERE (a.selfie_in IS NOT NULL OR a.selfie_out IS NOT NULL) 
                      AND (a.in_time < ? OR a.out_time < ?)";
    $stmtGetSelfies = $conn->prepare($sqlGetSelfies);
    if (!$stmtGetSelfies) {
        handle_error("Prepare statement for getting selfies failed: " . $conn->error);
        exit();
    }
    $stmtGetSelfies->bind_param('ss', $twoMinutesAgo, $twoMinutesAgo);
    if (!$stmtGetSelfies->execute()) {
        handle_error("Execute statement for getting selfies failed: " . $stmtGetSelfies->error);
        exit();
    }
    $resultGetSelfies = $stmtGetSelfies->get_result();

    $deletedSelfiesCount = 0;

    while ($row = $resultGetSelfies->fetch_assoc()) {
        $attendanceId = $row['id'];
        $userId = $row['user_id'];
        $username = $row['username'];
        $selfieIn = $row['selfie_in'];
        $selfieOut = $row['selfie_out'];

        $userDir = 'Selfies_in&out/' . basename($username) . '/';

        // Delete files if they exist
        if (!empty($selfieIn)) {
            $selfieInPath = $userDir . basename($selfieIn);
            if (file_exists($selfieInPath) && unlink($selfieInPath)) {
                $deletedSelfiesCount++;
            } else {
                handle_error("Failed to delete file: " . $selfieInPath);
            }
        }
        if (!empty($selfieOut)) {
            $selfieOutPath = $userDir . basename($selfieOut);
            if (file_exists($selfieOutPath) && unlink($selfieOutPath)) {
                $deletedSelfiesCount++;
            } else {
                handle_error("Failed to delete file: " . $selfieOutPath);
            }
        }

        // Update database to NULL
        $sqlUpdate = "UPDATE attendance 
                      SET selfie_in = NULL, selfie_out = NULL 
                      WHERE id = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            handle_error("Prepare statement for updating selfies failed: " . $conn->error);
            exit();
        }
        $stmtUpdate->bind_param('i', $attendanceId);
        if (!$stmtUpdate->execute()) {
            handle_error("Execute statement for updating selfies failed: " . $stmtUpdate->error);
            exit();
        }
        $stmtUpdate->close();
    }

    if ($deletedSelfiesCount > 0) {
        $currentTime = date('Y-m-d H:i:s');
        $sqlLogUpdate = "INSERT INTO deletion_log (last_deletion) VALUES (?)";
        $stmtLogUpdate = $conn->prepare($sqlLogUpdate);
        if (!$stmtLogUpdate) {
            handle_error("Prepare statement for logging deletion failed: " . $conn->error);
            exit();
        }
        $stmtLogUpdate->bind_param('s', $currentTime);
        if (!$stmtLogUpdate->execute()) {
            handle_error("Execute statement for logging deletion failed: " . $stmtLogUpdate->error);
            exit();
        }
        $stmtLogUpdate->close();

        $_SESSION['old_selfies'] = false;
        $_SESSION['success_message'] = "Selfies deleted successfully.";
    } else {
        $_SESSION['info_message'] = "No selfies were deleted.";
    }

    $stmtGetSelfies->close();
    header("Location: attendance_report.php");
    exit();
}