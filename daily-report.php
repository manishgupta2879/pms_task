<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();

// if (isset($_GET['delete'])) {
//     $delete_id = (int)$_GET['delete'];

//     $conn->query("UPDATE orders SET deleted_at = NOW() WHERE id = $delete_id");
//     $_SESSION['success'] = "Order deleted successfully.";
//     header("Location: orders.php");
//     exit();
// }

// search & filter
$search = $_GET['search'] ?? '';
$priority = $_GET['priority'] ?? '';
$employee = $_GET['employee'] ?? '';
// $date = $_GET['date'] ?? '';
$date = $_GET['date'] ?? date("Y-m-d");

$limit = 10;
$page = $_GET['page'] ?? 1;
$page = max(1, (int)$page);
$offset = ($page - 1) * $limit;


$where = "o.deleted_at IS NULL";

if ($search != '') {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND (o.order_no LIKE '%$search_esc%' OR o.product LIKE '%$search_esc%')";
}

if ($employee != '') {
    $employee_esc = (int)$employee;
    $where .= " AND t.user_id = $employee_esc";
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
// $count_res = $conn->query("
//     SELECT 
//     t.*,
//     u.id AS user_id,
//     u.name AS user_name,
//     ab.id AS assigned_by_id,
//     ab.name AS assigned_by_name,
//     o.id AS order_id
// FROM tasks t
// LEFT JOIN users u ON t.user_id = u.id
// LEFT JOIN users ab ON t.assigned_by = ab.id
// LEFT JOIN orders o ON t.order_id = o.id
// WHERE $where
// ORDER BY t.updated_at DESC
// ");
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
    o.deadline,
    o.id AS order_id,
    o.order_no

FROM tasks t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN users ab ON t.assigned_by = ab.id
LEFT JOIN orders o ON t.order_id = o.id
WHERE $where
ORDER BY t.updated_at DESC
LIMIT $limit OFFSET $offset");

// $total = count($taskRes->fetch_all());
// $total_pages = max(1, (int)ceil($total / $limit));

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
            <div class="alert alert-danger text-center">Order deleted successfully</div>
        <?php } ?>
        <div class="col-lg-4 col-md-5">
            <div class="pms-panel mb-4">
                <div class="pms-panel-header">
                    Filter Orders
                </div>
                <form method="GET">
                    <div class="pms-panel-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="pms-form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Order" value="<?= $search ?>">
                            </div>
                            <!-- Deadline -->
                            <div class="col-6">
                                <label class="pms-form-label">Date</label>
                                <input type="date" name="date" class="form-control" value="<?= $date ?>">
                            </div>
                            <div class="col-6">
                                
                                <label class="pms-form-label">Employee</label>
                                <select name="employee" class="form-select">
                                    <option value="">All Employee</option>
                                    <?php
                                        $users = $conn->query("SELECT id, name FROM users");
                                        while ($u = $users->fetch_assoc()) {
                                        ?>
                                        <option value="<?= $u['id'] ?>" <?= $employee == $u['id'] ? 'selected' : '' ?>>
                                            <?= $u['name'] ?>
                                        </option>
                                        <?php } ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="pms-form-label">Priority</label>
                                <select name="priority" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="low" <?= $priority == 'low' ? 'selected' : '' ?>>Low</option>
                                    <option value="medium" <?= $priority == 'medium' ? 'selected' : '' ?>>Medium</option>
                                    <option value="high" <?= $priority == 'high' ? 'selected' : '' ?>>High</option>
                                </select>
                            </div>

                        </div>
                    </div>
                    <div class="pms-panel-footer d-flex gap-2">
                        <a href="daily-report.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                        <button type="submit" name="apply_filter" class="pms-btn-dark btn-sm">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-8 col-md-7">
            <div class="pms-panel">
                <div class="pms-panel-header d-flex justify-content-between align-items-center">
                    <span>Daily Production Report – <?= date('M d, Y', strtotime($date)) ?> </span>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="export_csv.php?<?= http_build_query($_GET) ?>">
                                    <i class="bi bi-file-earmark-spreadsheet me-2"></i> Export CSV
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="export_pdf.php?<?= http_build_query($_GET) ?>">
                                    <i class="bi bi-file-earmark-pdf me-2"></i> Export PDF
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                <table class="pms-table">

                    <thead class="table-dark">
                        <tr>
                            <th>Task</th>
                            <th>Order #</th>
                            <th>Assigned To</th>
                            <th>Time</th>
                            <th>Priority </th>
                            <!-- <th class="text-end" style="width: 80px;">Actions</th> -->
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($taskRes->num_rows == 0) { ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found</td>
                            </tr>
                        <?php } ?>

                        <?php while ($task = $taskRes->fetch_assoc()) { ?>
                            <tr>
                                <td class="text-dark fw-medium"><?= $task['task_name'] ?></td>
                                <td class="text-dark fw-medium"><?= $task['order_no'] ?></td>
                                <td class="text-dark fw-medium"><?= $task['user_name'] ?? '-' ?></td>
                                <td class="text-dark fw-medium">
                                    <?= date('M d, Y', strtotime($task['deadline'])) ?>
                                    <!-- date("M d, Y", strtotime($row['deadline'])) -->
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

                        <?php  } ?>
                    </tbody>

                </table>
                </div>
                <div class="pms-footer">
                    <?php
                    $start = ($total > 0) ? $offset + 1 : 0;
                    $end   = min($total, $offset + $limit);
                    $qs = "&search=" . urlencode($search) .
                    "&employee=" . urlencode($employee) .
                    "&priority=" . urlencode($priority).
                    "&date=" . urlencode($date)
                    ;
                    ?>

                    <div>Showing <?= $start ?> to <?= $end ?> of <?= $total ?> orders</div>

                    <div class="pms-pagination">
                        <a href="?page=<?= $page - 1 . $qs ?>"
                            class="pms-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">
                            Previous
                        </a>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i . $qs ?>"
                                class="pms-page-btn <?= $i == $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <a href="?page=<?= $page + 1 . $qs ?>"
                            class="pms-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            Next
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include "includes/footer.php"; ?>