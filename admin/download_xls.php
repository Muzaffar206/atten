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

$to_date_adjusted = date('Y-m-d', strtotime($to_date . ' +1 day'));

$users_query = ($department === 'All') ?
    "SELECT u.id, u.employer_id, u.full_name, u.department, MAX(fa.first_in) AS latest_first_in, MAX(a.data) AS data
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE fa.first_in >= ? AND fa.first_in < ?
     GROUP BY u.id" :
    "SELECT u.id, u.employer_id, u.full_name, u.department, MAX(fa.first_in) AS latest_first_in, MAX(a.data) AS data
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE u.department = ? AND fa.first_in >= ? AND fa.first_in < ?
     GROUP BY u.id";

$stmt_users = $conn->prepare($users_query);
if ($department === 'All') {
    $stmt_users->bind_param("ss", $from_date, $to_date_adjusted);
} else {
    $stmt_users->bind_param("sss", $department, $from_date, $to_date_adjusted);
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

// Set headers
$sheet->setCellValue('A1', 'Department');
$sheet->setCellValue('B1', 'Employer Code');
$sheet->setCellValue('C1', 'Employer Name');

$column = 'D';
$column_count = 0;
foreach ($dates as $date) {
    $sheet->setCellValue($column . '1', $date);
    $column++;
    $column_count++;
}

// Apply border to the first row
$styleArray = [
    'borders' => [
        'outline' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($styleArray);

// Center align headers and content
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
    ->setVertical(Alignment::VERTICAL_CENTER);

// Initialize row number
$row_num = 2;

while ($user = $users_result->fetch_assoc()) {
    $column = 'D';
    $sheet->setCellValue('A' . $row_num, $user['department']);
    $sheet->setCellValue('B' . $row_num, $user['employer_id']);
    $sheet->setCellValue('C' . $row_num, $user['full_name']);

    for ($i = 0; $i < $column_count; $i++) {
        $attendance_query = "SELECT fa.first_in, fa.last_out, fa.first_mode, fa.last_mode, a.is_present, a.data 
                             FROM final_attendance fa
                             LEFT JOIN attendance a ON fa.user_id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
                             WHERE fa.user_id = ? AND DATE(fa.first_in) = ?";
        $stmt_attendance = $conn->prepare($attendance_query);
        $formatted_date = date('Y-m-d', strtotime($dates[$i]));
        $stmt_attendance->bind_param("is", $user['id'], $formatted_date);
        $stmt_attendance->execute();
        $attendance_result = $stmt_attendance->get_result();
    
        $is_sunday = (date('w', strtotime($formatted_date)) == 0);
    
        if ($is_sunday) {
            $cell_value = "Holiday";
        } elseif ($attendance_result->num_rows > 0) {
            $attendance_data = $attendance_result->fetch_assoc();
            $cell_value = $user['data'] . "\n" .
                          "Status: " . ($attendance_data['is_present'] ? "Present" : "Absent") . "\n" .
                          "First In: " . date('H:i:s', strtotime($attendance_data['first_in'])) . "\n" .
                          "First Mode: " . $attendance_data['first_mode'] . "\n";
            if ($attendance_data['last_out'] != null) {
                $cell_value .= "Last Out: " . date('H:i:s', strtotime($attendance_data['last_out'])) . "\n" .
                               "Last Mode: " . $attendance_data['last_mode'];
    
                // Calculate total hours worked
                $first_in = new DateTime($attendance_data['first_in']);
                $last_out = new DateTime($attendance_data['last_out']);
                $interval = $first_in->diff($last_out);
                $total_hours = $interval->format('%h:%i:%s');
    
                $cell_value .= "\nTotal Hours Worked: " . $total_hours;
            }
        } else {
            $cell_value = "Absent";
        }
    
        $sheet->setCellValue($column . $row_num, $cell_value);
        // Set wrap text and align left
        $sheet->getStyle($column . $row_num)
            ->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    
        $stmt_attendance->close();
        $column++;
    }
    
    $row_num++;
}

// Adjust column widths
$highestColumn = $sheet->getHighestColumn(); // This will return 'D'
$highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // Convert 'D' to 4

for ($colIndex = 1; $colIndex <= $highestColumnIndex; $colIndex++) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex); // Convert 4 to 'D'
    $sheet->getColumnDimension($colLetter)->setWidth(20);
}

// Adjust row heights to 100 px
$rowCount = $sheet->getHighestRow();
for ($row = 1; $row <= $rowCount; $row++) {
    $sheet->getRowDimension($row)->setRowHeight(135);
}

// Output the file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="attendance_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
