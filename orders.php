<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');


// ✅ DELETE ORDER
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // Check if order has any tasks
    $check_tasks = $conn->query("SELECT COUNT(*) as task_count FROM tasks WHERE order_id=$delete_id");
    $task_result = $check_tasks->fetch_assoc();

    if ($task_result['task_count'] > 0) {
        $_SESSION['error'] = "Cannot delete this order. It has " . $task_result['task_count'] . " task(s) assigned to it. Please delete all tasks first.";
    } else {
        // delete order
        $conn->query("UPDATE orders SET deleted_at = NOW() WHERE id = $delete_id");
        $_SESSION['success'] = "Order deleted successfully.";
    }

    header("Location: orders.php");
    exit();
}

// search & filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$customer = $_GET['customer'] ?? '';
$deadline = $_GET['deadline'] ?? '';
$due = $_GET['due'] ?? '';
$sort = $_GET['sort'] ?? 'order_no';
$order = $_GET['order'] ?? 'DESC';

// Validate sort column to prevent SQL injection
$allowed_sorts = ['order_no', 'customer', 'status', 'id'];
if (!in_array($sort, $allowed_sorts)) {
    $sort = 'order_no';
}

// Validate order direction
if (!in_array(strtoupper($order), ['ASC', 'DESC'])) {
    $order = 'DESC';
}

// pagination
$limit = $_SESSION['pagination_limit'] ?? 20;
$page = $_GET['page'] ?? 1;
$page = max(1, (int)$page);
$offset = ($page - 1) * $limit;

// base query
$where = "WHERE deleted_at IS NULL";

if ($search != '') {
    $where .= " AND (order_no LIKE '%$search%')";
    // $where .= " AND (order_no LIKE '%$search%' OR product LIKE '%$search%')";
}
if ($status != '') {
    $where .= " AND orders.status='$status'";
}


if ($customer != '') {
    $where .= " AND customer LIKE '%$customer%'";
}
if ($due == 'this_week') {
    $where .= " AND orders.status != 'completed' AND orders.deleted_at IS NULL AND YEARWEEK(tasks.deadline, 1) = YEARWEEK(CURDATE(), 1) ";
}
// total count
$totalRes = $conn->query("SELECT COUNT( distinct orders.id) as total FROM orders LEFT JOIN tasks ON tasks.order_id = orders.id $where");
$total = $totalRes->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// main query
// $query = "SELECT * FROM orders $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$query = "
    SELECT 
        orders.*, 
        COUNT(tasks.id) AS task_count
    FROM orders
    LEFT JOIN tasks ON tasks.order_id = orders.id
    $where
    GROUP BY orders.id
    ORDER BY orders.$sort $order
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
            <div class="alert alert-danger text-center">Order deleted successfully</div>
        <?php } ?>
        <div class="col-lg-12 col-md-12 filter-panel" style="display: none;">
            <div class="pms-panel mb-2">
                <div class="pms-panel-header">
                    <h5 class="mb-0 fw-bold" style="color: #1e293b;">Filter</h5>
                    
                </div>
                <form method="GET">
                    <div class="pms-panel-body">
                        <div class="row g-3">
                            <div class="col-3">
                                <label class="pms-form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Order No" value="<?= $search ?>">
                            </div>
                           
                            <div class="col-3">
                                <label class="pms-form-label">Customer</label>
                                <input type="text" name="customer" class="form-control" placeholder="Customer Name" value="<?= $customer ?>">
                            </div>

                            <div class="col-3">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>

                        </div>
                    </div>
                    <div class="pms-panel-footer d-flex gap-2 justify-content-end">
                        <a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                        <button type="submit" name="save_leave" class="pms-btn-dark btn-sm">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-12 col-md-12">
            <div class="pms-panel">
                <div class="pms-panel-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold" style="color: #1e293b;">Orders List</h5>
                    <div>
                        <button  class="btn btn-outline-secondary btn-sm filter-toggle">
                            <i class="bi bi-funnel"></i> Filters
                        </button>
                        <a href="add_order.php" class="btn btn-outline-secondary btn-sm">+ Add Order</a>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="pms-table">

                        <thead class="table-dark">
                            <tr>
                                <th><?= sortLink('order_no', 'Order No', $sort, $order) ?></th>
                                <th><?= sortLink('customer', 'Customer', $sort, $order) ?></th>
                                <th><?= sortLink('status', 'Status', $sort, $order) ?></th>
                                <th class="text-end" style="width: 80px;">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($result->num_rows == 0) { ?>
                                <tr>
                                    <td colspan="6" class="text-center">No orders found</td>
                                </tr>
                            <?php } ?>

                            <?php while ($row = $result->fetch_assoc()) { ?>
                                <tr style="background-color: <?= $row['status'] == 'completed' ? '#e0f2fe' : ($row['status'] == 'pending' ? '#fef9c3' : '#d1e7dd') ?>;">
                                    <td class="text-dark fw-medium"><?= $row['order_no'] ?></td>
                                    <td class="text-dark fw-medium"><?= $row['customer'] ?? '-' ?></td>
                                    

                                    <td>
                                        <?php if ($row['status'] == 'active') { ?>
                                            <span class="pms-status active">Active</span>
                                        <?php } elseif ($row['status'] == 'completed') { ?>
                                            <span class="pms-status completed">Completed</span>
                                        <?php } else { ?>
                                            <span class="pms-status pending text-dark">Pending</span>
                                        <?php } ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex gap-1">
                                            <a href="view_order.php?id=<?= $row['id'] ?>" class="pms-action-btn me-1"><i class="bi bi-eye"></i></a>
                                            <a href="edit_order.php?id=<?= $row['id'] ?>" class="pms-action-btn me-1"><i class="bi bi-pencil"></i></a>

                                            <?php if ($row['task_count'] == 0) { ?>
                                                <a href="orders.php?delete=<?= $row['id'] ?>" class="pms-action-btn pms-action-btn-danger" onclick="return confirm('Delete this order?')"><i class="bi bi-trash"></i></a>
                                            <?php } else { ?>

                                                <span class="pms-action-btn me-1" disabled
                                                    title="Order has tasks, delete them first"
                                                    data-bs-toggle="tooltip" data-bs-placement="top">
                                                    <i class="bi bi-lock-fill"></i>
                                                </span>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>

                    </table>
                </div>
                <div class="pms-footer">
                    <?php
                    $start = ($total > 0) ? $offset + 1 : 0;
                    $end   = min($total, $offset + $limit);

                    // build query string (preserve filters and sort)
                    $qs = "&search=" . urlencode($search) .
                        "&customer=" . urlencode($customer) .
                        "&status=" . urlencode($status) .
                        "&due=" . urlencode($due) .
                        "&sort=" . urlencode($sort) .
                        "&order=" . urlencode($order);
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
<script>
    // filter-panel
    document.querySelector('.filter-toggle').addEventListener('click', function() {
        const panel = document.querySelector('.filter-panel');
        if (panel.style.display === 'none' || panel.style.display === '') {
            panel.style.display = 'block';
        } else {
            panel.style.display = 'none';
        }
    });
</script>