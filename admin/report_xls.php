<?php
session_start();
session_regenerate_id(true);
include("../assest/connection/config.php");
require '../vendor/autoload.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

function handle_error($error_message) {
    $log_file = "error_log.txt";
    $current_time = date('Y-m-d H:i:s');
    $log_message = "[{$current_time}] ERROR: {$error_message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    error_log($error_message);
}

try {
    $filterDepartment = isset($_GET['department']) ? $_GET['department'] : '';
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

    $sql = "SELECT 
                users.id AS user_id,
                attendance.id AS attendance_id, 
                users.employer_id,
                users.username, 
                users.full_name,
                users.department,
                attendance.mode, 
                attendance.latitude, 
                attendance.longitude,
                attendance.in_time,
                attendance.out_time, 
                attendance.selfie_in, 
                attendance.selfie_out
            FROM attendance 
            JOIN users ON attendance.user_id = users.id
            WHERE users.role <> 'admin'";

    $params = array();
    if (!empty($filterDepartment)) {
        $sql .= " AND users.department = ?";
        $params[] = $filterDepartment;
    }
    if (!empty($startDate) && !empty($endDate)) {
        $sql .= " AND DATE(attendance.in_time) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
    } elseif (!empty($startDate)) {
        $sql .= " AND DATE(attendance.in_time) = ?";
        $params[] = $startDate;
    }

    $sql .= " ORDER BY attendance.id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set up header row
    $headers = ['ID', 'Emp ID', 'Username', 'Full Name', 'Department', 'Mode', 'Latitude', 'Longitude', 'In Time', 'Out Time', 'Selfie In', 'Selfie Out', 'Map'];
    $sheet->fromArray($headers, NULL, 'A1');

    $styleArray = [
        'font' => ['bold' => true, 'size' => 12],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ];

    $sheet->getStyle('A1:M1')->applyFromArray($styleArray);
    $sheet->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');

    $rowNumber = 2;
    $batchSize = 1000;
    $batchCount = 0;

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sheet->fromArray([
                $row['attendance_id'], $row['employer_id'], $row['username'], $row['full_name'],
                $row['department'], $row['mode'], $row['latitude'], $row['longitude'],
                $row['in_time'], $row['out_time']
            ], NULL, 'A' . $rowNumber);

            // Handle selfies
            $inTimestamp = strtotime($row['in_time']);
            $outTimestamp = strtotime($row['out_time']);

            $selfieInFilename = $row['username'] . "_in_" . $row['mode'] . date('Ymd_His', $inTimestamp) . ".jpg";
            $selfieOutFilename = $row['username'] . "_out_" . $row['mode'] . date('Ymd_His', $outTimestamp) . ".jpg";

            $selfieInPath = "Selfies_in&out/" . $row['username'] . "/" . $selfieInFilename;
            $selfieOutPath = "Selfies_in&out/" . $row['username'] . "/" . $selfieOutFilename;

            if (file_exists($selfieInPath)) {
                $selfieIn = new Drawing();
                $selfieIn->setName('Selfie In');
                $selfieIn->setDescription('Selfie In');
                $selfieIn->setPath($selfieInPath);
                $selfieIn->setCoordinates('K' . $rowNumber);
                $selfieIn->setWidth(100);
                $selfieIn->setHeight(100);
                $selfieIn->setOffsetX(5);
                $selfieIn->setOffsetY(5);
                $selfieIn->setWorksheet($sheet);
            }

            if (file_exists($selfieOutPath)) {
                $selfieOut = new Drawing();
                $selfieOut->setName('Selfie Out');
                $selfieOut->setDescription('Selfie Out');
                $selfieOut->setPath($selfieOutPath);
                $selfieOut->setCoordinates('L' . $rowNumber);
                $selfieOut->setWidth(100);
                $selfieOut->setHeight(100);
                $selfieOut->setOffsetX(5);
                $selfieOut->setOffsetY(5);
                $selfieOut->setWorksheet($sheet);
            }

            // Handle map link
            if ($row['latitude'] && $row['longitude']) {
                $sheet->setCellValue('M' . $rowNumber, 'View on Map');
                $sheet->getCell('M' . $rowNumber)->getHyperlink()->setUrl('https://www.google.com/maps?q=' . $row['latitude'] . ',' . $row['longitude']);
            } else {
                $sheet->setCellValue('M' . $rowNumber, 'N/A');
            }

            $sheet->getRowDimension($rowNumber)->setRowHeight(110);

            $rowNumber++;
            $batchCount++;

            if ($batchCount >= $batchSize) {
                $sheet->garbageCollect();
                $batchCount = 0;
            }
        }
    }

    // Auto-size columns
    foreach (range('A', 'M') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    $writer = new Xlsx($spreadsheet);
    $fileName = 'attendance_report_' . date('Ymd_His') . '.xlsx';
    $filePath = tempnam(sys_get_temp_dir(), 'xlsx_');
    
    $writer->save($filePath);
    
    if (!file_exists($filePath) || filesize($filePath) == 0) {
        throw new Exception("Failed to create Excel file or file is empty.");
    }

    // Set headers for file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    header('Content-Length: ' . filesize($filePath));
    
    // Output file contents
    readfile($filePath);

    // Clean up
    unlink($filePath);

    exit;
} catch (Exception $e) {
    handle_error("Error generating Excel file: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    echo "An error occurred while generating the Excel file. Please try again later.";
    exit;
}