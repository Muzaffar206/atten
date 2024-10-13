<?php
session_regenerate_id(true); 
include("../assest/connection/config.php");

date_default_timezone_set('Asia/Kolkata');

// Ensure user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Define the cutoff time for old selfies (45 days ago)
$fortyFiveDaysAgo = date('Y-m-d H:i:s', strtotime('-45 days'));

// Display alert for old selfies
function displayAlert() {
    global $conn;
    
    // Check the last deletion date
    $sqlLastDeletion = "SELECT MAX(last_deletion) as last_deletion FROM deletion_log";
    $resultLastDeletion = $conn->query($sqlLastDeletion);
    $rowLastDeletion = $resultLastDeletion->fetch_assoc();
    $lastDeletion = $rowLastDeletion['last_deletion'];
    
    // If there's no last deletion or it's been more than 45 days since the last deletion
    if (!$lastDeletion || strtotime($lastDeletion) < strtotime('-45 days')) {
        // Check for old selfies in the database
        $sql = "SELECT COUNT(*) as old_selfie_count 
                FROM attendance 
                WHERE (selfie_in IS NOT NULL OR selfie_out IS NOT NULL) 
                AND (in_time < ? OR out_time < ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo "An error occurred. Please try again later.";
            exit();
        }
        $stmt->bind_param('ss', $fortyFiveDaysAgo, $fortyFiveDaysAgo);
        if (!$stmt->execute()) {
            echo "An error occurred. Please try again later.";
            exit();
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $oldSelfiesExist = ($row['old_selfie_count'] > 0);
        $stmt->close();

        if ($oldSelfiesExist) {
            echo '<div id="deleteSelfieAlert" class="alert alert-warning alert-dismissible fade show" role="alert">
                    <strong>Reminder!</strong> Please delete selfies older than 45 days.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>';
        }
    }
}

// Check for old selfies in the database
$sql = "SELECT COUNT(*) as old_selfie_count 
        FROM attendance 
        WHERE (selfie_in IS NOT NULL OR selfie_out IS NOT NULL) 
        AND (in_time < ? OR out_time < ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "An error occurred. Please try again later.";
    exit();
}
$stmt->bind_param('ss', $fortyFiveDaysAgo, $fortyFiveDaysAgo);
if (!$stmt->execute()) {
    echo "An error occurred. Please try again later.";
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

    // Ensure the directory exists
    if (!is_dir($selfiesDir)) {
        echo "Directory does not exist.";
        exit();
    }

    // Function to recursively delete old files
    function deleteOldFiles($dir, $fortyFiveDaysAgo, &$deletedSelfiesCount) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . $file;
            if (is_dir($filePath)) {
                deleteOldFiles($filePath . '/', $fortyFiveDaysAgo, $deletedSelfiesCount);
            } else {
                $fileModTime = filemtime($filePath);
                if ($fileModTime < strtotime($fortyFiveDaysAgo)) {
                    if (unlink($filePath)) {
                        $deletedSelfiesCount++;
                    } else {
                        echo "Failed to delete file: " . $filePath;
                    }
                }
            }
        }
    }

    // Delete old files from the file system
    deleteOldFiles($selfiesDir, $fortyFiveDaysAgo, $deletedSelfiesCount);

    // Update database to NULL only for selfies older than 45 days
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
        echo "An error occurred. Please try again later.";
        exit();
    }
    $stmtUpdate->bind_param('ssss', $fortyFiveDaysAgo, $fortyFiveDaysAgo, $fortyFiveDaysAgo, $fortyFiveDaysAgo);
    if (!$stmtUpdate->execute()) {
        echo "An error occurred. Please try again later.";
        exit();
    }
    $affectedRows = $stmtUpdate->affected_rows;
    $stmtUpdate->close();

    // Log the deletion
    if ($deletedSelfiesCount > 0 || $affectedRows > 0) {
        $currentTime = date('Y-m-d H:i:s');
        $sqlLogUpdate = "INSERT INTO deletion_log (last_deletion) VALUES (?)";
        $stmtLogUpdate = $conn->prepare($sqlLogUpdate);
        if (!$stmtLogUpdate) {
            echo "An error occurred. Please try again later.";
            exit();
        }
        $stmtLogUpdate->bind_param('s', $currentTime);
        if (!$stmtLogUpdate->execute()) {
            echo "An error occurred. Please try again later.";
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
