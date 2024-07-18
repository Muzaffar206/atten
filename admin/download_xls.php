<?php
session_start();
session_regenerate_id(true);
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../home.php");
    exit();
}
include("../assest/connection/config.php");
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

$department = isset($_POST['department']) ? $_POST['department'] : 'All';
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-01');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

$users_query = ($department === 'All') ?
    "SELECT u.id, u.employer_id, u.full_name, u.department, MAX(a.data) AS data
FROM users u
LEFT JOIN attendance a ON u.id = a.user_id
GROUP BY u.id" :
    "SELECT u.id, u.employer_id, u.full_name, u.department, MAX(a.data) AS data
FROM users u
LEFT JOIN attendance a ON u.id = a.user_id
WHERE u.department = ?
GROUP BY u.id";


$stmt_users = $conn->prepare($users_query);
if ($department !== 'All') {
    $stmt_users->bind_param("s", $department);
}
$stmt_users->execute();
$users_result = $stmt_users->get_result();

$dates = [];
$current_date = strtotime($from_date);
$end_date = strtotime($to_date);

while ($current_date <= $end_date) {
    $dates[] = date('d-m-Y', $current_date);
    $current_date = strtotime('+1 day', $current_date);
}

$stmt_users->close();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set bold and larger font for the first row
$sheet->getStyle('1')->getFont()->setBold(true)->setSize(14);

// Apply border to the first row
$styleArray = [
    'borders' => [
        'outline' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
$sheet->getStyle('1')->applyFromArray($styleArray);

$sheet->setCellValue('A1', 'Department');
$sheet->setCellValue('B1', 'Employer Code');
$sheet->setCellValue('C1', 'Employer Name');

$column = 'D';
foreach ($dates as $date) {
    $sheet->setCellValue($column . '1', $date);
    $column++;
}

$row_num = 2;

while ($user = $users_result->fetch_assoc()) {
    $column = 'D';
    $sheet->setCellValue('A' . $row_num, $user['department']);
    $sheet->setCellValue('B' . $row_num, $user['employer_id']);
    $sheet->setCellValue('C' . $row_num, $user['full_name']);

    foreach ($dates as $date) {
        $attendance_query = "SELECT * FROM attendance WHERE user_id = ? AND DATE_FORMAT(in_time, '%d-%m-%Y') = ?";
        $stmt_attendance = $conn->prepare($attendance_query);
        $stmt_attendance->bind_param("is", $user['id'], $date);
        $stmt_attendance->execute();
        $attendance_result = $stmt_attendance->get_result();

        if ($attendance_result->num_rows > 0) {
            $attendance_data = $attendance_result->fetch_assoc();
            $cell_value =
                $user['data'] . "\n" .
                "Status: " . ($attendance_data['is_present'] ? "Present" : "Absent") . "\n" .
                "In: " . date('H:i:s', strtotime($attendance_data['in_time'])) . "\n" .
                ($attendance_data['out_time'] ? "Out: " . date('H:i:s', strtotime($attendance_data['out_time'])) : "");
            $sheet->setCellValue($column . $row_num, $cell_value);
            // Set wrap text and align left
            $sheet->getStyle($column . $row_num)->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_LEFT);
        } else {
            $sheet->setCellValue($column . $row_num, "Absent");
        }

        $stmt_attendance->close();
        $column++;
    }
    $row_num++;
}

// Adjust column widths
foreach (range('A', $sheet->getHighestColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="attendance_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
