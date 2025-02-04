<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    exit(json_encode(['error' => 'Unauthorized']));
}

include("../assest/connection/config.php");

$rowsPerLoad = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $rowsPerLoad;

$filter_department = isset($_GET['department']) ? $_GET['department'] : '';
$filter_role = isset($_GET['role']) ? $_GET['role'] : '';

// Get total count
$totalCountQuery = "SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL";
$totalResult = $conn->query($totalCountQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRecords = $totalRow['total'];

// Prepare the query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
$types = '';

if (!empty($filter_department)) {
    $sql .= " AND department = ?";
    $params[] = $filter_department;
    $types .= 's';
}
if (!empty($filter_role)) {
    if ($filter_role === 'deleted') {
        $sql .= " AND deleted_at IS NOT NULL";
    } else {
        $sql .= " AND role = ? AND deleted_at IS NULL";
        $params[] = $filter_role;
        $types .= 's';
    }
} else {
    $sql .= " AND deleted_at IS NULL";
}

$sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
$params[] = $rowsPerLoad;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$html = '';
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>';
    if ($filter_role === 'deleted') {
        $html .= '<td>' . htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . date('Y-m-d H:i:s', strtotime($row['deleted_at'])) . '</td>';
        $html .= '<td>';
        $html .= '<a class="btn btn-success" href="recover_user.php?id=' . urlencode($row['id']) . '" onclick="return confirm(\'Are you sure you want to recover this user?\');">Recover</a>';
        $html .= '</td>';
    } else {
        $html .= '<td>' . htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['employer_id'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['phone_number'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>' . htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td>';
        $html .= '<a class="btn btn-primary" href="edit_user.php?id=' . urlencode($row['id']) . '">Edit</a> ';
        $html .= '<a class="btn btn-danger" href="delete_user.php?id=' . urlencode($row['id']) . '" onclick="return confirm(\'Are you sure you want to delete this user?\');">Delete</a>';
        $html .= '</td>';
    }
    $html .= '</tr>';
}

$currentCount = min($page * $rowsPerLoad, $totalRecords);
$hasMore = $currentCount < $totalRecords;

echo json_encode([
    'html' => $html,
    'currentCount' => $currentCount,
    'hasMore' => $hasMore
]);

$conn->close();