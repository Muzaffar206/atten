<?php
session_regenerate_id(true); // Regenerate session ID to prevent session fixation

include("../assest/connection/config.php");

date_default_timezone_set('Asia/Kolkata');

// Ensure user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Reset alert flag if it's a new day
if (!isset($_SESSION['last_alert_date']) || $_SESSION['last_alert_date'] !== date('Y-m-d')) {
    unset($_SESSION['deletion_success_shown']);
}

// Function to check if it's time to delete selfies
function isTimeToDeleteSelfies() {
    global $conn;
    $currentMonth = date('Y-m');
    $sql = "SELECT MAX(last_deletion) as last_deletion FROM deletion_log WHERE DATE_FORMAT(last_deletion, '%Y-%m') = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $currentMonth);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return !$row['last_deletion'] && date('d') >= '05';
}

// Function to display alert
function displayAlert() {
    global $conn;
    
    if (isset($_SESSION['deletion_success_shown']) && $_SESSION['deletion_success_shown'] === true) {
        return;
    }
    
    $sqlLastDeletion = "SELECT MAX(last_deletion) as last_deletion FROM deletion_log";
    $resultLastDeletion = $conn->query($sqlLastDeletion);
    $rowLastDeletion = $resultLastDeletion->fetch_assoc();
    $lastDeletion = $rowLastDeletion['last_deletion'];
    
    if ($lastDeletion) {
        $lastDeletionDate = date('Y-m-d', strtotime($lastDeletion));
        $currentDate = date('Y-m-d');
        
        if ($lastDeletionDate == $currentDate) {
            $message = "Selfies from the previous month have been deleted at " . date('H:i:s', strtotime($lastDeletion)) . ".";
            
            echo '<div id="deleteSelfieAlert" class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> ' . $message . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>';
            $_SESSION['deletion_success_shown'] = true;
            $_SESSION['last_alert_date'] = $currentDate;
        }
    }
}

// Deletion process
if (isTimeToDeleteSelfies()) {
    $deletedSelfiesCount = 0;
    $selfiesDir = 'Selfies_in&out/';

    if (!is_dir($selfiesDir)) {
        error_log("Selfies directory does not exist: $selfiesDir");
        exit();
    }

    $endDate = date('Y-m-t', strtotime('last day of previous month')); // Last day of previous month
    $startDate = date('Y-m-01', strtotime('first day of previous month')); // First day of previous month

    function deleteFilesInRange($dir, $startDate, $endDate, &$deletedSelfiesCount) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            $filePath = $dir . $file;
            if (is_dir($filePath)) {
                deleteFilesInRange($filePath . '/', $startDate, $endDate, $deletedSelfiesCount);
            } else {
                $fileDate = date('Y-m-d', filemtime($filePath));
                if ($fileDate >= $startDate && $fileDate <= $endDate) {
                    if (unlink($filePath)) {
                        $deletedSelfiesCount++;
                    } else {
                        error_log("Failed to delete file: $filePath");
                    }
                }
            }
        }
    }

    deleteFilesInRange($selfiesDir, $startDate, $endDate, $deletedSelfiesCount);

    $sqlUpdate = "UPDATE attendance 
                  SET selfie_in = CASE 
                        WHEN DATE(in_time) BETWEEN ? AND ? THEN NULL 
                        ELSE selfie_in 
                      END,
                      selfie_out = CASE 
                        WHEN DATE(out_time) BETWEEN ? AND ? THEN NULL 
                        ELSE selfie_out 
                      END 
                  WHERE (selfie_in IS NOT NULL AND DATE(in_time) BETWEEN ? AND ?) 
                     OR (selfie_out IS NOT NULL AND DATE(out_time) BETWEEN ? AND ?)";
    $stmtUpdate = $conn->prepare($sqlUpdate);
    if (!$stmtUpdate) {
        error_log("Error preparing statement: " . $conn->error);
        exit();
    }
    $stmtUpdate->bind_param('ssssssss', $startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    if (!$stmtUpdate->execute()) {
        error_log("Error executing statement: " . $stmtUpdate->error);
        exit();
    }
    $affectedRows = $stmtUpdate->affected_rows;
    $stmtUpdate->close();

    if ($deletedSelfiesCount > 0 || $affectedRows > 0) {
        $currentDateTime = date('Y-m-d H:i:s');
        $sqlLogUpdate = "INSERT INTO deletion_log (last_deletion) VALUES (?)";
        $stmtLogUpdate = $conn->prepare($sqlLogUpdate);
        if (!$stmtLogUpdate) {
            error_log("Error preparing log statement: " . $conn->error);
            exit();
        }
        $stmtLogUpdate->bind_param('s', $currentDateTime);
        if (!$stmtLogUpdate->execute()) {
            error_log("Error executing log statement: " . $stmtLogUpdate->error);
            exit();
        }
        $stmtLogUpdate->close();

        $_SESSION['old_selfies'] = false;
        unset($_SESSION['deletion_success_shown']);
        unset($_SESSION['last_alert_date']);
    }
}
?>
