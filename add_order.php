<?php
include "includes/config.php";

if (isset($_POST['save'])) {

    // ✅ get max id safely
    $res = $conn->query("SELECT MAX(id) as max_id FROM orders");
    $row = $res->fetch_assoc();

    // fix NULL case
    $next_id = ($row['max_id'] ?? 0) + 1;

    // generate order number
    $order_no = 10000 + $next_id;

    $customer = $_POST['customer'];
    $product = $_POST['product'];
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];

    // ✅ insert with error check
    $sql = "INSERT INTO orders(order_no,customer,product,deadline,status)
            VALUES('$order_no','$customer','$product','$deadline','$status')";

    if ($conn->query($sql)) {
        header("Location: orders.php?msg=created");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}
include "includes/header.php";
?>

<!-- <div class="card shadow">
    <div class="card-header">
        <h4>Create Order</h4>
    </div>

    <div class="card-body">

        <form method="POST">

            <input class="form-control mb-2" name="customer" placeholder="Customer" required>

            <input class="form-control mb-2" name="product" placeholder="Product" required>

            <input type="date" class="form-control mb-2" name="deadline" required>

            <select class="form-select mb-2" name="status">
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
            </select>

            <button class="btn btn-success" name="save">Save Order</button>

        </form>

    </div>
</div> -->
<div class="card shadow-lg border-0 rounded-3">
    <div class="card-header border-0 bg-transparent d-flex justify-content-between align-items-center">
        <h4 class="mb-0"> Create Order</h4>
        <a href="orders.php" class="btn btn-success btn-sm">
            <i class="bi bi-list"></i> Order List
        </a>
    </div>

    <div class="card-body">
        <form method="POST" class="row g-3">

            <div class="col-md-6">
                <label class="form-label fw-bold">Customer</label>
                <input type="text" name="customer" class="form-control" placeholder="Customer Name" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Product</label>
                <input type="text" name="product" class="form-control" placeholder="Product Name" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Deadline</label>
                <input type="date" name="deadline" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="pending">Pending</option>
                    <option value="completed">Completed</option>
                </select>
            </div>

            <div class="row p-0 m-0 justify-content-end">
                <div class="col-12 col-md-3 text-end mt-3">
                    <button type="submit" name="save" class="btn btn-success px-4 py-2">
                        <i class="bi bi-check-circle me-1"></i> Save Order
                    </button>
                </div>
            </div>


        </form>

    </div>
</div>

<?php include "includes/footer.php"; ?>