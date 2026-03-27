<?php 
include "includes/config.php";
include "includes/header.php";

// ✅ DELETE ORDER
if(isset($_GET['delete'])){
    $delete_id = (int)$_GET['delete'];

    // delete tasks first (important)
    $conn->query("DELETE FROM tasks WHERE order_id=$delete_id");

    // delete order
    $conn->query("DELETE FROM orders WHERE id=$delete_id");

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
$where = "WHERE 1";

if($search != ''){
    $where .= " AND (order_no LIKE '%$search%' OR product LIKE '%$search%')";
}

if($status != ''){
    $where .= " AND status='$status'";
}

if($customer != ''){
    $where .= " AND customer LIKE '%$customer%'";
}

// total count
$totalRes = $conn->query("SELECT COUNT(*) as total FROM orders $where");
$total = $totalRes->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// main query
$query = "SELECT * FROM orders $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
?>

<div class="card shadow">

<div class="card-header d-flex justify-content-between">
    <h4>📦 Orders List</h4>
</div>

<div class="card-body">

<!-- ✅ MESSAGE -->
<?php if(isset($_GET['msg']) && $_GET['msg']=='deleted'){ ?>
    <div class="alert alert-danger">Order deleted successfully</div>
<?php } ?>

<!-- 🔍 SEARCH + FILTER -->
<form method="GET" class="row mb-3">

    <div class="col-md-3">
        <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= $search ?>">
    </div>

    <div class="col-md-3">
        <input type="text" name="customer" class="form-control" placeholder="Customer" value="<?= $customer ?>">
    </div>

    <div class="col-md-3">
        <select name="status" class="form-select">
            <option value="">Status</option>
            <option value="active" <?= $status=='active'?'selected':'' ?>>Active</option>
            <option value="completed" <?= $status=='completed'?'selected':'' ?>>Completed</option>
            <option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
        </select>
    </div>

    <div class="col-md-3">
        <button class="btn btn-primary">Filter</button>
        <a href="orders.php" class="btn btn-secondary">Reset</a>
    </div>

</form>

<!-- ➕ CREATE BUTTON -->
<div class="mb-3 text-end">
    <a href="add_order.php" class="btn btn-success">+ Create New Order</a>
</div>

<!-- 📊 TABLE -->
<table class="table table-bordered table-hover align-middle">
<thead class="table-dark">
<tr>
    <th>Order #</th>
    <th>Customer</th>
    <th>Product</th>
    <th>Deadline</th>
    <th>Status</th>
    <th width="180">Actions</th>
</tr>
</thead>

<tbody>
<?php if($result->num_rows == 0){ ?>
<tr>
    <td colspan="6" class="text-center">No orders found</td>
</tr>
<?php } ?>

<?php while($row = $result->fetch_assoc()){ ?>
<tr>
    <td><?= $row['order_no'] ?></td>
    <td><?= $row['customer'] ?></td>
    <td><?= $row['product'] ?></td>
    <td><?= date("M d", strtotime($row['deadline'])) ?></td>

    <td>
        <?php if($row['status']=='active'){ ?>
            <span class="badge bg-primary">Active</span>
        <?php } elseif($row['status']=='completed'){ ?>
            <span class="badge bg-success">Completed</span>
        <?php } else { ?>
            <span class="badge bg-warning text-dark">Pending</span>
        <?php } ?>
    </td>

    <td>
        <a href="view_order.php?id=<?= $row['id'] ?>" 
           class="btn btn-sm btn-info">
           View
        </a>

        <a href="edit_order.php?id=<?= $row['id'] ?>" 
           class="btn btn-sm btn-warning">
           Edit
        </a>

        <a href="orders.php?delete=<?= $row['id'] ?>" 
           class="btn btn-sm btn-danger"
           onclick="return confirm('Delete this order and all tasks?')">
           Delete
        </a>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

<!-- 🔢 PAGINATION -->
<nav>
<ul class="pagination justify-content-center">

    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
        <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= $search ?>&customer=<?= $customer ?>&status=<?= $status ?>">
            Previous
        </a>
    </li>

    <?php for($i = 1; $i <= $total_pages; $i++){ ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&search=<?= $search ?>&customer=<?= $customer ?>&status=<?= $status ?>">
                <?= $i ?>
            </a>
        </li>
    <?php } ?>

    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
        <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= $search ?>&customer=<?= $customer ?>&status=<?= $status ?>">
            Next
        </a>
    </li>

</ul>
</nav>

</div>
</div>

<?php include "includes/footer.php"; ?>