<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();

// filters
// $search   = $_GET['search'] ?? '';
$priority = $_GET['priority'] ?? '';
$employee = $_GET['employee'] ?? '';
$from_date = $_GET['from_date'] ?? date("Y-m-d");
$to_date = $_GET['to_date'] ?? date("Y-m-d");

$where = "o.deleted_at IS NULL AND t.status = 'completed'";

// if ($search != '') {
//     $search_esc = $conn->real_escape_string($search);
//     $where .= " AND (o.order_no LIKE '%$search_esc%')";
//     // $where .= " AND (o.order_no LIKE '%$search_esc%' OR o.product LIKE '%$search_esc%')";
// }

if ($employee != '') {
    $where .= " AND t.user_id = " . (int)$employee;
}

if ($priority != '') {
    $priority_esc = $conn->real_escape_string($priority);
    if($priority_esc == 'low'){
        $where .= " AND (t.priority = '$priority_esc' OR t.priority IS NULL)";  
    }
    else{
        $where .= " AND t.priority = '$priority_esc'";
    }    
}

if ($from_date != '' && $to_date != '') {
    $from_date_esc = $conn->real_escape_string($from_date);
    $to_date_esc = $conn->real_escape_string($to_date);
    $where .= " AND DATE(t.deadline) BETWEEN '$from_date_esc' AND '$to_date_esc'";
}

// query
$result = $conn->query("
    SELECT 
        t.task_name,
        o.order_no,
        u.name AS employee,
        t.deadline,
        t.est_time,
        t.start_time,
        t.end_time,
        t.priority,
        oi.product
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN orders o ON t.order_id = o.id
    left join order_items oi on o.order_no = oi.order_id and oi.id = t.product
    WHERE $where
    ORDER BY t.updated_at DESC
");

// headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="daily_report.csv"');

$output = fopen("php://output", "w");

// column headers
fputcsv($output, [
    'Task',
    'Order No',
    'Employee',
    'Product',
    'Deadline',
    'Allocated Time',
    'Time Taken',
    'Priority'
]);

// data
while ($row = $result->fetch_assoc()) {

    // Allocated Time
    $allocated = $row['est_time'] ? formatMinutes($row['est_time']) : '-';

    // Time Taken Calculation
    if ($row['start_time'] && $row['end_time']) {
        $start = new DateTime($row['start_time']);
        $end   = new DateTime($row['end_time']);

        $start->setTime($start->format('H'), $start->format('i'), 0);
        $end->setTime($end->format('H'), $end->format('i'), 0);

        if ($end < $start) {
            $end->modify('+1 day');
        }

        $minutes = floor(($end->getTimestamp() - $start->getTimestamp()) / 60);
        $timeTaken = formatMinutes($minutes);
    } else {
        $timeTaken = '-';
    }

    fputcsv($output, [
        $row['task_name'],
        $row['order_no'],
        $row['employee'],
        $row['product'] ?? '-',
        $row['deadline'],
        // date('M d, Y', strtotime($row['deadline'])),
        $allocated,
        $timeTaken,
        ucfirst($row['priority'] ?? 'Low')
    ]);
}

fclose($output);
exit;