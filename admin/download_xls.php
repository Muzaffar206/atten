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
$search = isset($_POST['search']) ? $conn->real_escape_string($_POST['search']) : '';

$to_date_adjusted = date('Y-m-d', strtotime($to_date . ' +1 day'));

$search_condition = '';
if (!empty($search)) {
    $search_condition = " AND (u.full_name LIKE '%$search%' OR u.employer_id LIKE '%$search%' OR u.department LIKE '%$search%')";
}

// Fetch all holidays
$holidays_query = "SELECT holiday_date, holiday_name FROM holidays";
$holidays_result = $conn->query($holidays_query);
$holidays = [];
while ($holiday = $holidays_result->fetch_assoc()) {
    $holidays[date('Y-m-d', strtotime($holiday['holiday_date']))] = $holiday['holiday_name'];
}

// Prepare SQL query based on department filter
$users_query = ($department === 'All') ?
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin' $search_condition
     GROUP BY u.id" :
    "SELECT u.id, u.employer_id, u.full_name, u.department, 
            MAX(fa.first_in) AS latest_first_in, 
            MAX(a.data) AS data,
            MAX(fa.total_hours) AS total_hours
     FROM users u
     LEFT JOIN final_attendance fa ON u.id = fa.user_id
     LEFT JOIN attendance a ON u.id = a.user_id AND DATE(fa.first_in) = DATE(a.in_time)
     WHERE u.department = ? AND fa.first_in >= ? AND fa.first_in < ? AND u.role <> 'admin' $search_condition
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
$header_row[] = 'Attendance Summary';
fputcsv($output, $header_row);

// Add user data to CSV
while ($user = $users_result->fetch_assoc()) {
    $row = [
        $user['department'],
        $user['employer_id'],
        $user['full_name']
    ];

    $total_absents = 0;
    $total_half_days = 0;
    $total_full_days = 0;
    $total_holidays = 0;

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

        $is_holiday = ($day_of_week == 0) || isset($holidays[$formatted_date]);
        $holiday_name = $day_of_week == 0 ? 'Sunday' : ($holidays[$formatted_date] ?? '');

        // Display the 'data' for this date if it exists
        if (isset($user_data[$date])) {
            $cell_value .= $user_data[$date] . "\n";
        }

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
            $cell_value .= "In: " . date('H:i:s', strtotime($attendance_data['first_in'])) . "," . $attendance_data['first_mode'] . "\n";
            if ($attendance_data['last_out'] != null) {
                $cell_value .= "Out: " . date('H:i:s', strtotime($attendance_data['last_out'])) . "," . $attendance_data['last_mode'] . "\n";
                
                // Calculate attendance status based on total hours
                if ($attendance_data['total_hours'] >= 6.5) {
                    $total_full_days += 1;
                    $cell_value .= "Full Day: ";
                } elseif ($attendance_data['total_hours'] < 5 && $attendance_data['total_hours'] > 0) {
                    $total_half_days += 0.5;
                    $cell_value .= "Half Day: ";
                } else {
                    $total_absents += 1;
                    $cell_value .= "Absent: ";
                }
                $cell_value .= "Total hours: " . $attendance_data['total_hours'] . "\n";

                if ($is_holiday) {
                    $cell_value .= "Worked on Holiday: $holiday_name\n";
                }
            } else {
                $cell_value .= "No Last Out data\n";
                $total_absents += 1;
            }
        } else {
            if ($is_holiday) {
                $cell_value .= "Holiday: $holiday_name\n";
                $total_holidays += 1;
            } else {
                $cell_value .= "Absent\n";
                $total_absents += 1;
            }
        }
        $stmt_attendance->close();

        $row[] = $cell_value;
    }

    // Add Attendance Summary
    $summary_value = "Absents: " . $total_absents . "\n" .
                     "Half Days: " . $total_half_days . "\n" .
                     "Total Days Present: " . ($total_full_days + $total_holidays) . "\n" .
                     "Total Days Absent: " . ($total_absents + $total_half_days) . "\n" .
                     "Total Holidays: " . $total_holidays;
    $row[] = $summary_value;

    fputcsv($output, $row);
}

fclose($output);
exit();
?>