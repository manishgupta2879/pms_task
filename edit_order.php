<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');
// include "includes/header.php";

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
    $species  = $conn->real_escape_string($_POST['species']);
    $qty  = $conn->real_escape_string($_POST['qty']);
    $deadline = $_POST['deadline'];
    $status   = $_POST['status'];

    // ✅ DEBUG (optional remove later)
    // echo $status; exit();

    $sql = "UPDATE orders 
            SET customer='$customer',
                product='$product',
                deadline='$deadline',
                status='$status',
                species ='$species',
                qty='$qty'
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
<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        Edit Order
                        <a href="view_order.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>
                             Back
                        </a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="pms-form-label">Order #</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($order['order_no']) ?>" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Customer</label>
                                <input type="text" name="customer" class="form-control" value="<?= htmlspecialchars($order['customer']) ?>" required>
                                <div class="invalid-feedback">Please enter customer name</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Product</label>
                                <input type="text" name="product" class="form-control" value="<?= htmlspecialchars($order['product']) ?>" required>
                                <div class="invalid-feedback">Please enter product name</div>
                            </div>
                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Species</label>
                                <input type="text" name="species" class="form-control" value="<?= htmlspecialchars($order['species']) ?>" required>
                                <div class="invalid-feedback">Please enter Species name</div>
                            </div>
                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Quantity</label>
                                <input type="text" name="qty" class="form-control" value="<?= htmlspecialchars($order['qty']) ?>" required>
                                <div class="invalid-feedback">Please enter Quantity</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Deadline</label>
                                <input type="date" name="deadline" class="form-control" value="<?= htmlspecialchars($order['deadline']) ?>" required>
                                <div class="invalid-feedback">Please select deadline</div>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?= ($order['status'] == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="pending" <?= ($order['status'] == 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="completed" <?= ($order['status'] == 'completed') ? 'selected' : '' ?>>Completed</option>
                                </select>
                                <div class="invalid-feedback">Please select status</div>
                            </div>

                        </div>
                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="view_order.php?id=<?= $id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="update" class="pms-btn-dark btn-sm">
                            <i class="bi bi-pencil"></i> Update Order
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