<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');
// include "includes/header.php";

if (isset($_POST['save_order'])) {

    
    $res = $conn->query("SELECT MAX(id) as max_id FROM orders");
    $row = $res->fetch_assoc();

    $next_id = ($row['max_id'] ?? 0) + 1;

    $order_no = 10000 + $next_id;

    $customer = $_POST['customer'];
    $product = $_POST['product'];
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];

    $sql = "INSERT INTO orders(order_no,customer,product,deadline,status)
            VALUES('$order_no','$customer','$product','$deadline','$status')";

    if ($conn->query($sql)) {
        header("Location: orders.php?msg=created");
        exit();
    } else {
        die($conn->error);
        echo "<div class='alert alert-danger'>Error: " . $conn->error . "</div>";
    }
}

include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        <?= isset($order_id) ? 'Edit Order' : 'Create Order' ?>
                        <a href="orders.php" class="btn btn-outline-secondary btn-sm">Back to Orders</a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Customer</label>
                                <input type="text" name="customer" class="form-control" placeholder="Customer Name"
                                    value="<?= htmlspecialchars($customer ?? '') ?>" required autofocus>
                                <div class="invalid-feedback">Please enter customer name</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Product</label>
                                <input type="text" name="product" class="form-control" placeholder="Product Name"
                                    value="<?= htmlspecialchars($product ?? '') ?>" required>
                                <div class="invalid-feedback">Please enter product name</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Deadline</label>
                                <input type="date" name="deadline" class="form-control"
                                    value="<?= htmlspecialchars($deadline ?? '') ?>" required>
                                <div class="invalid-feedback">Please select deadline</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= (isset($status) && $status == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="pending" <?= (isset($status) && $status == 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="completed" <?= (isset($status) && $status == 'completed') ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="orders.php" class="btn btn-outline-secondary btn-sm me-2">Cancel</a>
                        <button type="submit" name="save_order" class="pms-btn-dark btn-sm">
                            <i class="bi bi-check-lg"></i> Save Order
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>
<script>
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
    })()
</script>
<?php include "includes/footer.php"; ?>