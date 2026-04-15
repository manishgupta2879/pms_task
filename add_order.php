<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('orders');
$order_no = '';
$customer = '';
$status = 'active';
$products = [];
$species_list = [];
$qtys = [];
$field_errors = [];

if (isset($_POST['save_order'])) {

    $order_no = trim($_POST['order_no'] ?? '');
    $customer = trim($_POST['customer'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $products = $_POST['product'] ?? [];
    $species_list = $_POST['species'] ?? [];
    $qtys = $_POST['qty'] ?? [];

    $raw_customer = $customer; // Keep raw for display
    $customer = $conn->real_escape_string($customer);
    $status = $conn->real_escape_string($status);
    $order_no = $conn->real_escape_string($order_no);


    if (empty($order_no) || !preg_match('/^\d{8}$/', $order_no)) {
        $field_errors['order_no'] = "Order number must be exactly 8 digits.";
    }
    if (empty(trim($raw_customer))) {
        $field_errors['customer'] = "Customer name is required.";
    }
    $valid_products_count = count(array_filter(array_map('trim', $products)));
    if ($valid_products_count == 0) {
        $field_errors['products'] = "At least one product is required.";
    }
    for ($i = 0; $i < count($products); $i++) {
        if (empty(trim($products[$i]))) {
            $field_errors["product_$i"] = "Product name is required in row " . ($i + 1);
        }
        if (empty(trim($species_list[$i] ?? ''))) {
            $field_errors["species_$i"] = "Species is required in row " . ($i + 1);
        }
        if (!is_numeric($qtys[$i] ?? 0) || ($qtys[$i] ?? 0) <= 0) {
            $field_errors["qty_$i"] = "Valid quantity required in row " . ($i + 1);
        }
    }

    $check = $conn->query("SELECT id FROM orders WHERE order_no = '$order_no'");
    if ($check->num_rows > 0) {
        $field_errors['order_no'] = "Order number already exists.";
    }
    if (!empty($field_errors)) {
        // $_SESSION['field_errors'] = $field_errors;
        // header("Location: add_order.php");
        // exit();
    } else {
        $conn->begin_transaction();
        try {

            $conn->query("INSERT INTO orders(order_no, customer, status)
              VALUES('$order_no', '$customer', '$status')");
            $order_id = $conn->insert_id;

            for ($i = 0; $i < count($products); $i++) {

                $product = $conn->real_escape_string($products[$i]);
                $species = $conn->real_escape_string($species_list[$i]);
                $qty = $conn->real_escape_string($qtys[$i]);

                $conn->query("INSERT INTO order_items(order_id, product, species, qty) VALUES('$order_no', '$product', '$species', '$qty')");
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

    // $res = $conn->query("SELECT MAX(id) as max_id FROM orders");
    // $row = $res->fetch_assoc();

    // $next_id = ($row['max_id'] ?? 0) + 1;

    // $order_no = 10000 + $next_id;

}
include "includes/header.php";
?>

<div class="pms-wrap">
    <div class="row">
        <?php if (!empty($field_errors) && 1 == 2): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($field_errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold" style="color: #1e293b;">
                            <?= isset($order_id) ? 'Edit Order' : 'Create Order' ?></h5>
                        <a href="orders.php" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">
                        <div class="row g-3">
                            <!-- 8 digit order number -->
                            <div class="col-md-4">
                                <label class="pms-form-label">
                                    <span class="text-danger">*</span> Order No
                                </label>
                                <input type="text" name="order_no" class="form-control <?= isset($field_errors['order_no']) ? 'is-invalid' : '' ?>"
                                    placeholder="Enter 8-digit Order No"
                                    value="<?= htmlspecialchars($order_no) ?>"
                                    required autofocus pattern="\d{8}" maxlength="8" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                                <div class="invalid-feedback">
                                    <?= isset($field_errors['order_no']) ? htmlspecialchars($field_errors['order_no']) : 'Please enter a valid 8-digit order number' ?>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="pms-form-label"><span class="text-danger">*</span> Customer</label>
                                <input type="text" name="customer" class="form-control <?= isset($field_errors['customer']) ? 'is-invalid' : '' ?>" placeholder="Customer Name"
                                    value="<?= htmlspecialchars($customer) ?>" required>
                                <div class="invalid-feedback">
                                    <?= isset($field_errors['customer']) ? htmlspecialchars($field_errors['customer']) : 'Please enter customer name' ?>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="pms-form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>
                            <div id="order-items">
                                <div class="d-flex justify-content-end align-items-center">
                                    <button type="button" id="add-row" class="btn btn-sm btn-primary mb-2 ">+ Add
                                        More</button>
                                </div>
                                <!-- <div class="row g-3 item-row mt-2">
                                    <div class="col-md-4 m-0">
                                        <label class="pms-form-label"><span class="text-danger">*</span>Product</label>
                                        <div class="position-relative">
                                            <input type="text" name="product[]" class="form-control product-input" placeholder="Product"
                                                required autocomplete="off">
                                            <ul class="product-suggestions list-unstyled position-absolute bg-white border rounded mt-1" style="display:none; width:100%; max-height:200px; overflow-y:auto; z-index:1000; top:100%;">
                                            </ul>
                                        </div>
                                        <div class="invalid-feedback">Please enter product name</div>
                                    </div>
                                    <div class="col-md-4 m-0">
                                        <label class="pms-form-label"><span class="text-danger">*</span>Species</label>
                                        <input type="text" name="species[]" class="form-control" placeholder="Species"
                                            required>
                                        <div class="invalid-feedback">Please enter species name</div>
                                    </div>
                                    <div class="col-md-3 m-0">
                                        <label class="pms-form-label"><span class="text-danger">*</span>
                                            Quantity</label>
                                        <input type="number" name="qty[]" class="form-control" placeholder="Qty"
                                            required>
                                        <div class="invalid-feedback">Please enter Quantity</div>
                                    </div>
                                    <div class="col-md-1">
                                        <div>&nbsp;</div>
                                        <button type="button" class="btn btn-danger remove-row">X</button>
                                    </div>
                                </div> -->
                                <?php
                                $old_count = max(1, count($products));
                                for ($i = 0; $i < $old_count; $i++):
                                ?>
                                    <div class="row g-3 item-row mt-2">

                                        <!-- Product -->
                                        <div class="col-md-4 m-0">
                                            <label class="pms-form-label"><span class="text-danger">*</span>Product</label>
                                            <div class="position-relative">
                                                <input type="text"
                                                    name="product[]"
                                                    class="form-control product-input <?= isset($field_errors["product_$i"]) ? 'is-invalid' : '' ?>"
                                                    placeholder="Product"
                                                    value="<?= htmlspecialchars($products[$i] ?? '') ?>"
                                                    required autocomplete="off">

                                                <ul class="product-suggestions list-unstyled position-absolute bg-white border rounded mt-1"
                                                    style="display:none; width:100%; max-height:200px; overflow-y:auto; z-index:1000;">
                                                </ul>
                                            </div>
                                            <div class="invalid-feedback">
                                                <?= $field_errors["product_$i"] ?? 'Please enter product name' ?>
                                            </div>
                                        </div>

                                        <!-- Species -->
                                        <div class="col-md-4 m-0">
                                            <label class="pms-form-label"><span class="text-danger">*</span>Species</label>
                                            <input type="text"
                                                name="species[]"
                                                class="form-control <?= isset($field_errors["species_$i"]) ? 'is-invalid' : '' ?>"
                                                placeholder="Species"
                                                value="<?= htmlspecialchars($species_list[$i] ?? '') ?>"
                                                required>

                                            <div class="invalid-feedback">
                                                <?= $field_errors["species_$i"] ?? 'Please enter species name' ?>
                                            </div>
                                        </div>

                                        <!-- Quantity -->
                                        <div class="col-md-3 m-0">
                                            <label class="pms-form-label"><span class="text-danger">*</span>Quantity</label>
                                            <input type="number"
                                                name="qty[]"
                                                class="form-control <?= isset($field_errors["qty_$i"]) ? 'is-invalid' : '' ?>"
                                                placeholder="Qty"
                                                value="<?= htmlspecialchars($qtys[$i] ?? '') ?>"
                                                required>

                                            <div class="invalid-feedback">
                                                <?= $field_errors["qty_$i"] ?? 'Please enter Quantity' ?>
                                            </div>
                                        </div>

                                        <!-- Remove button -->
                                        <div class="col-md-1">
                                            <div>&nbsp;</div>
                                            <button type="button" class="btn btn-danger remove-row">X</button>
                                        </div>

                                    </div>
                                <?php endfor; ?>
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
    // Product autocomplete functionality with debouncing
    function setupProductAutocomplete(input) {
        const suggestionsList = input.closest('.position-relative').querySelector('.product-suggestions');
        let debounceTimer;
        const DEBOUNCE_DELAY = 300; // 300ms debounce

        input.addEventListener('keyup', function(e) {
            const query = this.value.trim();

            // Clear existing timer
            clearTimeout(debounceTimer);

            // Don't show suggestions if input is empty
            if (query.length === 0) {
                suggestionsList.style.display = 'none';
                return;
            }

            // Show loader
            suggestionsList.innerHTML = '<li class="px-3 py-2 text-center"><span class="spinner-border spinner-border-sm me-2"></span>Loading...</li>';
            suggestionsList.style.display = 'block';

            // Debounce the API call
            debounceTimer = setTimeout(async function() {
                try {
                    const response = await fetch('get_product_suggestions.php?q=' + encodeURIComponent(query));
                    const data = await response.json();

                    // Clear previous suggestions
                    suggestionsList.innerHTML = '';

                    if (data.length > 0) {
                        data.forEach(product => {
                            const li = document.createElement('li');
                            li.className = 'px-3 py-2 cursor-pointer suggestion-item';
                            li.textContent = product;
                            li.style.cursor = 'pointer';
                            li.style.borderBottom = '1px solid #f0f0f0';

                            li.addEventListener('mouseenter', () => {
                                li.style.backgroundColor = '#e9ecef';
                            });
                            li.addEventListener('mouseleave', () => {
                                li.style.backgroundColor = '';
                            });

                            li.addEventListener('click', () => {
                                input.value = product;
                                suggestionsList.style.display = 'none';
                            });

                            suggestionsList.appendChild(li);
                        });
                        suggestionsList.style.display = 'block';
                    } else {
                        // Show "No records found" message
                        const li = document.createElement('li');
                        li.className = 'px-3 py-2 text-center text-muted';
                        li.textContent = 'No records found';
                        li.style.cursor = 'default';
                        suggestionsList.appendChild(li);
                        suggestionsList.style.display = 'block';
                    }
                } catch (error) {
                    console.error('Error fetching suggestions:', error);
                    suggestionsList.innerHTML = '<li class="px-3 py-2 text-center text-danger">Error loading suggestions</li>';
                    suggestionsList.style.display = 'block';
                }
            }, DEBOUNCE_DELAY);
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.position-relative')) {
                suggestionsList.style.display = 'none';
            }
        });

        // Show suggestions on focus if there's a value
        input.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                const event = new KeyboardEvent('keyup', {
                    bubbles: true
                });
                this.dispatchEvent(event);
            }
        });
    }

    // Initialize autocomplete for existing and new product inputs
    function initializeProductInputs() {
        document.querySelectorAll('.product-input').forEach(input => {
            // Remove old event listeners by cloning
            const newInput = input.cloneNode(true);
            input.parentNode.replaceChild(newInput, input);
            setupProductAutocomplete(newInput);
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeProductInputs();
    });

    document.getElementById('add-row').addEventListener('click', function() {
        let row = document.querySelector('.item-row').cloneNode(true);

        row.querySelectorAll('input').forEach(input => {
            input.value = '';
            input.classList.remove('is-invalid'); // reset validation
        });

        document.getElementById('order-items').appendChild(row);

        // Reinitialize autocomplete for the new product input
        const newProductInput = row.querySelector('.product-input');
        setupProductAutocomplete(newProductInput);
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
        let products = [];
        let duplicateProducts = new Set();

        // Check for empty fields and collect products
        document.querySelectorAll('.item-row').forEach(row => {
            let productInput = row.querySelector('input[name="product[]"]');
            let inputs = row.querySelectorAll('input');

            // Check for empty fields
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('is-invalid');
                }
            });

            // Check for duplicate products
            if (productInput && productInput.value.trim()) {
                let productValue = productInput.value.trim().toLowerCase();
                if (products.includes(productValue)) {
                    duplicateProducts.add(productValue);
                    valid = false;
                } else {
                    products.push(productValue);
                }
            }
        });

        // Mark duplicate products as invalid
        if (duplicateProducts.size > 0) {
            document.querySelectorAll('.item-row').forEach(row => {
                let productInput = row.querySelector('input[name="product[]"]');
                if (productInput) {
                    let productValue = productInput.value.trim().toLowerCase();
                    if (duplicateProducts.has(productValue)) {
                        productInput.classList.add('is-invalid');
                        productInput.title = 'Duplicate product in this order';
                    }
                }
            });
            // Show alert
            alert('Error: Duplicate products found in the same order. Please remove duplicates.');
        }

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