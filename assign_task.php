<?php
include "includes/config.php";
include "includes/rbac.php";

requireAuth();
requirePermission('tasks');


if (!isset($_GET['id'])) {
    header("Location: task_library.php");
    exit();
}

$task_id = (int) $_GET['id'];
$selected_order = isset($_GET['selected_order']) ? (int) $_GET['selected_order'] : 0;
$res = $conn->query("SELECT * FROM task_library WHERE id=$task_id");
$task = $res->fetch_assoc();

if (!$task) {
    die("Task not found");
}

$orders = $conn->query("SELECT id, order_no, customer FROM orders ORDER BY id DESC");

$products = null;
if ($selected_order) {
    //if already has task of product then not select that product again for same order

    $products = $conn->query("
        SELECT oi.id, oi.product FROM orders o 
        left join order_items oi on o.order_no = oi.order_id 
        WHERE o.id = $selected_order
        AND oi.id NOT IN (
            SELECT product 
            FROM tasks 
            WHERE order_id = $selected_order
        )
        ORDER BY product ASC
    ");
}

$resourcesArr = getAvailableResources($conn, $task['default_time']);

if (isset($_POST['assign'])) {

    $order_id = (int) $_POST['order_id'];
    $user_id = (int) $_POST['user_id'];
    $priority = $_POST['priority'];
    $deadline = $_POST['deadline'];
    $deadline = date('Y-m-d', strtotime($deadline));
    $product_id = (int) $_POST['product_id'];

    if ($order_id == 0 || $user_id == 0) {
        $error = "Please select order and user";
    } else {

        $sql = "INSERT INTO tasks(order_id, task_name, est_time, status, user_id, assigned_by,priority,deadline, product)
                VALUES('$order_id', '{$task['task_name']}', '{$task['default_time']}', 'not_started', '$user_id', ".$_SESSION['user_id'].",'$priority','$deadline', '$product_id')";

        if ($conn->query($sql)) {
            $_SESSION['success'] = "Task assigned successfully.";
            header("Location: view_order.php?id=$order_id");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
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
                        📌 Assign Task: <?= $task['task_name'] ?>
                        <a href="task_library.php" class="pms-btn-back"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    </div>

                    <div class="pms-panel-body">

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mb-3"><?= $error ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="pms-form-label"><span class="text-danger">*</span> Select Order</label>
                                <select name="order_id" class="form-select" id="selectOrder" required>
                                    <option value="">Search Order...</option>
                                    <?php while ($o = $orders->fetch_assoc()): ?>
                                        <option value="<?= $o['id'] ?>" <?= ($selected_order == $o['id']) ? 'selected' : '' ?>>
                                            <?= $o['order_no'] ?> - <?= $o['customer'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select an order
                                </div>
                            </div>
                            <?php if ($selected_order && $products): ?>
                                <div class="col-md-6 mb-2">
                                    <label class="pms-form-label"><span class="text-danger">*</span> Select Product</label>
                                    <select name="product_id" class="form-select select2" required>
                                        <?php if($products->num_rows == 0): ?>
                                            <option value="">Product Not Found</option>
                                        <?php else: ?>
                                            <!-- <option value="">Search Product...</option> -->
                                            <?php while ($p = $products->fetch_assoc()): ?>
                                                <option value="<?= $p['id'] ?>">
                                                    <?= $p['product'] ?>
                                                </option>
                                        <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a product
                                    </div>
                                </div>
                            <?php endif; ?>
                            

                            <div class="col-md-6 mb-2">
                                <label class="pms-form-label"><span class="text-danger">*</span> Assign Resource</label>
                                <select name="user_id" class="form-select select2-subtext" required>
                                    <option value="">Search Resource...</option>
                                    <?php foreach ($resourcesArr as $r): 
                                        $remText = ($r['type'] == 'Part-time') ? " | Available: " . formatMinutes($r['remaining_mins']) : " | Full-time";
                                    ?>
                                        <option value="<?= $r['id'] ?>" data-subtext="(<?= $r['role'] . $remText ?>)">
                                            <?= $r['name'] ?> 
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a user
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="pms-form-label"><span class="text-danger">*</span> Priority</label>
                                <select name="priority" class="form-select select2" required>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>                                    
                                </select>
                                <div class="invalid-feedback">
                                    Please select a Priority
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="pms-form-label"><span class="text-danger">*</span>Deadline</label>
                                <input type="date" name="deadline" class="form-control" required>
                                <div class="invalid-feedback">
                                    Please select a Deadline
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="task_library.php" class="pms-btn-cancel"><i class="bi bi-x me-1"></i>Cancel</a>
                        <button type="submit" name="assign" class="pms-btn-dark btn-sm">
                            <i class="bi bi-check-lg"></i> Assign Task
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
<script>
document.getElementById('selectOrder').addEventListener('change', function() {
    const orderId = this.value;
    if (orderId) {
        window.location.href = `assign_task.php?id=<?= $task_id ?>&selected_order=${orderId}`;
    }   else {
        window.location.href = `assign_task.php?id=<?= $task_id ?>`;
    } 
});

(() => {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')

    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }

            form.classList.add('was-validated')
        }, false)
    })
})()

</script>