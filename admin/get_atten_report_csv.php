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

// Set default values for filters
$department = isset($_POST['department']) ? $_POST['department'] : 'All';
$from_date = isset($_POST['from_date']) ? $_POST['from_date'] : date('Y-m-01');
$to_date = isset($_POST['to_date']) ? $_POST['to_date'] : date('Y-m-d');

$to_date_adjusted = date('Y-m-d', strtotime($to_date . ' +1 day'));

// Prepare SQL query based on department filter
$users_query = ($department === 'All') ?
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin'
     GROUP BY u.id" :
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE u.department = ? AND fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin'
     GROUP BY u.id";

$stmt_users = $conn->prepare($users_query);
if ($department === 'All') {
    $stmt_users->bind_param("ss", $from_date, $to_date_adjusted);
} else {
    $stmt_users->bind_param("sss", $department, $from_date, $to_date_adjusted);
}
$stmt_users->execute();
$users_result = $stmt_users->get_result();

// Generate list of dates for the report
$dates = [];
$current_date = strtotime($from_date);
$end_date = strtotime($to_date);

while ($current_date <= $end_date) {
    $dates[] = date('d-m-Y', $current_date);
    $current_date = strtotime('+1 day', $current_date);
}

$stmt_users->close();

// Prepare CSV output
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="attendance_report.csv"');

// Open output stream for writing CSV data
$output = fopen('php://output', 'w');

// Set headers for the CSV
$header_row = ['Department', 'Employee Code', 'Employee Name'];
foreach ($dates as $date) {
    $header_row[] = $date;
}
fputcsv($output, $header_row);

// Add user data to CSV
while ($user = $users_result->fetch_assoc()) {
    $row = [
        $user['department'],
        $user['employer_id'],
        $user['full_name']
    ];

    // Fetch all 'data' entries for this user within the date range
    $data_query = "SELECT DATE(a.in_time) as date, a.data 
                   FROM attendance a 
                   WHERE a.user_id = ? AND a.in_time >= ? AND a.in_time < ?";
    $stmt_data = $conn->prepare($data_query);
    $stmt_data->bind_param("iss", $user['id'], $from_date, $to_date_adjusted);
    $stmt_data->execute();
    $data_result = $stmt_data->get_result();

    $user_data = [];
    while ($data_row = $data_result->fetch_assoc()) {
        $user_data[date('d-m-Y', strtotime($data_row['date']))] = $data_row['data'];
    }
    $stmt_data->close();

    foreach ($dates as $date) {
        $attendance_date = DateTime::createFromFormat('d-m-Y', $date);
        $day_of_week = $attendance_date->format('w');
        $formatted_date = $attendance_date->format('Y-m-d');

        $cell_value = "";

        $attendance_query = "SELECT fa.first_in, fa.last_out, fa.first_mode, fa.last_mode, fa.total_hours, a.is_present 
                             FROM final_attendance fa
                             LEFT JOIN attendance a ON fa.user_id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
                             WHERE fa.user_id = ? AND DATE(fa.first_in) = ?";
        $stmt_attendance = $conn->prepare($attendance_query);
        $stmt_attendance->bind_param("is", $user['id'], $formatted_date);
        $stmt_attendance->execute();
        $attendance_result = $stmt_attendance->get_result();

        if ($attendance_result->num_rows > 0) {
            $attendance_data = $attendance_result->fetch_assoc();
            $cell_value .= "" . date('H:i', strtotime($attendance_data['first_in'])) . "\n";
            if ($attendance_data['last_out'] != null) {
                $cell_value .= "" . date('H:i', strtotime($attendance_data['last_out'])) ;
            } else {
                $cell_value .= "*Out*";
            }
        } else {
            if ($day_of_week == 0) {
                $cell_value .= "Holiday";
            } else {
                $cell_value .= "Absent";
            }
        }
        $stmt_attendance->close();

        $row[] = $cell_value;
    }


    fputcsv($output, $row);
}

fclose($output);
exit();
?>