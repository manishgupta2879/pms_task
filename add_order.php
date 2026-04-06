<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');

if (isset($_POST['save_order'])) {

    $customer = $_POST['customer'];
    $deadline = $_POST['deadline'];
    $status = $_POST['status'];
    $customer = $conn->real_escape_string($customer);
    $deadline = $conn->real_escape_string($deadline);
    $status = $conn->real_escape_string($status);
    $products = $_POST['product'];
    $species_list = $_POST['species'];
    $qtys = $_POST['qty'];

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
        header("Location: create_order.php");
        exit();
    }

    $res = $conn->query("SELECT MAX(id) as max_id FROM orders");
    $row = $res->fetch_assoc();

    $next_id = ($row['max_id'] ?? 0) + 1;

    $order_no = 10000 + $next_id;
    $conn->begin_transaction();

    try {

        for ($i = 0; $i < count($products); $i++) {

            $product = $conn->real_escape_string($products[$i]);
            $species = $conn->real_escape_string($species_list[$i]);
            $qty = $conn->real_escape_string($qtys[$i]);

            $sql = "INSERT INTO orders(order_no, customer, product, deadline, status, species, qty)
                    VALUES('$order_no', '$customer', '$product', '$deadline', '$status', '$species', '$qty')";

            if (!$conn->query($sql)) {
                throw new Exception($conn->error);
            }
        }

        $conn->commit();

        $_SESSION['success'] = "Order created successfully.";
        header("Location: orders.php");
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
        <?php if (!empty($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        <?= isset($order_id) ? 'Edit Order' : 'Create Order' ?>
                        <a href="orders.php" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="pms-form-label"><span class="text-danger">*</span> Customer</label>
                                <input type="text" name="customer" class="form-control" placeholder="Customer Name"
                                    value="<?= htmlspecialchars($customer ?? '') ?>" required autofocus>
                                <div class="invalid-feedback">Please enter customer name</div>
                            </div>
                            <div class="col-md-3">
                                <label class="pms-form-label"><span class="text-danger">*</span> Deadline</label>
                                <input type="date" name="deadline" class="form-control"
                                    value="<?= htmlspecialchars($deadline ?? '') ?>" required>
                                <div class="invalid-feedback">Please select deadline</div>
                            </div>

                            <div class="col-md-3">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= (isset($status) && $status == 'active') ? 'selected' : '' ?>>Active</option>
                                    <option value="pending" <?= (isset($status) && $status == 'pending') ? 'selected' : '' ?>>Pending</option>
                                    <option value="completed" <?= (isset($status) && $status == 'completed') ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>
                            <div id="order-items">
                                <div class="d-flex justify-content-end align-items-center">
                                    <button type="button" id="add-row" class="btn btn-sm btn-primary mb-2 ">+ Add More</button>
                                </div>
                                <div class="row g-3 item-row mt-2">
                                    <div class="col-md-4 m-0">
                                        <label class="pms-form-label"><span class="text-danger">*</span>Product</label>
                                        <input type="text" name="product[]" class="form-control" placeholder="Product" required>
                                        <div class="invalid-feedback">Please enter product name</div>
                                    </div>
                                    <div class="col-md-4 m-0">
                                        <label class="pms-form-label"><span class="text-danger">*</span>Species</label>
                                        <input type="text" name="species[]" class="form-control" placeholder="Species" required>
                                        <div class="invalid-feedback">Please enter species name</div>
                                    </div>
                                    <div class="col-md-3 m-0">
                                        <label class="pms-form-label"><span class="text-danger">*</span> Quantity</label>
                                        <input type="number" name="qty[]" class="form-control" placeholder="Qty" required>
                                        <div class="invalid-feedback">Please enter Quantity</div>
                                    </div>
                                    <div class="col-md-1">
                                        <div>&nbsp;</div>
                                        <button type="button" class="btn btn-danger remove-row">X</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="orders.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
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