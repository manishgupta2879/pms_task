<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');


// ✅ DELETE ORDER
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    // delete tasks first (important)
    // $conn->query("DELETE FROM tasks WHERE order_id=$delete_id");

    // delete order
    // $conn->query("DELETE FROM orders WHERE id=$delete_id");
    $conn->query("UPDATE orders SET deleted_at = NOW() WHERE id = $delete_id");
    header("Location: orders.php?msg=deleted");
    exit();
}

// search & filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$customer = $_GET['customer'] ?? '';

// pagination
$limit = 5;
$page = $_GET['page'] ?? 1;
$page = max(1, (int)$page);
$offset = ($page - 1) * $limit;

// base query
$where = "WHERE deleted_at IS NULL";

if ($search != '') {
    $where .= " AND (order_no LIKE '%$search%' OR product LIKE '%$search%')";
}

if ($status != '') {
    $where .= " AND status='$status'";
}

if ($customer != '') {
    $where .= " AND customer LIKE '%$customer%'";
}

// total count
$totalRes = $conn->query("SELECT COUNT(*) as total FROM orders $where");
$total = $totalRes->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// main query
// $query = "SELECT * FROM orders $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$query = "
    SELECT 
        orders.*, 
        COUNT(tasks.id) AS task_count
    FROM orders
    LEFT JOIN tasks 
        ON tasks.order_id = orders.id
    $where
    GROUP BY orders.id
    ORDER BY orders.id DESC
    LIMIT $limit OFFSET $offset
";

$result = $conn->query($query);
// die(var_dump($result->fetch_all(MYSQLI_ASSOC)));
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
                                <input type="text" name="search" class="form-control" placeholder="Order # or Product" value="<?= $search ?>">
                            </div>

                            <div class="col-6">
                                <label class="pms-form-label">Customer</label>
                                <input type="text" name="customer" class="form-control" placeholder="Customer Name" value="<?= $customer ?>">
                            </div>

                            <div class="col-6">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                </select>
                            </div>

                            <!-- <button class="btn btn-primary w-100">Apply Filters</button>
                    <a href="orders.php" class="btn btn-secondary w-100 mt-2">Reset Filters</a> -->
                        </div>
                    </div>
                    <div class="pms-panel-footer d-flex gap-2">
                        <a href="orders.php" class="btn btn-outline-secondary btn-sm">Reset</a>
                        <button type="submit" name="save_leave" class="pms-btn-dark btn-sm">
                            <i class="bi bi-funnel"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-8 col-md-7">
            <div class="pms-panel">
                <div class="pms-panel-header d-flex justify-content-between align-items-center">
                    <span>Orders List</span>
                    <a href="add_order.php" class="btn btn-outline-secondary btn-sm">+ Create New Order</a>
                </div>

                <div style="overflow-x: auto;">
                <table class="pms-table">

                    <thead class="table-dark">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Deadline</th>
                            <th>Status</th>
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
                            <tr>
                                <td class="text-dark fw-medium"><?= $row['order_no'] ?></td>
                                <td class="text-dark fw-medium"><?= $row['customer'] ?></td>
                                <td class="text-dark fw-medium"><?= $row['product'] ?></td>
                                <td class="text-dark fw-medium"><?= date("M d, Y", strtotime($row['deadline'])) ?></td>

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
                                                <i class="bi bi-trash"></i>
                                            </span>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>

                </table>
                </div>
                <div class=\"pms-footer\">
                    <?php
                    $start = ($total > 0) ? $offset + 1 : 0;
                    $end   = min($total, $offset + $limit);

                    // build query string (preserve filters)
                    $qs = "&search=" . urlencode($search) .
                        "&customer=" . urlencode($customer) .
                        "&status=" . urlencode($status);
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