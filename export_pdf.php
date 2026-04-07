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
    $where .= " AND DATE(o.deadline) BETWEEN '$from_date_esc' AND '$to_date_esc'";
}

$result = $conn->query("
    SELECT 
        t.task_name,
        o.order_no,
        u.name AS employee,
        o.deadline,
        t.est_time,
        t.start_time,
        t.end_time,
        t.priority
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN orders o ON t.order_id = o.id
    WHERE $where
    ORDER BY t.updated_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Production Report</title>
    <style>
        body { font-family: Arial; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background: #eee; }
        @media print {
        body { margin: 20px; }
        table { font-size: 12px; }
    }
    </style>
</head>
<body onload="window.print()">

<h2>Production Report (<?= date("M d, Y", strtotime($from_date)) ?> to <?= date("M d, Y", strtotime($to_date)) ?>)</h2>

<table>
    <tr>
        <th>Task</th>
        <th>Order #</th>
        <th>Employee</th>
        <th>Deadline</th>
        <th>Allocated Time</th>
        <th>Time Taken</th>
        <th>Priority</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { 

    // Allocated Time
    $allocated = $row['est_time'] ? formatMinutes($row['est_time']) : '-';

    // Time Taken
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

?>
<tr>
    <td><?= $row['task_name'] ?></td>
    <td><?= $row['order_no'] ?></td>
    <td><?= $row['employee'] ?></td>
    <td><?= date('M d, Y', strtotime($row['deadline'])) ?></td>
    <td><?= $allocated ?></td>
    <td><?= $timeTaken ?></td>
    <td><?= ucfirst($row['priority'] ?? 'Low') ?></td>
</tr>
<?php } ?>

</table>

</body>
</html>