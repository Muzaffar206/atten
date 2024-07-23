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

        // Handle selfies from file directory
        $selfieInPath = '../uploads/selfies/' . $row['username'] . '/selfie_in.jpg';
        $selfieOutPath = '../uploads/selfies/' . $row['username'] . '/selfie_out.jpg';

        if (file_exists($selfieInPath)) {
            $selfieIn = new Drawing();
            $selfieIn->setName('Selfie In');
            $selfieIn->setDescription('Selfie In');
            $selfieIn->setPath($selfieInPath);
            $selfieIn->setCoordinates('K' . $rowNumber);
            $selfieIn->setHeight(80);
            $selfieIn->setWorksheet($sheet);
            $sheet->getRowDimension($rowNumber)->setRowHeight(80);
        }

        if (file_exists($selfieOutPath)) {
            $selfieOut = new Drawing();
            $selfieOut->setName('Selfie Out');
            $selfieOut->setDescription('Selfie Out');
            $selfieOut->setPath($selfieOutPath);
            $selfieOut->setCoordinates('L' . $rowNumber);
            $selfieOut->setHeight(80);
            $selfieOut->setWorksheet($sheet);
            $sheet->getRowDimension($rowNumber)->setRowHeight(80);
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

// Download the generated XLSX file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');
readfile($filePath);

// Delete the temporary XLSX file from the server
unlink($filePath);

exit;
?>
