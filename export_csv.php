<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();

// filters
$search   = $_GET['search'] ?? '';
$priority = $_GET['priority'] ?? '';
$employee = $_GET['employee'] ?? '';
$date     = $_GET['date'] ?? date("Y-m-d");

$where = "o.deleted_at IS NULL";

if ($search != '') {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND (o.order_no LIKE '%$search_esc%')";
    // $where .= " AND (o.order_no LIKE '%$search_esc%' OR o.product LIKE '%$search_esc%')";
}

if ($employee != '') {
    $where .= " AND t.user_id = " . (int)$employee;
}

if ($priority != '') {
    $priority_esc = $conn->real_escape_string($priority);
    $where .= " AND t.priority = '$priority_esc'";
}

if ($date != '') {
    $date_esc = $conn->real_escape_string($date);
    $where .= " AND DATE(o.deadline) = '$date_esc'";
}

// query
$result = $conn->query("
    SELECT 
        t.task_name,
        o.order_no,
        u.name AS employee,
        o.deadline,
        t.priority
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN orders o ON t.order_id = o.id
    WHERE $where
    ORDER BY t.updated_at DESC
");

// headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="daily_report.csv"');

$output = fopen("php://output", "w");

// column headers
fputcsv($output, ['Task', 'Order #', 'Employee', 'Deadline', 'Priority']);

// data
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['task_name'],
        $row['order_no'],
        $row['employee'],
        date('M d, Y', strtotime($row['deadline'])),
        ucfirst($row['priority']?? 'Low')
    ]);
}

fclose($output);
exit;