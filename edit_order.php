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
    $status   = $conn->real_escape_string($_POST['status']);
    $products  = $_POST['product'];
    $species_list  = $_POST['species'];
    $qtys  = $_POST['qty'];

    $errors = [];
    if (empty(trim($customer))) {
        $errors[] = "Customer name is required.";
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

        $conn->query("UPDATE orders SET customer='$customer', status='$status' WHERE id='$order_id'");

        $existing_ids = [];

        for ($i = 0; $i < count($products); $i++) {

            $item_id = $_POST['item_id'][$i] ?? '';

            $product = $conn->real_escape_string($products[$i]);
            $species = $conn->real_escape_string($species_list[$i]);
            $qty     = (int)$qtys[$i];

            if (!empty($item_id)) {
                $existing_ids[] = $item_id;

                $conn->query("UPDATE order_items 
                    SET product='$product', species='$species', qty='$qty'
                    WHERE id='$item_id'");
            } else {

                $conn->query("INSERT INTO order_items(order_id, product, species, qty)
                    VALUES('$order_no', '$product', '$species', '$qty')");

                $existing_ids[] = $conn->insert_id;
            }
        }

        $old_items = $conn->query("SELECT id FROM order_items WHERE order_id='$order_no'");

        while ($old = $old_items->fetch_assoc()) {

            if (!in_array($old['id'], $existing_ids)) {
                $task_check = $conn->query("SELECT id FROM tasks WHERE product='{$old['id']}' LIMIT 1");
                if ($task_check->num_rows == 0) {
                    $conn->query("DELETE FROM order_items WHERE id='{$old['id']}'");
                }
            }
        }

        $conn->commit();

        $_SESSION['success'] = "Order updated successfully.";
        header("Location: edit_order.php?id=" . $order_id);
        exit();
    } catch (Exception $e) {

        $conn->rollback();

        $_SESSION['error'] = $e->getMessage();
        header("Location: edit_order.php?id=" . $order_id);
        exit();
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
                        <h5 class="mb-0 fw-bold" style="color: #1e293b;">Edit Order</h5>
                        <a href="view_order.php?id=<?= $order_id ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>
                            Back
                        </a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row g-3">
                            <input type="hidden" name="order_id" value="<?= $order_id ?? '' ?>">
                            <div class="col-md-6">
                                <label class="pms-form-label">Order No</label>
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
                                        <?php
                                        // check if task exists for this product
                                        $task_check = $conn->query("SELECT id FROM tasks WHERE product='{$item['id']}' LIMIT 1");
                                        $hasTask = $task_check->num_rows > 0;
                                        ?>
                                        <div class="row g-3 item-row mt-2">
                                            <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
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
                                                <?php if ($hasTask): ?>
                                                    <button type="button" class="btn btn-secondary" disabled title="Task assigned">🔒</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-danger remove-row">X</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="row g-3 item-row mt-2">
                                        <input type="hidden" name="item_id[]" value="">
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
                                <label class="pms-form-label"><span class="text-danger">*</span> Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?= ($order['status'] == 'active') ? 'selected' : '' ?>>Active</option>
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
            input.classList.remove('is-invalid');
        });

        // ✅ clear hidden ID
        let hidden = row.querySelector('input[name="item_id[]"]');
        if (hidden) hidden.value = '';

        // ✅ enable remove button (in case cloned from locked row)
        let btn = row.querySelector('.btn');
        if (btn) {
            btn.disabled = false;
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-danger', 'remove-row');
            btn.innerText = 'X';
        }

        document.getElementById('order-items').appendChild(row);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            if (e.target.disabled) return;
            if (document.querySelectorAll('.item-row').length > 1) {
                e.target.closest('.item-row').remove();
            }
        }
    });
    document.querySelector('form').addEventListener('submit', function(e) {
        let valid = true;

        document.querySelectorAll('.item-row').forEach(row => {

            let product = row.querySelector('input[name="product[]"]');
            let species = row.querySelector('input[name="species[]"]');
            let qty = row.querySelector('input[name="qty[]"]');

            if (!product.value.trim()) {
                valid = false;
                product.classList.add('is-invalid');
            }

            if (!species.value.trim()) {
                valid = false;
                species.classList.add('is-invalid');
            }

            if (!qty.value || qty.value <= 0) {
                valid = false;
                qty.classList.add('is-invalid');
            }
        });

        if (!valid) {
            e.preventDefault();
        }
    });
    document.addEventListener('input', function(e) {
        if (e.target.matches('input')) {
            e.target.classList.remove('is-invalid');
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