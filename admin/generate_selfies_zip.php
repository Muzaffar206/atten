<?php
// Disable error reporting
error_reporting(0);
ini_set('display_errors', 0);

// Set the correct path to the Selfies_in&out folder
$selfies_path = 'Selfies_in&out/';

// Create a new ZipArchive object
$zip = new ZipArchive();

// Create a temporary file for the zip
$zip_file = tempnam(sys_get_temp_dir(), 'selfies_');

// Open the zip file
if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // Function to add files from a directory to the zip
    function addFolderToZip($folder, $zipFile, $subfolder = '') {
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
                        
                        $zipFile->addFile($entryPath, $newPath);
                    }
                }
            }
            closedir($handle);
        }
    }

    // Add all files from the Selfies_in&out folder to the zip
    addFolderToZip($selfies_path, $zip);

    // Close the zip file
    if ($zip->close()) {
        // Check if the zip file was created and is not empty
        if (file_exists($zip_file) && filesize($zip_file) > 0) {
            // Set headers for download
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=selfies.zip");
            header("Content-Length: " . filesize($zip_file));

            // Output the file
            readfile($zip_file);

            // Delete the temporary file
            unlink($zip_file);
            exit;
        }
    }
}

// If we reach here, something went wrong, but we don't expose any error messages
header("HTTP/1.1 500 Internal Server Error");
exit;
?>