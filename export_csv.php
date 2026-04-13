<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();

$priority = $_GET['priority'] ?? '';
$employee = $_GET['employee'] ?? '';
$from_date = $_GET['from_date'] ?? date("Y-m-d");
$to_date = $_GET['to_date'] ?? date("Y-m-d");
$product = $_GET['product'] ?? '';
$species = $_GET['species'] ?? '';
$sort  = $_GET['sort'] ?? 'deadline';
$order = $_GET['order'] ?? 'DESC';

$allowed_sorts = [
    'order_no'   => 'o.order_no',
    'deadline'   => 't.deadline',
    'task_name'  => 't.task_name',
    'user_name'  => 'u.name',
    'qty'        => 'oi.qty',
    'product'    => 'oi.product',
    'species'    => 'oi.species',
    'priority'   => 't.priority',
    'est_time'   => 't.est_time',
];

if (!array_key_exists($sort, $allowed_sorts)) {
    $sort = 'deadline';
}

$sort_column = $allowed_sorts[$sort];

if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
    $order = 'DESC';
}
$where = "o.deleted_at IS NULL";
if ($product != '') {
    $product_esc = $conn->real_escape_string($product);
    $where .= " AND oi.product LIKE '%$product_esc%'";
}

if ($species != '') {
    $species_esc = $conn->real_escape_string($species);
    $where .= " AND oi.species LIKE '%$species_esc%'";
}
if ($employee != '') {
    $where .= " AND t.user_id = " . (int)$employee;
}

if ($priority != '') {
    $priority_esc = $conn->real_escape_string($priority);
    if ($priority_esc == 'low') {
        $where .= " AND (t.priority = '$priority_esc' OR t.priority IS NULL)";
    } else {
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
t.*,
u.id AS user_id,
u.name AS user_name,
ab.id AS assigned_by_id,
ab.name AS assigned_by_name,
t.deadline,
o.id AS order_id,
o.order_no,
oi.product,
oi.qty,
oi.species,
o.notes as extras

FROM tasks t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN users ab ON t.assigned_by = ab.id
LEFT JOIN orders o ON t.order_id = o.id
left join order_items oi on o.order_no = oi.order_id and oi.id = t.product
WHERE $where
ORDER BY $sort_column $order
");

// headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="daily_report.csv"');

$output = fopen("php://output", "w");

// column headers
fputcsv($output, [
    'Order No',
    'Need Date',
    'Task',
    'Name',
    'Qty',
    'Product',
    'Species',
    'Duration',
    'Time Taken',
    'Priority',
    'Extras'
]);

// data
while ($row = $result->fetch_assoc()) {

    $start_time = new DateTime($row['start_time']);
    $end_time = new DateTime($row['end_time']);

    $start_time->setTime($start_time->format('H'), $start_time->format('i'), 0);
    $end_time->setTime($end_time->format('H'), $end_time->format('i'), 0);

    if ($end_time < $start_time) {
        $end_time->modify('+1 day');
    }

    $actual_minutes = floor(($end_time->getTimestamp() - $start_time->getTimestamp()) / 60);
    $priority = '';
    if ($row['priority'] == 'low' || $row['priority'] == '') {
        $priority = 'Low';
    } elseif ($row['priority'] == 'medium') {
        $priority = 'Medium';
    } elseif ($row['priority'] == 'high') {
        $priority = 'High';
    }

    fputcsv($output, [
        $row['order_no'],
        date('M d, Y', strtotime($row['deadline'])),
        $row['task_name'],

        $row['user_name'] ?? '-',
        $row['qty'] ?? '-',
        $row['product'] ?? '-',
        $row['species'] ?? '-',
        $row['est_time'] ? formatMinutes($row['est_time']) : '-',
        $row['start_time'] && $row['end_time'] ? formatMinutes($actual_minutes) : '-',
        $priority,
        $row['extras'] ?? '-'
    ]);
}

fclose($output);
exit;
