<?php
require '../vendor/pclzip.lib.php'; // Ensure the path is correct

session_start();
session_regenerate_id(true);

date_default_timezone_set('Asia/Kolkata'); // Set timezone to IST

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debugging output
var_dump($_SESSION);

// Redirect if user is not logged in or is not an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}

// Set the correct path to the Selfies_in&out folder
$selfies_path = 'Selfies_in&out/';

// Create a temporary file for the zip
$temp_file = tempnam(sys_get_temp_dir(), 'selfies_');

// Ensure the temporary file is writable
if (!is_writable($temp_file)) {
    die('Cannot write to the temporary file');
}

// Create a new PclZip object
$zip = new PclZip($temp_file);

// Function to add files from a directory to the zip
function addFolderToZip($folder, $zipFile, $subfolder = '')
{
    $files = [];
    if ($handle = opendir($folder)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $entryPath = $folder . $entry;
                if (is_dir($entryPath)) {
                    // If it's a directory, recursively add its contents
                    addFolderToZip($entryPath . '/', $zipFile, $subfolder . $entry . '/');
                } else {
                    // If it's a file, add it to the zip with the new folder structure
                    $fileInfo = pathinfo($entryPath);
                    $fileMTime = filemtime($entryPath);

                    // Extract username from the current subfolder
                    $pathParts = explode('/', trim($subfolder, '/'));
                    $username = $pathParts[0] ?? '';

                    // Generate month and date based on file modification time
                    $month = date('F', $fileMTime); // Full month name
                    $newDate = date('d-m-y', $fileMTime);

                    // Create the new path: username/month/dd-mm-yy/filename
                    $newPath = $username . '/' . $month . '/' . $newDate . '/' . $fileInfo['basename'];

                    // Add file to the list of files to add to zip
                    $files[] = [
                        'name' => $newPath,
                        'file' => $entryPath,
                    ];
                }
            }
        }
        closedir($handle);
    }
    if (!$zipFile->add($files)) {
        die('Failed to add files to zip');
    }
}

// Add all files from the Selfies_in&out folder to the zip
addFolderToZip($selfies_path, $zip);

// Close the zip file to finalize it
if (!$zip->create($temp_file)) {
    die('Failed to create the zip file');
}

// Set headers for download
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=selfies.zip");
header("Content-Length: " . filesize($temp_file));

// Output the file
readfile($temp_file);

// Delete the temporary file
unlink($temp_file);
exit;
