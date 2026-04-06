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
    if($priority_esc == 'low'){
        $where .= " AND (t.priority = '$priority_esc' OR t.priority IS NULL)";  
    }
    else{
        $where .= " AND t.priority = '$priority_esc'";
    }
}

if ($date != '') {
    $date_esc = $conn->real_escape_string($date);
    $where .= " AND DATE(o.deadline) = '$date_esc'";
}

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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daily Report</title>
    <style>
        body { font-family: Arial; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body onload="window.print()">

<h2>Daily Production Report - <?= date("M d, Y", strtotime($date)) ?></h2>

<table>
    <tr>
        <th>Task</th>
        <th>Order #</th>
        <th>Employee</th>
        <th>Deadline</th>
        <th>Priority</th>
    </tr>

    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?= $row['task_name'] ?></td>
            <td><?= $row['order_no'] ?></td>
            <td><?= $row['employee'] ?></td>
            <td><?= date('M d, Y', strtotime($row['deadline'])) ?></td>
            <td><?= ucfirst($row['priority']?? 'Low') ?></td>
        </tr>
    <?php } ?>

</table>

</body>
</html>