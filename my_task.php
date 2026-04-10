<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');
include "includes/header.php";

$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$order_id = $_GET['order_id'] ?? '';
$customer = $_GET['customer'] ?? '';
$deadline = $_GET['deadline'] ?? '';
$product = $_GET['product'] ?? '';

// pagination
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// base query - only user's tasks
$where = "t.user_id = $user_id";

// filters
if ($search != '') {
    $search_term = $conn->real_escape_string($search);
    $where .= " AND t.task_name LIKE '%$search_term%'";
}

if ($status_filter != '') {
    $status_esc = $conn->real_escape_string($status_filter);
    $where .= " AND t.status = '$status_esc'";
}

if ($order_id != '') {
    $order_id_esc = $conn->real_escape_string($order_id);
    $where .= " AND (o.order_no LIKE '%$order_id_esc%')";
    // $where .= " AND (o.order_no LIKE '%$order_id_esc%' OR o.id = '$order_id_esc')";
}

if ($customer != '') {
    $customer_esc = $conn->real_escape_string($customer);
    $where .= " AND o.customer LIKE '%$customer_esc%'";
}

if ($deadline != '') {
    $deadline_esc = $conn->real_escape_string($deadline);
    $where .= " AND o.deadline = '$deadline_esc'";
}

if ($product != '') {
    $product_esc = $conn->real_escape_string($product);
    $where .= " AND o.product LIKE '%$product_esc%'";
}

// total count
$count_res = $conn->query("
    SELECT COUNT(DISTINCT t.id) as cnt FROM tasks t 
    LEFT JOIN orders o ON t.order_id = o.id 
    WHERE $where
");
$total = $count_res->fetch_assoc()['cnt'];
$total_pages = max(1, (int)ceil($total / $limit));

// main query
$taskRes = $conn->query("
    SELECT 
        t.*, 
        u.username as assigned_to_user,
        ab.username as assigned_by_user,
        o.id as order_id_num,
        o.order_no,
        o.customer,
        oi.product,
        t.deadline,
        o.status as order_status
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN users ab ON t.assigned_by = ab.id
    INNER JOIN orders o ON t.order_id = o.id
    INNER JOIN order_items oi ON o.order_no = oi.order_id
    WHERE $where
    GROUP BY t.id
    ORDER BY t.updated_at DESC
    LIMIT $limit OFFSET $offset
");

// build query string
$qs = '&search=' . urlencode($search) . '&status=' . urlencode($status_filter) .
    '&order_id=' . urlencode($order_id) . '&customer=' . urlencode($customer) .
    '&deadline=' . urlencode($deadline) . '&product=' . urlencode($product);
?>

<div class="pms-wrap">
    <!-- TOP - FILTERS (Collapsible) -->
    <div class="pms-panel mb-3">
        <div class="pms-panel-header d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterPanel" aria-expanded="true">
            <span>
                <i class="bi bi-funnel me-2"></i>Filter My Tasks
            </span>
            <i class="bi bi-chevron-down"></i>
        </div>
        <div id="filterPanel" class="collapse show">
            <form method="GET">
                <div class="pms-panel-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="pms-form-label">Task Name</label>
                            <input type="text" name="search" class="form-control" placeholder="Search task..." value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="pms-form-label">Order ID</label>
                            <input type="text" name="order_id" class="form-control" placeholder="Order #" value="<?= htmlspecialchars($order_id) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="pms-form-label">Customer</label>
                            <input type="text" name="customer" class="form-control" placeholder="Customer Name" value="<?= htmlspecialchars($customer) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="pms-form-label">Product</label>
                            <input type="text" name="product" class="form-control" placeholder="Product Name" value="<?= htmlspecialchars($product) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="pms-form-label">Deadline</label>
                            <input type="date" name="deadline" class="form-control" value="<?= htmlspecialchars($deadline) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="pms-form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="not_started" <?= $status_filter == 'not_started' ? 'selected' : '' ?>>Not Started</option>
                                <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="pms-panel-footer d-flex gap-2 text-end">
                    <a href="my_task.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                    <button type="submit" class="pms-btn-dark btn-sm">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- BOTTOM - TASK TABLE (Full Width) -->
    <div class="pms-panel">
        <div class="pms-panel-header">
            <i class="bi bi-list-check me-2"></i>My Tasks
        </div>

        <?php if ($taskRes->num_rows == 0): ?>
            <div class="alert alert-info text-center py-4 m-3">
                <i class="bi bi-info-circle me-2"></i> No tasks found
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="pms-table">
                    <thead>
                        <tr>
                            <th class="w-auto">#</th>
                            <th class="w-auto">Order</th>
                            <th class="w-auto">Customer</th>
                            <th class="w-auto">Product</th>
                            <th class="w-auto">Task Name</th>
                            <th class="w-auto">Duration</th>
                            <th class="w-auto">Assigned By</th>
                            <th class="w-auto">Status</th>
                            <th class="w-auto">Start Time</th>
                            <th class="w-auto">End Time</th>
                            <th class="w-auto">On-Time/Delay</th>
                            <th class="w-auto text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $task_counter = $offset + 1;
                        while ($t = $taskRes->fetch_assoc()):
                            $is_delayed = false;
                            $delay_text = '-';
                            if ($t['status'] == 'completed' && $t['start_time'] && $t['end_time'] && $t['est_time']) {

                                // $start_timestamp = strtotime($t['start_time']);
                                // $end_timestamp = strtotime($t['end_time']);
                            
                                // $actual_seconds = $end_timestamp - $start_timestamp;
                                // $est_seconds = $t['est_time'] * 60; // assuming est_time is in minutes
                            
                                // if ($actual_seconds > $est_seconds) {
                                //     $is_delayed = true;
                            
                                //     $diff_seconds = $actual_seconds - $est_seconds;
                                //     $diff_hours = floor($diff_seconds / 3600);
                                //     $diff_minutes = floor(($diff_seconds % 3600) / 60);
                            
                                //     if ($diff_hours > 0) {
                                //         $delay_text = $diff_hours . 'h ' . $diff_minutes . 'm late';
                                //     } else {
                                //         $delay_text = $diff_minutes . 'm late';
                                //     }
                            
                                // } else {
                                //     $delay_text = '✓ On Time';
                                // }
                                $start = new DateTime($t['start_time']);
                                $end = new DateTime($t['end_time']);

                                $start->setTime($start->format('H'), $start->format('i'), 0);
                                $end->setTime($end->format('H'), $end->format('i'), 0);

                                if ($end < $start) {
                                    $end->modify('+1 day');
                                }

                                $actual_minutes = floor(($end->getTimestamp() - $start->getTimestamp()) / 60);

                                $est_minutes = (int)$t['est_time'];

                                if ($actual_minutes > $est_minutes) {
                                    $is_delayed = true;

                                    $diff_minutes_total = $actual_minutes - $est_minutes;

                                    $diff_hours = floor($diff_minutes_total / 60);
                                    $diff_minutes = $diff_minutes_total % 60;

                                    if ($diff_hours > 0) {
                                        $delay_text = $diff_hours . 'h ' . $diff_minutes . 'm late';
                                    } else {
                                        $delay_text = $diff_minutes . 'm late';
                                    }

                                } else {
                                    $delay_text = '✓ On Time';
                                }
                            }
                        ?>
                            <tr>
                                <td class="text-start"><?= $task_counter++ ?></td>
                                <td>#<?= $t['order_no'] ?></td>
                                <td><?= htmlspecialchars($t['customer']) ?></td>
                                <td><?= htmlspecialchars(substr($t['product'], 0, 20)) ?></td>
                                <td><?= htmlspecialchars(substr($t['task_name'], 0, 25)) ?></td>
                                <td>
                                    <span class="badge bg-info text-dark"><?= $t['est_time'] ? formatMinutes($t['est_time']) : '-' ?></span>
                                </td>
                                <td class="text-dark">
                                    <?= htmlspecialchars($t['assigned_by_user'] ?? 'N/A') ?>
                                </td>

                                <td>
                                    <span class="pms-status <?= str_replace('_', ' ', $t['status']) ?>" style="font-size: 11px;">
                                        <?= ucfirst(str_replace('_', ' ', $t['status'])) ?>
                                    </span>
                                </td>
                               
                                <td class="text-dark">
                                    <?= $t['start_time'] ? date('M d, H:i', strtotime($t['start_time'])) : '-' ?>
                                </td>
                                <td class="text-dark">
                                    <?= $t['end_time'] ? date('M d, H:i', strtotime($t['end_time'])) : '-' ?>
                                </td>
                                 <td>
                                    <?php if ($t['status'] == 'completed'): ?>
                                        <span class="badge <?= $is_delayed ? 'bg-danger' : 'bg-success' ?>" style="font-size: 11px;">
                                            <?= $delay_text ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($t['status'] == 'not_started'): ?>
                                        <a href="start_task.php?id=<?= $t['id'] ?>" class="pms-action-btn pms-action-btn-success" title="Start Task">
                                            <i class="bi bi-play-fill"></i> Start Task
                                        </a>
                                    <?php elseif ($t['status'] == 'in_progress'): ?>
                                        <a href="stop_task.php?id=<?= $t['id'] ?>" class="pms-action-btn pms-action-btn-danger" title="End Task">
                                            <i class="bi bi-stop-fill"></i> End Task
                                        </a>
                                    <?php else: ?>
                                        <span class="text-success fw-medium">✓ Done</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pms-footer">
                    <?php
                    $start = ($total > 0) ? $offset + 1 : 0;
                    $end = min($total, $offset + $limit);
                    ?>
                    <div>Showing <?= $start ?> to <?= $end ?> of <?= $total ?> tasks</div>

                    <div class="pms-pagination">
                        <a href="?page=<?= max(1, $page - 1) ?><?= $qs ?>" class="pms-page-btn <?= $page <= 1 ? 'disabled' : '' ?>">Previous</a>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1) echo '<a href="?page=1' . $qs . '" class="pms-page-btn">1</a>';
                        if ($start_page > 2) echo '<span class="pms-page-btn disabled">...</span>';

                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?= $i ?><?= $qs ?>" class="pms-page-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor;

                        if ($end_page < $total_pages - 1) echo '<span class="pms-page-btn disabled">...</span>';
                        if ($end_page < $total_pages) echo '<a href="?page=' . $total_pages . $qs . '" class="pms-page-btn">' . $total_pages . '</a>';
                        ?>

                        <a href="?page=<?= min($total_pages, $page + 1) ?><?= $qs ?>" class="pms-page-btn <?= $page >= $total_pages ? 'disabled' : '' ?>">Next</a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
</div>

<?php include "includes/footer.php"; ?>