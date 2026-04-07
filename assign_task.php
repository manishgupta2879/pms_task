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

$res = $conn->query("SELECT * FROM task_library WHERE id=$task_id");
$task = $res->fetch_assoc();

if (!$task) {
    die("Task not found");
}

$orders = $conn->query("SELECT id, order_no, customer FROM orders ORDER BY id DESC");

$resourcesArr = getAvailableResources($conn, $task['default_time']);

if (isset($_POST['assign'])) {

    $order_id = (int) $_POST['order_id'];
    $user_id = (int) $_POST['user_id'];
    $priority = $_POST['priority'];

    if ($order_id == 0 || $user_id == 0) {
        $error = "Please select order and user";
    } else {

        $sql = "INSERT INTO tasks(order_id, task_name, est_time, status, user_id, assigned_by,priority)
                VALUES('$order_id', '{$task['task_name']}', '{$task['default_time']}', 'not_started', '$user_id', ".$_SESSION['user_id'].",'$priority')";

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
                                <select name="order_id" class="form-select select2" required>
                                    <option value="">Search Order...</option>
                                    <?php while ($o = $orders->fetch_assoc()): ?>
                                        <option value="<?= $o['id'] ?>">
                                            <?= $o['order_no'] ?> - <?= $o['customer'] ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback">
                                    Please select an order
                                </div>
                            </div>

                            <div class="col-md-6 mb-2">
                                <label class="pms-form-label"><span class="text-danger">*</span> Assign Resource</label>
                                <select name="user_id" class="form-select select2" required>
                                    <option value="">Search User...</option>
                                    <?php foreach ($resourcesArr as $r): 
                                        $remText = ($r['type'] == 'Part-time') ? " | Available: " . formatMinutes($r['remaining_mins']) : " | Full-time";
                                    ?>
                                        <option value="<?= $r['id'] ?>">
                                            <?= $r['name'] ?> (<?= $r['role'] . $remText ?>)
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