<?php
include("../assest/connection/config.php");

date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$twoMinutesAgo = date('Y-m-d H:i:s', strtotime('-2 minutes'));

function handle_error($error_message) {
    $log_file = "error_log.txt";
    $current_time = date('Y-m-d H:i:s');
    $log_message = "[{$current_time}] ERROR: {$error_message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    echo "An error occurred. Please check the logs for details.";
}

function displayAlert() {
    if (isset($_SESSION['old_selfies']) && $_SESSION['old_selfies']) {
        echo '<div id="deleteSelfieAlert" class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>Reminder!</strong> Please delete selfies older than 2 minutes.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
    }
}

// Check for old selfies in the database
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
    $deletedSelfiesCount = 0;
    $selfiesDir = 'Selfies_in&out/';

    // Function to recursively delete old files
    function deleteOldFiles($dir, $twoMinutesAgo, &$deletedSelfiesCount) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . $file;
            if (is_dir($filePath)) {
                deleteOldFiles($filePath . '/', $twoMinutesAgo, $deletedSelfiesCount);
            } else {
                $fileModTime = filemtime($filePath);
                if ($fileModTime < strtotime($twoMinutesAgo)) {
                    if (unlink($filePath)) {
                        $deletedSelfiesCount++;
                    } else {
                        handle_error("Failed to delete file: " . $filePath);
                    }
                }
            }
        }
    }

    // Delete old files from the file system
    deleteOldFiles($selfiesDir, $twoMinutesAgo, $deletedSelfiesCount);

    // Update database to NULL only for selfies older than 2 minutes
    $sqlUpdate = "UPDATE attendance 
                  SET selfie_in = CASE 
                        WHEN selfie_in IS NOT NULL AND in_time < ? THEN NULL 
                        ELSE selfie_in 
                      END,
                      selfie_out = CASE 
                        WHEN selfie_out IS NOT NULL AND out_time < ? THEN NULL 
                        ELSE selfie_out 
                      END 
                  WHERE (selfie_in IS NOT NULL AND in_time < ?) 
                     OR (selfie_out IS NOT NULL AND out_time < ?)";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        handle_error("Prepare statement for updating selfies failed: " . $conn->error);
        exit();
    }
    $stmtUpdate->bind_param('ssss', $twoMinutesAgo, $twoMinutesAgo, $twoMinutesAgo, $twoMinutesAgo);
    if (!$stmtUpdate->execute()) {
        handle_error("Execute statement for updating selfies failed: " . $stmtUpdate->error);
        exit();
    }
    $affectedRows = $stmtUpdate->affected_rows;
    $stmtUpdate->close();

    if ($deletedSelfiesCount > 0 || $affectedRows > 0) {
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
        $_SESSION['success_message'] = "Selfies deleted successfully. Files deleted: $deletedSelfiesCount, Database records updated: $affectedRows";
    } else {
        $_SESSION['info_message'] = "No selfies were deleted.";
    }

    header("Location: attendance_report.php");
    exit();
}
?>