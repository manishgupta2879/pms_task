<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();

// Filters
$resource_filter = $_GET['resource'] ?? '';
$week = $_GET['week'] ?? date('o-\WW');

$where = "o.deleted_at IS NULL";

// Week filter
if (!empty($week) && strpos($week, '-W') !== false) {
    list($year, $week_num) = explode('-W', $week);

    $dto = new DateTime();
    $dto->setISODate((int) $year, (int) $week_num);

    $start_date = $dto->format('Y-m-d');

    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');

    $where .= " AND DATE(t.deadline) BETWEEN '$start_date' AND '$end_date'";
}

// Resource filter
if ($resource_filter != '') {
    $resource_esc = (int) $resource_filter;
    $where .= " AND t.user_id = $resource_esc";
}

// Query
$query = "
SELECT 
    t.task_name,
    o.order_no,
    u.name AS employee,
    oi.product,
    oi.species,
    oi.qty,
    o.notes,
    t.deadline,
    t.est_time,
    t.start_time,
    t.end_time,
    t.priority,
    t.status
FROM tasks t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN orders o ON t.order_id = o.id
LEFT JOIN order_items oi ON o.order_no = oi.order_id AND oi.id = t.product
WHERE $where
ORDER BY u.name, t.deadline ASC
";

$result = $conn->query($query);

// Headers for Excel download
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=weekly_report.csv");

$output = fopen("php://output", "w");

// Column headers
fputcsv($output, [
    "Order No.",
    "Need date", //Deadline
    "Current Task", //Task
    "Name", //Employee
    "Qty.",
    "Product",
    "Species",
    "Duration",
    "Time Taken",
    "Priority",
    "Status",
    "Extras",
]);

// Helper: convert minutes to readable format
// function formatMinutes($minutes) {
//     if (!$minutes) return '';
//     $h = floor($minutes / 60);
//     $m = $minutes % 60;
//     return ($h ? $h . "h " : "") . ($m ? $m . "m" : "");
// }

// Loop data
while ($row = $result->fetch_assoc()) {

    // Calculate Time Taken
    $timeTaken = '';

    if ($row['start_time'] && $row['end_time']) {
        $start = new DateTime($row['start_time']);
        $end = new DateTime($row['end_time']);

        if ($end < $start) {
            $end->modify('+1 day');
        }

        $minutes = floor(($end->getTimestamp() - $start->getTimestamp()) / 60);
        $timeTaken = formatMinutes($minutes);
    }

    fputcsv($output, [
        $row['order_no'],
        date('M d, Y', strtotime($row['deadline'])),
        $row['task_name'],
        $row['employee'],
        $row['qty'] ?? 1,
        $row['product'],
        $row['species'] ?? '',
        formatMinutes($row['est_time']),
        $timeTaken,
        $row['priority'] ?? 'low',
        ucfirst($row['status'] ? ($row['status'] == 'completed' ? 'Completed' : ($row['status'] == 'in_progress' ? 'In Progress' : 'Not Started')) : 'Not Started'),
        $row['notes'] ?? ''
    ]);
}

fclose($output);
exit;
?>