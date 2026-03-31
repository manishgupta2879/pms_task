<?php
include "includes/config.php";
include "includes/header.php";

if (!isset($_GET['id'])) {
    header("Location: task_library.php");
    exit();
}

$task_id = (int) $_GET['id'];

// get task
$res = $conn->query("SELECT * FROM task_library WHERE id=$task_id");
$task = $res->fetch_assoc();

if (!$task) {
    die("Task not found");
}

// get orders
$orders = $conn->query("SELECT id, order_no, customer FROM orders ORDER BY id DESC");

// ✅ get available resources
$resourcesArr = getAvailableResources($conn, $task['default_time']);

// assign task
if (isset($_POST['assign'])) {

    $order_id = (int) $_POST['order_id'];
    $user_id = (int) $_POST['user_id'];

    if ($order_id == 0 || $user_id == 0) {
        $error = "Please select order and user";
    } else {

        $sql = "INSERT INTO tasks(order_id, task_name, est_time, status, user_id)
                VALUES('$order_id', '{$task['task_name']}', '{$task['default_time']}', 'not_started', '$user_id')";

        if ($conn->query($sql)) {
            header("Location: view_order.php?id=$order_id&msg=task_added");
            exit();
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}
?>

<div class="pms-wrap">
    <div class="row">
        <div class="col-md-12">

            <form method="POST" class="needs-validation" novalidate>
                <div class="pms-panel">

                    <div class="pms-panel-header d-flex justify-content-between align-items-center">
                        📌 Assign Task: <?= $task['task_name'] ?>
                        <a href="task_library.php" class="btn btn-outline-secondary btn-sm">Back</a>
                    </div>

                    <div class="pms-panel-body">

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger mb-3"><?= $error ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
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

                            <div class="col-md-6">
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
                        </div>

                    </div>

                    <div class="pms-panel-footer text-end">
                        <a href="task_library.php" class="btn btn-outline-secondary btn-sm me-2">Cancel</a>
                        <button type="submit" name="assign" class="pms-btn-dark btn-sm">
                            <i class="bi bi-check-lg"></i> Assign Task
                        </button>
                    </div>

                </div>
            </form>

        </div>
    </div>
</div>

<!-- SELECT2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- SELECT2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Search...",
            width: '100%'
        });
    });
</script>

<?php include "includes/footer.php"; ?>