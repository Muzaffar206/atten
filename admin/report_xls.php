<?php
session_start();
session_regenerate_id(true);
include("../assest/connection/config.php");
require '../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

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
        JOIN users ON attendance.user_id = users.id";

$whereClause = [];

if (!empty($filterDepartment)) {
    $whereClause[] = "users.department = '$filterDepartment'";
}

if (!empty($startDate) && !empty($endDate)) {
    $whereClause[] = "DATE(attendance.in_time) BETWEEN '$startDate' AND '$endDate'";
} elseif (!empty($startDate)) {
    $whereClause[] = "DATE(attendance.in_time) = '$startDate'";
}

if (!empty($whereClause)) {
    $sql .= " WHERE " . implode(" AND ", $whereClause);
}

$sql .= " ORDER BY attendance.id DESC ";

$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'Emp ID');
$sheet->setCellValue('C1', 'Username');
$sheet->setCellValue('D1', 'Full Name');
$sheet->setCellValue('E1', 'Department');
$sheet->setCellValue('F1', 'Mode');
$sheet->setCellValue('G1', 'Latitude');
$sheet->setCellValue('H1', 'Longitude');
$sheet->setCellValue('I1', 'In Time');
$sheet->setCellValue('J1', 'Out Time');
$sheet->setCellValue('K1', 'Selfie In');
$sheet->setCellValue('L1', 'Selfie Out');
$sheet->setCellValue('M1', 'Map');

$styleArray = [
    'font' => [
        'bold' => true,
        'size' => 12,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
    ],
];

$sheet->getStyle('A1:M1')->applyFromArray($styleArray);
$sheet->getStyle('A1:M1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');

$rowNumber = 2;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue('A' . $rowNumber, $row['attendance_id']);
        $sheet->setCellValue('B' . $rowNumber, $row['employer_id']);
        $sheet->setCellValue('C' . $rowNumber, $row['username']);
        $sheet->setCellValue('D' . $rowNumber, $row['full_name']);
        $sheet->setCellValue('E' . $rowNumber, $row['department']);
        $sheet->setCellValue('F' . $rowNumber, $row['mode']);
        $sheet->setCellValue('G' . $rowNumber, $row['latitude']);
        $sheet->setCellValue('H' . $rowNumber, $row['longitude']);
        $sheet->setCellValue('I' . $rowNumber, $row['in_time']);
        $sheet->setCellValue('J' . $rowNumber, $row['out_time']);

        // Handle selfies
        if (!empty($row['selfie_in'])) {
            $imageNameIn = 'selfie_' . $row['username'] . '_' . date('Ymd_His') . '_in.jpg';
            $imagePathIn = __DIR__ . '/selfies/' . $row['username'] . '/' . $imageNameIn;
            if (!is_dir(dirname($imagePathIn))) {
                mkdir(dirname($imagePathIn), 0777, true); // Create directory if not exists
            }
            file_put_contents($imagePathIn, $row['selfie_in']);

            $selfieIn = new Drawing();
            $selfieIn->setName('Selfie In');
            $selfieIn->setDescription('Selfie In');
            $selfieIn->setPath($imagePathIn);
            $selfieIn->setCoordinates('K' . $rowNumber);
            // Adjust the image size proportionally
            [$widthIn, $heightIn, $typeIn, $attrIn] = getimagesize($imagePathIn);
            $selfieIn->setHeight(80); // Adjust height if needed
            $selfieIn->setWidth(80 * $widthIn / $heightIn); // Adjust width proportionally
            $selfieIn->setOffsetX(5); // Adjust X offset if needed
            $selfieIn->setOffsetY(5); // Adjust Y offset if needed
            $selfieIn->setWorksheet($sheet);
            $sheet->getRowDimension($rowNumber)->setRowHeight(80); // Adjust row height if needed
        }

        if (!empty($row['selfie_out'])) {
            $imageNameOut = 'selfie_' . $row['username'] . '_' . date('Ymd_His') . '_out.jpg';
            $imagePathOut = __DIR__ . '/selfies/' . $row['username'] . '/' . $imageNameOut;
            if (!is_dir(dirname($imagePathOut))) {
                mkdir(dirname($imagePathOut), 0777, true); // Create directory if not exists
            }
            file_put_contents($imagePathOut, $row['selfie_out']);

            $selfieOut = new Drawing();
            $selfieOut->setName('Selfie Out');
            $selfieOut->setDescription('Selfie Out');
            $selfieOut->setPath($imagePathOut);
            $selfieOut->setCoordinates('L' . $rowNumber);
            // Adjust the image size proportionally
            [$widthOut, $heightOut, $typeOut, $attrOut] = getimagesize($imagePathOut);
            $selfieOut->setHeight(80); // Adjust height if needed
            $selfieOut->setWidth(80 * $widthOut / $heightOut); // Adjust width proportionally
            $selfieOut->setOffsetX(5); // Adjust X offset if needed
            $selfieOut->setOffsetY(5); // Adjust Y offset if needed
            $selfieOut->setWorksheet($sheet);
            $sheet->getRowDimension($rowNumber)->setRowHeight(80); // Adjust row height if needed
        }

        // Handle map link
        if ($row['latitude'] && $row['longitude']) {
            $sheet->setCellValue('M' . $rowNumber, 'View on Map');
            $sheet->getCell('M' . $rowNumber)->getHyperlink()->setUrl('https://www.google.com/maps?q=' . $row['latitude'] . ',' . $row['longitude']);
        } else {
            $sheet->setCellValue('M' . $rowNumber, 'N/A');
        }

        $rowNumber++;
    }
}

// Auto-size columns
foreach (range('A', 'M') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

$writer = new Xlsx($spreadsheet);
$fileName = 'attendance_report_' . date('Ymd_His') . '.xlsx';
$filePath = __DIR__ . '/' . $fileName;
$writer->save($filePath);

// Clean up selfies after generating the report
$files = scandir(__DIR__ . '/selfies/');
foreach ($files as $file) {
    if (is_dir(__DIR__ . '/selfies/' . $file) && strpos($file, '.') !== 0) {
        // Check if it's a directory and not a hidden directory (e.g., . or ..)
        $selfieFiles = scandir(__DIR__ . '/selfies/' . $file);
        foreach ($selfieFiles as $selfieFile) {
            if (strpos($selfieFile, 'selfie_') === 0 && pathinfo($selfieFile, PATHINFO_EXTENSION) === 'jpg') {
                unlink(__DIR__ . '/selfies/' . $file . '/' . $selfieFile);
            }
        }
        // After deleting files, delete the directory if it's empty
        if (count(scandir(__DIR__ . '/selfies/' . $file)) <= 2) { // 2 because of . and ..
            rmdir(__DIR__ . '/selfies/' . $file);
        }
    }
}

// Download the generated XLSX file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
readfile($filePath);

// Delete the temporary XLSX file from the server
unlink($filePath);

exit;
