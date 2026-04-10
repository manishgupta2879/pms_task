<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();


$priority = $_GET['priority'] ?? '';
$employee = $_GET['employee'] ?? '';
$from_date = $_GET['from_date'] ?? date("Y-m-d");
$to_date = $_GET['to_date'] ?? date("Y-m-d");

$where = "o.deleted_at IS NULL";

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
$taskRes = $conn->query("SELECT
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
ORDER BY t.updated_at DESC
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Production Report</title>
    <style>
        body {
            font-family: Arial;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #eee;
        }

        @media print {
            body {
                margin: 20px;
            }

            table {
                font-size: 12px;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <h2>Production Report (<?= date("M d, Y", strtotime($from_date)) ?> to <?= date("M d, Y", strtotime($to_date)) ?>)</h2>

    <table>
        <tr>
            <th>Order No</th>
            <th>Need Date</th>
            <th>Task</th>
            <th>Name</th>
            <th>Qty</th>
            <th>Product</th>
            <th>Species</th>
            <th>Duration</th>
            <th>Time Taken</th>
            <th>Priority</th>
            <th>Extras</th>
        </tr>

        <tbody>
            <?php if ($taskRes->num_rows == 0) { ?>
                <tr>
                    <td colspan="11" class="text-center">No orders found</td>
                </tr>
            <?php } ?>

            <?php while ($task = $taskRes->fetch_assoc()) { ?>
                <tr>
                    <td class="text-dark fw-medium text-nowrap"><?= $task['order_no'] ?></td>
                    <td class="text-dark fw-medium text-nowrap"><?= date('M d, Y', strtotime($task['deadline'])) ?></td>
                    <td class="text-dark fw-medium text-nowrap"><?= $task['task_name'] ?></td>
                    <td class="text-dark fw-medium text-nowrap"><?= $task['user_name'] ?? '-' ?></td>
                    <td class="text-dark fw-medium text-nowrap"><?= $task['qty'] ?? '-' ?></td>
                    <td class="text-dark fw-medium text-nowrap"><?= $task['product'] ?? '-' ?></td>
                    <td class="text-dark fw-medium text-nowrap"><?= $task['species'] ?? '-' ?></td>
                    <td class="text-dark fw-medium text-nowrap"><?= $task['est_time'] ? formatMinutes($task['est_time']) : '-' ?></td>
                    <td class=" text-nowrap">
                        <?php
                        $start_time = new DateTime($task['start_time']);
                        $end_time = new DateTime($task['end_time']);

                        $start_time->setTime($start_time->format('H'), $start_time->format('i'), 0);
                        $end_time->setTime($end_time->format('H'), $end_time->format('i'), 0);

                        if ($end_time < $start_time) {
                            $end_time->modify('+1 day');
                        }

                        $actual_minutes = floor(($end_time->getTimestamp() - $start_time->getTimestamp()) / 60);
                        ?>
                        <?= $task['start_time'] && $task['end_time'] ? formatMinutes($actual_minutes) : '-' ?>
                    </td>

                    <td class=" text-nowrap">
                        <?php if ($task['priority'] == 'low' || $task['priority'] == '') { ?>
                            <span class="pms-status active">Low</span>
                        <?php } elseif ($task['priority'] == 'medium') { ?>
                            <span class="pms-status completed">Medium</span>
                        <?php } elseif ($task['priority'] == 'high') { ?>
                            <span class="pms-status pending text-dark">High</span>
                        <?php } ?>
                    </td>
                    <td class=" text-nowrap"><?= $task['extras'] ?? '-' ?></td>
                </tr>

            <?php } ?>
        </tbody>

    </table>

</body>

</html>