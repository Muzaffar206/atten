<?php
// Ensure PhpSpreadsheet library is included
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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

// Create new PhpSpreadsheet object
$objPHPExcel = new Spreadsheet();

// Set properties
$objPHPExcel->getProperties()->setCreator("Your Name")
                             ->setLastModifiedBy("Your Name")
                             ->setTitle("Employee Data")
                             ->setSubject("Employee Data")
                             ->setDescription("Employee Data Export")
                             ->setKeywords("employee")
                             ->setCategory("Employee Data");

// Add data headers
$objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', 'Username')
            ->setCellValue('C1', 'Employer ID')
            ->setCellValue('D1', 'Full Name')
            ->setCellValue('E1', 'Email')
            ->setCellValue('F1', 'Phone Number')
            ->setCellValue('G1', 'Address')
            ->setCellValue('H1', 'Role')
            ->setCellValue('I1', 'Department')
            ->setCellValue('J1', 'Passport Size Photo');

// Set default filter values
$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

// Fetch filtered data
$sql_export = "SELECT * FROM users WHERE deleted_at IS NULL";
if (!empty($filter_department)) {
    $sql_export .= " AND department = ?";
}
if (!empty($filter_role)) {
    $sql_export .= " AND role = ?";
}

$stmt_export = $conn->prepare($sql_export);
if (!empty($filter_department) && !empty($filter_role)) {
    $stmt_export->bind_param("ss", $filter_department, $filter_role);
} elseif (!empty($filter_department)) {
    $stmt_export->bind_param("s", $filter_department);
} elseif (!empty($filter_role)) {
    $stmt_export->bind_param("s", $filter_role);
}

$stmt_export->execute();
$result_export = $stmt_export->get_result();

// Set row counter
$rowCount = 2;

// Iterate through results
while ($row_export = $result_export->fetch_assoc()) {
    $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $rowCount, $row_export['id'])
                ->setCellValue('B' . $rowCount, $row_export['username'])
                ->setCellValue('C' . $rowCount, $row_export['employer_id'])
                ->setCellValue('D' . $rowCount, $row_export['full_name'])
                ->setCellValue('E' . $rowCount, $row_export['email'])
                ->setCellValue('F' . $rowCount, $row_export['phone_number'])
                ->setCellValue('G' . $rowCount, $row_export['address'])
                ->setCellValue('H' . $rowCount, $row_export['role'])
                ->setCellValue('I' . $rowCount, $row_export['department']);

    // Add image if available
    $imagePath = $row_export['passport_size_photo'];
    if (!empty($imagePath) && file_exists($imagePath)) {
        $drawing = new Drawing();
        $drawing->setName('Passport Size Photo');
        $drawing->setDescription('Passport Size Photo');
        $drawing->setPath($imagePath);
        $drawing->setCoordinates('J' . $rowCount);
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);
        $drawing->setWidth(100); // Adjust width as needed
        $drawing->setHeight(100); // Adjust height as needed
        $drawing->setWorksheet($objPHPExcel->getActiveSheet());

        // Adjust row height to fit the photo
        $objPHPExcel->getActiveSheet()->getRowDimension($rowCount)->setRowHeight(110); // Adjust row height
    }

    // Auto-size columns based on content length
    foreach (range('A', 'J') as $col) {
        $objPHPExcel->getActiveSheet()
                    ->getColumnDimension($col)
                    ->setAutoSize(true);
    }

    $rowCount++;
}

$stmt_export->close();

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Filtered Employee Data');

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

// Redirect output to a client’s web browser (Xlsx format)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="filtered_employee_data.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = new Xlsx($objPHPExcel);
$objWriter->save('php://output');

exit();
?>
