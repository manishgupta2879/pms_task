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
$order_id = $_GET['id'];
$order_id = (int)$order_id;

// get order
$res = $conn->query("SELECT * FROM orders WHERE id = $order_id");
$order = $res->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header("Location: orders.php");
    exit();
}
$order_no = $order['order_no'];
$customer = $order['customer'];
$deadline = $order['deadline'];
$status   = $order['status'];
$items_res = $conn->query("SELECT * FROM order_items WHERE order_id = $order_no");
$items = [];
while ($row = $items_res->fetch_assoc()) {
    $items[] = $row;
}

// update
if (isset($_POST['update'])) {

    $order_id = (int)($_POST['order_id'] ?? 0);
    $customer = $conn->real_escape_string($_POST['customer']);
    $deadline = $conn->real_escape_string($_POST['deadline']);
    $status   = $conn->real_escape_string($_POST['status']);
    $products  = $_POST['product'];
    $species_list  = $_POST['species'];
    $qtys  = $_POST['qty'];

    $errors = [];
    if (empty(trim($customer))) {
        $errors[] = "Customer name is required.";
    }

    if (empty($deadline)) {
        $errors[] = "Deadline is required.";
    }
    if (!isset($products) || !is_array($products) || count($products) == 0) {
        $errors[] = "At least one product is required.";
    }
    for ($i = 0; $i < count($products); $i++) {

        if (empty(trim($products[$i]))) {
            $errors[] = "Product name is required in row " . ($i + 1);
        }

        if (empty(trim($species_list[$i]))) {
            $errors[] = "Species is required in row " . ($i + 1);
        }

        if (!is_numeric($qtys[$i]) || $qtys[$i] <= 0) {
            $errors[] = "Valid quantity required in row " . ($i + 1);
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: edit_order.php?id=" . $order_id);
        exit();
    }

    $conn->begin_transaction();
    try {

        $order_no = $order['order_no'];
        $conn->query("UPDATE orders 
                SET customer='$customer', deadline='$deadline', status='$status'
                WHERE id='$order_id'");

        $conn->query("DELETE FROM order_items WHERE order_id='$order_no'");

        for ($i = 0; $i < count($products); $i++) {

            $product = $conn->real_escape_string($products[$i]);
            $species = $conn->real_escape_string($species_list[$i]);
            $qty     = $conn->real_escape_string($qtys[$i]);

            $conn->query("INSERT INTO order_items(order_id, product, species, qty)
                VALUES('$order_no', '$product', '$species', '$qty')");
        }

        $conn->commit();

        $_SESSION['success'] = $order_id ? "Order updated successfully." : "Order created successfully.";
        // header("Location: orders.php");
        header("Location: edit_order.php?id=" . $order_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        die("Error: " . $e->getMessage());
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
                        <a href="view_order.php?id=<?= $order_id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>
                            Back
                        </a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row g-3">
                            <input type="hidden" name="order_id" value="<?= $order_id ?? '' ?>">
                            <div class="col-md-6">
                                <label class="pms-form-label">Order #</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($order['order_no']) ?>" disabled>
                            </div>

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Customer</label>
                                <input type="text" name="customer" class="form-control" value="<?= htmlspecialchars($order['customer']) ?>" required>
                                <div class="invalid-feedback">Please enter customer name</div>
                            </div>
                            <div id="order-items">
                                <div class="d-flex justify-content-end align-items-center">
                                    <button type="button" id="add-row" class="btn btn-sm btn-primary mb-2 ">+ Add More</button>
                                </div>
                                <?php if (!empty($items)): ?>
                                    <?php foreach ($items as $item): ?>
                                        <div class="row g-3 item-row mt-2">
                                            <div class="col-md-4">
                                                <label class="pms-form-label"><span class="text-danger">*</span>Product</label>
                                                <input type="text" name="product[]" class="form-control"
                                                    value="<?= htmlspecialchars($item['product']) ?>" required>
                                                <div class="invalid-feedback">Please enter product name</div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="pms-form-label"><span class="text-danger">*</span>Species</label>
                                                <input type="text" name="species[]" class="form-control"
                                                    value="<?= htmlspecialchars($item['species']) ?>" required>
                                                <div class="invalid-feedback">Please enter species name</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="pms-form-label"><span class="text-danger">*</span>Quantity</label>
                                                <input type="number" name="qty[]" class="form-control"
                                                    value="<?= htmlspecialchars($item['qty']) ?>" required>
                                                <div class="invalid-feedback">Please enter quantity</div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-danger remove-row">X</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row g-3 item-row mt-2">
                                        <div class="col-md-4">
                                            <label class="pms-form-label"><span class="text-danger">*</span>Product</label>
                                            <input type="text" name="product[]" class="form-control" placeholder="Product" required>
                                            <div class="invalid-feedback">Please enter product name</div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="pms-form-label"><span class="text-danger">*</span>Species</label>
                                            <input type="text" name="species[]" class="form-control" placeholder="Species" required>
                                            <div class="invalid-feedback">Please enter species name</div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="pms-form-label"><span class="text-danger">*</span>Quantity</label>
                                            <input type="number" name="qty[]" class="form-control" placeholder="Quantity" required>
                                            <div class="invalid-feedback">Please enter quantity</div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger remove-row">X</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
                        <a href="view_order.php?id=<?= $order_id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x me-1"></i>Cancel</a>
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
    document.getElementById('add-row').addEventListener('click', function() {
        let row = document.querySelector('.item-row').cloneNode(true);

        row.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.classList.remove('is-invalid'); // reset validation
        });

        document.getElementById('order-items').appendChild(row);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            if (document.querySelectorAll('.item-row').length > 1) {
                e.target.closest('.item-row').remove();
            }
        }
    });
    document.querySelector('form').addEventListener('submit', function(e) {
        let valid = true;

        document.querySelectorAll('.item-row').forEach(row => {
            let inputs = row.querySelectorAll('input');
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('is-invalid');
                }
            });
        });

        if (!valid) {
            e.preventDefault();
        }
    });
</script>
<script>
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
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