<?php
include "includes/config.php";


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
$limit = 10;
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

<div class="main-content-container">
    <div class="row my-4 mx-1">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') { ?>
            <div class="alert alert-danger text-center">Order deleted successfully</div>
        <?php } ?>
        <div class="col-3 ps-0">
            <div class="fillter-container card shadow">
                <h5>Filter Orders</h5>
                <form method="GET">
                    <div class="mb-3">
                        <label>Search</label>
                        <input type="text" name="search" class="form-control" placeholder="Order # or Product" value="<?= $search ?>">
                    </div>

                    <div class="mb-3">
                        <label>Customer</label>
                        <input type="text" name="customer" class="form-control" placeholder="Customer Name" value="<?= $customer ?>">
                    </div>

                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <a href="orders.php" class="btn btn-secondary w-100">Reset</a>
                        </div>
                        <div class="col-12 col-md-6">
                            <button class="btn btn-primary m-0 w-100">Apply Filters</button>
                        </div>
                    </div>
                    <!-- <button class="btn btn-primary w-100">Apply Filters</button>
                    <a href="orders.php" class="btn btn-secondary w-100 mt-2">Reset Filters</a> -->
                </form>
            </div>
        </div>
        <div class="col-9 card pb-0 shadow">
            <div class="table-container">
                <div class="table-header d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="bi bi-bag-check-fill text-success"></i> Orders List</h4>
                    <a href="add_order.php" class="btn btn-success">+ Create New Order</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle text-nowrap">

                        <thead class="table-dark">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th style="min-width:180px;">Actions</th>
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
                                    <td><?= $row['order_no'] ?></td>
                                    <td><?= $row['customer'] ?></td>
                                    <td><?= $row['product'] ?></td>
                                    <td><?= date("d-M-Y", strtotime($row['deadline'])) ?></td>

                                    <td>
                                        <?php if ($row['status'] == 'active') { ?>
                                            <span class="badge bg-primary">Active</span>
                                        <?php } elseif ($row['status'] == 'completed') { ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php } else { ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="view_order.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                            <a href="edit_order.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i></a>

                                            <?php if ($row['task_count'] == 0) { ?>
                                                <a href="orders.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this order?')"><i class="bi bi-trash"></i></a>
                                            <?php } else { ?>
                                                <span class="btn btn-sm btn-secondary" disabled
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

                <!-- 🔢 PAGINATION -->
                <nav>
                    <ul class="pagination justify-content-end">

                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= $search ?>&customer=<?= $customer ?>&status=<?= $status ?>">
                                <i class="bi bi-caret-left-fill"></i>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                            <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= $search ?>&customer=<?= $customer ?>&status=<?= $status ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php } ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= $search ?>&customer=<?= $customer ?>&status=<?= $status ?>">
                                <i class="bi bi-caret-right-fill"></i>
                            </a>
                        </li>

                    </ul>
                </nav>
            </div>
        </div>
    </div>

</div>

<?php include "includes/footer.php"; ?>