<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();

$limit = 10;
$page = $_GET['page'] ?? 1;
$page = max(1, (int) $page);
$offset = ($page - 1) * $limit;


$where = "o.deleted_at IS NULL AND t.status != 'completed' AND t.deadline < CURDATE()";

$countRes = $conn->query("
    SELECT COUNT(*) as total
    FROM tasks t
    LEFT JOIN orders o ON t.order_id = o.id
    WHERE $where
");

$total = $countRes->fetch_assoc()['total'];
$total_pages = max(1, ceil($total / $limit));

$taskRes = $conn->query("
    SELECT 
    t.*,
    u.id AS user_id,
    u.name AS user_name,
    ab.id AS assigned_by_id,
    ab.name AS assigned_by_name,
    t.deadline,
    o.id AS order_id,
    o.order_no,
    oi.product

FROM tasks t
INNER JOIN users u ON t.user_id = u.id
INNER JOIN users ab ON t.assigned_by = ab.id
INNER JOIN orders o ON t.order_id = o.id
INNER JOIN order_items oi on o.order_no = oi.order_id and oi.id = t.product
WHERE $where
ORDER BY 
  CASE t.priority
    WHEN 'high' THEN 3
    WHEN 'medium' THEN 2
    WHEN 'low' THEN 1
  END DESC
LIMIT $limit OFFSET $offset");


include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="pms-panel">
        <div class="pms-panel-header d-flex justify-content-between align-items-center">
            <span>OverDue Tasks</span>
        </div>

        <div style="overflow-x: auto;">
            <table class="pms-table">

                <thead class="table-dark">
                    <tr>
                        <th>Task</th>
                        <th>Order</th>
                        <th>Assigned To</th>
                        <th>Product</th>
                        <th>Deadline</th>
                        <th>Allocated Time</th>
                        <th>Priority </th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($taskRes->num_rows == 0) { ?>
                        <tr>
                            <td colspan="7" class="text-center">No tasks found</td>
                        </tr>
                    <?php } ?>

                    <?php while ($task = $taskRes->fetch_assoc()) { ?>
                        <tr>
                            <td class="text-dark fw-medium"><?= $task['task_name'] ?></td>
                            <td class="text-dark fw-medium">
                                <a href="view_order.php?id=<?= $task['order_id'] ?>" class="fw-bold text-decoration-none">
                                    <?= $task['order_no'] ?>
                                </a>
                            </td>
                            <td class="text-dark fw-medium"><?= $task['user_name'] ?? '-' ?></td>
                            <td class="text-dark fw-medium"><?= $task['product'] ?? '-' ?></td>
                            <td class="text-dark fw-medium">
                                <?= date('M d, Y', strtotime($task['deadline'])) ?>
                                <!-- date("M d, Y", strtotime($row['deadline'])) -->
                            </td>
                            <td class="text-dark fw-medium">
                                <?= $task['est_time'] ? formatMinutes($task['est_time']) : '-' ?>
                            </td>
                            
                            <td>
                                <?php if ($task['priority'] == 'low' || $task['priority'] == '') { ?>
                                    <span class="pms-status active">Low</span>
                                <?php } elseif ($task['priority'] == 'medium') { ?>
                                    <span class="pms-status completed">Medium</span>
                                <?php } elseif ($task['priority'] == 'high') { ?>
                                    <span class="pms-status pending text-dark">High</span>
                                <?php } ?>
                            </td>
                        </tr>

                    <?php } ?>
                </tbody>

            </table>
        </div>
        <div class="pms-footer">
            <?php
            $start = ($total > 0) ? $offset + 1 : 0;
            $end = min($total, $offset + $limit);
            ?>

            <div>Showing <?= $start ?> to <?= $end ?> of <?= $total ?> orders</div>

            <div class="pms-pagination">
                <a href="?page=<?= $page - 1 ?>" class="pms-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                    Previous
                </a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="pms-page-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <a href="?page=<?= $page + 1 ?>"
                    class="pms-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    Next
                </a>
            </div>
        </div>
    </div>

</div>

<?php include "includes/footer.php"; ?>