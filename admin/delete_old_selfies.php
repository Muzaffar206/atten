<?php

include("../assest/connection/config.php");

date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Check for selfies older than 2 minutes
$twoMinutesAgo = date('Y-m-d H:i:s', strtotime('-2 minutes'));

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

function handle_error($error_message) {
    $log_file = "error_log.txt"; // Ensure this directory exists and is writable
    $current_time = date('Y-m-d H:i:s');
    $log_message = "[{$current_time}] ERROR: {$error_message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    echo "An error occurred. Please check the logs for details.";
}

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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_selfies'])) {
    // Query to get old selfies
    $sqlGetSelfies = "SELECT user_id, selfie_in, selfie_out 
                      FROM attendance 
                      WHERE (selfie_in IS NOT NULL OR selfie_out IS NOT NULL) 
                      AND (in_time < ? OR out_time < ?)";
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
        $userId = $row['user_id'];
        $selfieIn = $row['selfie_in'];
        $selfieOut = $row['selfie_out'];

        // Define file paths using paths stored in the database
        $selfieInPath = "../uploads/selfies/" . basename($selfieIn);
        $selfieOutPath = "../uploads/selfies/" . basename($selfieOut);

        // Debugging: Log file paths
        error_log("Deleting selfie_in file: " . $selfieInPath);
        error_log("Deleting selfie_out file: " . $selfieOutPath);

        // Delete files if they exist
        if (file_exists($selfieInPath)) {
            if (unlink($selfieInPath)) {
                $deletedSelfiesCount++;
            } else {
                handle_error("Failed to delete file: " . $selfieInPath);
            }
        }
        if (file_exists($selfieOutPath)) {
            if (unlink($selfieOutPath)) {
                $deletedSelfiesCount++;
            } else {
                handle_error("Failed to delete file: " . $selfieOutPath);
            }
        }

        // Update database to NULL
        $sqlUpdate = "UPDATE attendance 
                      SET selfie_in = NULL, selfie_out = NULL 
                      WHERE user_id = ? 
                      AND (selfie_in = ? OR selfie_out = ?)";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        if (!$stmtUpdate) {
            handle_error("Prepare statement for updating selfies failed: " . $conn->error);
            exit();
        }
        $stmtUpdate->bind_param('iss', $userId, $selfieIn, $selfieOut);
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
        echo "Selfies deleted successfully.";
    } else {
        echo "No selfies were deleted.";
    }

    $stmtGetSelfies->close();
    header("Location: attendance_report.php");
    exit();
}

?>
