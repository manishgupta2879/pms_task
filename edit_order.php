<?php
include "includes/config.php";

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$id = (int)$_GET['id'];

// get order
$res = $conn->query("SELECT * FROM orders WHERE id=$id");
$order = $res->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// update
if (isset($_POST['update'])) {

    $customer = $conn->real_escape_string($_POST['customer']);
    $product  = $conn->real_escape_string($_POST['product']);
    $deadline = $_POST['deadline'];
    $status   = $_POST['status'];

    // ✅ DEBUG (optional remove later)
    // echo $status; exit();

    $sql = "UPDATE orders 
            SET customer='$customer',
                product='$product',
                deadline='$deadline',
                status='$status'
            WHERE id=$id";

    if ($conn->query($sql)) {
        header("Location: view_order.php?id=$id&msg=updated");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}
include "includes/header.php";
?>

<div class="card shadow-lg border-0 rounded-3">
    <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Edit Order</h4>
        <a href="view_order.php?id=<?= $id ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card-body">
        <form method="POST" class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-bold">Order #</label>
                <input type="text" class="form-control" value="<?= $order['order_no'] ?>" disabled>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Customer</label>
                <input type="text" name="customer" class="form-control"
                    value="<?= $order['customer'] ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Product</label>
                <input type="text" name="product" class="form-control"
                    value="<?= $order['product'] ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Deadline</label>
                <input type="date" name="deadline" class="form-control"
                    value="<?= $order['deadline'] ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select" required>
                    <option value="active" <?= ($order['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="pending" <?= ($order['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= ($order['status'] == 'completed') ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="col-12 col-md-3 align-self-end">                
                    <button type="submit" name="update" class="btn btn-success w-100">
                         <i class="bi bi-check-circle me-1"></i> Update Order
                    </button>
            </div>
            <!-- <div class="row p-0 m-0 justify-content-end">
                <div class="col-12 col-md-3 text-end mt-3">
                    <button type="submit" name="update" class="btn btn-warning px-4 py-2">
                        <i class="bi bi-pencil-square me-1"></i> Update Order
                    </button>
                </div>
            </div> -->

        </form>
    </div>
</div>

<?php include "includes/footer.php"; ?>